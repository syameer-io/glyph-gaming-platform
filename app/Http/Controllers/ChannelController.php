<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Channel;
use App\Models\Message;
use App\Events\MessagePosted;
use App\Events\MessageEdited;
use App\Events\MessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChannelController extends Controller
{
    private function ensureEditedAtColumn()
    {
        if (!Schema::hasColumn('messages', 'edited_at')) {
            Schema::table('messages', function ($table) {
                $table->timestamp('edited_at')->nullable()->after('is_edited');
            });
        }
    }

    private function getUserMembership(Server $server, $user)
    {
        return $server->members()->where('user_id', $user->id)->first();
    }

    private function checkMembershipAndAccess(Server $server, $user, $checkMuted = false)
    {
        $membership = $this->getUserMembership($server, $user);
        
        if (!$membership) {
            return ['error' => 'Not a member of this server', 'membership' => null];
        }

        if ($membership->pivot->is_banned) {
            return ['error' => 'You are banned from this server', 'membership' => null];
        }

        if ($checkMuted && $membership->pivot->is_muted) {
            return ['error' => 'You are muted in this server', 'membership' => null];
        }

        return ['error' => null, 'membership' => $membership];
    }

    public function show(Server $server, Channel $channel)
    {
        $user = Auth::user();
        
        // Check membership and access in one query
        $accessCheck = $this->checkMembershipAndAccess($server, $user);
        if ($accessCheck['error']) {
            return redirect()->route('dashboard')->with('error', $accessCheck['error']);
        }

        // Check if channel belongs to server
        if ($channel->server_id !== $server->id) {
            return redirect()->route('server.show', $server);
        }

        $server->load(['channels', 'members.profile', 'members.roles' => function ($query) use ($server) {
            $query->where('user_roles.server_id', $server->id);
        }]);

        $messages = $channel->messages()
            ->with('user.profile')
            ->latest()
            ->take(50)
            ->get()
            ->reverse();

        return view('channels.show', compact('server', 'channel', 'messages'));
    }

    public function sendMessage(Request $request, Server $server, Channel $channel)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        // Check membership and access including mute status
        $accessCheck = $this->checkMembershipAndAccess($server, $user, true);
        if ($accessCheck['error']) {
            return response()->json(['error' => $accessCheck['error']], 403);
        }

        $message = Message::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        $message->load('user.profile');

        // Broadcast the message to other users in the channel
        broadcast(new MessagePosted($message, $channel))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'is_edited' => $message->is_edited,
                'edited_at' => $message->edited_at ? $message->edited_at->toISOString() : null,
                'user' => [
                    'id' => $message->user->id,
                    'display_name' => $message->user->display_name,
                    'avatar_url' => $message->user->profile->avatar_url,
                ],
            ],
        ]);
    }

    public function editMessage(Request $request, Server $server, Channel $channel, Message $message)
    {
        try {
            // Ensure the edited_at column exists
            $this->ensureEditedAtColumn();
            
            \Log::info('Edit message request', [
                'server_id' => $server->id,
                'channel_id' => $channel->id,
                'message_id' => $message->id,
                'content' => $request->content,
                'user_id' => Auth::id()
            ]);
            
            $request->validate([
                'content' => 'required|string|max:2000',
            ]);

            $user = Auth::user();

        // Check if user can edit this message
        if (!$message->canEdit($user->id)) {
            return response()->json(['error' => 'You can only edit your own messages'], 403);
        }

        // Check if message belongs to this channel
        if ($message->channel_id !== $channel->id) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        // Check if user is still a member and not banned/muted
        $accessCheck = $this->checkMembershipAndAccess($server, $user, true);
        if ($accessCheck['error']) {
            return response()->json(['error' => 'You cannot edit messages'], 403);
        }

        $message->content = $request->content;
        $message->is_edited = true;
        $message->edited_at = now();
        $message->save();

        $message->load('user.profile');

        // Broadcast the message edit to all users in the channel
        broadcast(new MessageEdited($message, $channel));

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'is_edited' => $message->is_edited,
                'edited_at' => $message->edited_at->toISOString(),
                'user' => [
                    'id' => $message->user->id,
                    'display_name' => $message->user->display_name,
                    'avatar_url' => $message->user->profile->avatar_url,
                ],
            ],
        ]);
        
        } catch (\Exception $e) {
            \Log::error('Error editing message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to edit message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteMessage(Request $request, Server $server, Channel $channel, Message $message)
    {
        $user = Auth::user();

        // Check if user can delete this message
        if (!$message->canDelete($user->id)) {
            return response()->json(['error' => 'You can only delete your own messages'], 403);
        }

        // Check if message belongs to this channel
        if ($message->channel_id !== $channel->id) {
            return response()->json(['error' => 'Message not found'], 404);
        }

        // Check if user is still a member and not banned (don't check muted for delete)
        $accessCheck = $this->checkMembershipAndAccess($server, $user, false);
        if ($accessCheck['error']) {
            return response()->json(['error' => 'You cannot delete messages'], 403);
        }

        $messageId = $message->id;
        
        // Broadcast the message deletion to all users in the channel
        broadcast(new MessageDeleted($messageId, $channel));

        $message->delete();

        return response()->json([
            'success' => true,
            'message_id' => $messageId,
        ]);
    }
}