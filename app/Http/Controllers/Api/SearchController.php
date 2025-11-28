<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 3: Server Header & Navigation
 * Search Controller for searching messages within a server
 */
class SearchController extends Controller
{
    /**
     * Search messages within a server
     *
     * @param Request $request
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, Server $server)
    {
        // Validate user is a member of the server
        $user = Auth::user();
        if (!$user || !$server->members->contains($user->id)) {
            return response()->json([
                'error' => 'You are not a member of this server',
                'results' => [],
                'total' => 0
            ], 403);
        }

        // Validate request
        $request->validate([
            'q' => 'nullable|string|max:100',
            'channel' => 'nullable|string|max:50',
            'from' => 'nullable|string|max:50',
            'has' => 'nullable|string|in:link,image,file,embed',
            'before' => 'nullable|date',
            'after' => 'nullable|date',
            'page' => 'nullable|integer|min:1'
        ]);

        $query = $request->input('q', '');
        $channelFilter = $request->input('channel');
        $fromFilter = $request->input('from');
        $hasFilter = $request->input('has');
        $beforeFilter = $request->input('before');
        $afterFilter = $request->input('after');
        $page = $request->input('page', 1);
        $perPage = 25;

        // Build the query
        $messagesQuery = Message::query()
            ->with(['user.profile', 'channel'])
            ->whereHas('channel', function ($q) use ($server) {
                $q->where('server_id', $server->id);
            });

        // Search by content
        if (!empty($query)) {
            $messagesQuery->where('content', 'LIKE', '%' . $query . '%');
        }

        // Filter by channel name
        if (!empty($channelFilter)) {
            $messagesQuery->whereHas('channel', function ($q) use ($channelFilter) {
                $q->where('name', 'LIKE', '%' . $channelFilter . '%');
            });
        }

        // Filter by author username
        if (!empty($fromFilter)) {
            $messagesQuery->whereHas('user', function ($q) use ($fromFilter) {
                $q->where('username', 'LIKE', '%' . $fromFilter . '%')
                  ->orWhere('display_name', 'LIKE', '%' . $fromFilter . '%');
            });
        }

        // Filter by content type
        if (!empty($hasFilter)) {
            switch ($hasFilter) {
                case 'link':
                    $messagesQuery->where('content', 'REGEXP', 'https?://[^\s]+');
                    break;
                case 'image':
                    $messagesQuery->where(function ($q) {
                        $q->where('content', 'LIKE', '%.png%')
                          ->orWhere('content', 'LIKE', '%.jpg%')
                          ->orWhere('content', 'LIKE', '%.jpeg%')
                          ->orWhere('content', 'LIKE', '%.gif%')
                          ->orWhere('content', 'LIKE', '%.webp%');
                    });
                    break;
                case 'file':
                    // Would need attachment relationship in a full implementation
                    break;
                case 'embed':
                    // Would check for embed metadata in a full implementation
                    break;
            }
        }

        // Date filters
        if (!empty($beforeFilter)) {
            $messagesQuery->whereDate('created_at', '<', $beforeFilter);
        }

        if (!empty($afterFilter)) {
            $messagesQuery->whereDate('created_at', '>', $afterFilter);
        }

        // Order by most recent first
        $messagesQuery->orderBy('created_at', 'desc');

        // Get total count
        $total = $messagesQuery->count();

        // Paginate results
        $messages = $messagesQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format results
        $results = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at->toIso8601String(),
                'is_edited' => $message->is_edited,
                'author' => [
                    'id' => $message->user->id,
                    'username' => $message->user->username,
                    'display_name' => $message->user->display_name,
                    'avatar_url' => $message->user->profile->avatar_url ?? asset('images/default-avatar.png')
                ],
                'channel' => [
                    'id' => $message->channel->id,
                    'name' => $message->channel->name,
                    'type' => $message->channel->type
                ]
            ];
        });

        return response()->json([
            'results' => $results,
            'total' => $total,
            'page' => (int) $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total
        ]);
    }

    /**
     * Pin a message
     *
     * @param Request $request
     * @param Message $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function pinMessage(Request $request, Message $message)
    {
        $user = Auth::user();
        $server = $message->channel->server;

        // Check if user is admin
        if (!$user->isServerAdmin($server->id)) {
            return response()->json(['error' => 'You do not have permission to pin messages'], 403);
        }

        $message->is_pinned = true;
        $message->pinned_at = now();
        $message->pinned_by = $user->id;
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message pinned successfully'
        ]);
    }

    /**
     * Unpin a message
     *
     * @param Request $request
     * @param Message $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpinMessage(Request $request, Message $message)
    {
        $user = Auth::user();
        $server = $message->channel->server;

        // Check if user is admin
        if (!$user->isServerAdmin($server->id)) {
            return response()->json(['error' => 'You do not have permission to unpin messages'], 403);
        }

        $message->is_pinned = false;
        $message->pinned_at = null;
        $message->pinned_by = null;
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Message unpinned successfully'
        ]);
    }
}
