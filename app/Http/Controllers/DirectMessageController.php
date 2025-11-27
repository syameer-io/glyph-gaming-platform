<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Events\DirectMessagePosted;
use App\Events\DirectMessageEdited;
use App\Events\DirectMessageDeleted;
use App\Events\DirectMessageRead;
use App\Events\UserTypingDM;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    /**
     * Number of messages to load per page for infinite scroll.
     */
    private const MESSAGES_PER_PAGE = 50;

    /**
     * List all conversations for the authenticated user.
     * Returns a view with conversations sorted by most recent activity.
     *
     * @return View
     */
    public function index(): View
    {
        $user = Auth::user();

        // Cache key for user's conversations
        $cacheKey = 'user.' . $user->id . '.conversations';

        // Get all conversations with eager loading to prevent N+1 queries
        // Load both participants and the latest message for preview
        // Cache for 5 minutes to reduce database queries
        $conversations = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return Conversation::forUser($user->id)
                ->with([
                    'userOne.profile',
                    'userTwo.profile',
                    'latestMessage.sender',
                ])
                ->get();
        });

        // Add computed properties (not cached as they may change frequently)
        $conversations = $conversations->map(function ($conversation) use ($user) {
            // Add computed properties for the view
            $conversation->other_participant = $conversation->getOtherParticipant($user);
            $conversation->unread_count = $conversation->getUnreadCountFor($user->id);
            return $conversation;
        });

        // Get total unread count for notification badge
        $totalUnreadCount = $user->getUnreadDmCount();

        return view('direct-messages.index', compact('conversations', 'totalUnreadCount'));
    }

    /**
     * Show a specific conversation with paginated messages.
     * Marks messages as read when viewing the conversation.
     *
     * @param Conversation $conversation
     * @return View|JsonResponse
     */
    public function show(Conversation $conversation): View|JsonResponse
    {
        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'You are not a participant in this conversation'], 403);
            }
            return redirect()->route('dm.index')
                ->with('error', 'You are not a participant in this conversation');
        }

        // Load conversation with participants
        $conversation->load(['userOne.profile', 'userTwo.profile']);

        // Get the other participant for display
        $otherParticipant = $conversation->getOtherParticipant($user);

        // Verify friendship still exists (users might have unfriended)
        if (!$user->canDirectMessage($otherParticipant)) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'You can no longer message this user'], 403);
            }
            return redirect()->route('dm.index')
                ->with('error', 'You can no longer message this user');
        }

        // Get messages with pagination (most recent first, then reverse for display)
        $messages = $conversation->messages()
            ->with('sender.profile')
            ->latest()
            ->take(self::MESSAGES_PER_PAGE)
            ->get()
            ->reverse()
            ->values();

        // Mark all unread messages from the other user as read
        $this->markConversationAsRead($conversation, $user->id);

        // Get unread count for badge update
        $unreadCount = $conversation->getUnreadCountFor($user->id);

        return view('direct-messages.show', compact(
            'conversation',
            'otherParticipant',
            'messages',
            'unreadCount'
        ));
    }

    /**
     * Start a new conversation with first message.
     * Creates the conversation if it doesn't exist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'content' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);

        // Authorization: Check if user can message the recipient
        if (!$user->canDirectMessage($recipient)) {
            return response()->json([
                'error' => 'You can only send direct messages to friends'
            ], 403);
        }

        // Cannot message yourself
        if ($user->id === $recipient->id) {
            return response()->json([
                'error' => 'You cannot send messages to yourself'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get or create conversation
            $conversation = Conversation::findOrCreateBetween($user->id, $recipient->id);

            // Create the message
            $message = DirectMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => $request->content,
            ]);

            // Load message relationships for response
            $message->load('sender.profile');

            // Update last_message_at timestamp
            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            // Broadcast the message to the recipient
            broadcast(new DirectMessagePosted($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => $this->formatMessageForResponse($message),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create conversation/message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'recipient_id' => $recipient->id,
            ]);

            return response()->json([
                'error' => 'Failed to send message. Please try again.'
            ], 500);
        }
    }

    /**
     * Send a message in an existing conversation.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'error' => 'You are not a participant in this conversation'
            ], 403);
        }

        // Get the other participant and verify friendship still exists
        $otherParticipant = $conversation->getOtherParticipant($user);
        if (!$user->canDirectMessage($otherParticipant)) {
            return response()->json([
                'error' => 'You can no longer message this user'
            ], 403);
        }

        try {
            // Create the message
            $message = DirectMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => $request->content,
            ]);

            // Load message relationships for response
            $message->load('sender.profile');

            // Update last_message_at timestamp
            $conversation->update(['last_message_at' => now()]);

            // Broadcast the message to the recipient
            broadcast(new DirectMessagePosted($message, $conversation))->toOthers();

            // Invalidate cache for both participants so conversation list updates
            Cache::forget('user.' . $conversation->user_one_id . '.conversations');
            Cache::forget('user.' . $conversation->user_two_id . '.conversations');

            return response()->json([
                'success' => true,
                'message' => $this->formatMessageForResponse($message),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send direct message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);

            return response()->json([
                'error' => 'Failed to send message. Please try again.'
            ], 500);
        }
    }

    /**
     * Edit own message.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @param DirectMessage $message
     * @return JsonResponse
     */
    public function editMessage(Request $request, Conversation $conversation, DirectMessage $message): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'error' => 'You are not a participant in this conversation'
            ], 403);
        }

        // Check if message belongs to this conversation
        if ($message->conversation_id !== $conversation->id) {
            return response()->json([
                'error' => 'Message not found in this conversation'
            ], 404);
        }

        // Authorization: Check if user can edit this message (only own messages)
        if (!$message->canEdit($user->id)) {
            return response()->json([
                'error' => 'You can only edit your own messages'
            ], 403);
        }

        try {
            // Update the message content and mark as edited
            $message->update([
                'content' => $request->content,
            ]);
            $message->markAsEdited();

            // Reload relationships for response
            $message->load('sender.profile');

            // Broadcast the message edit
            broadcast(new DirectMessageEdited($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'message' => $this->formatMessageForResponse($message),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to edit direct message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'message_id' => $message->id,
            ]);

            return response()->json([
                'error' => 'Failed to edit message. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete own message.
     *
     * @param Conversation $conversation
     * @param DirectMessage $message
     * @return JsonResponse
     */
    public function deleteMessage(Conversation $conversation, DirectMessage $message): JsonResponse
    {
        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'error' => 'You are not a participant in this conversation'
            ], 403);
        }

        // Check if message belongs to this conversation
        if ($message->conversation_id !== $conversation->id) {
            return response()->json([
                'error' => 'Message not found in this conversation'
            ], 404);
        }

        // Authorization: Check if user can delete this message (only own messages)
        if (!$message->canDelete($user->id)) {
            return response()->json([
                'error' => 'You can only delete your own messages'
            ], 403);
        }

        try {
            $messageId = $message->id;

            // Broadcast the message deletion before deleting
            broadcast(new DirectMessageDeleted($messageId, $conversation))->toOthers();

            $message->delete();

            // Invalidate cache for both participants so conversation list updates
            Cache::forget('user.' . $conversation->user_one_id . '.conversations');
            Cache::forget('user.' . $conversation->user_two_id . '.conversations');

            return response()->json([
                'success' => true,
                'message_id' => $messageId,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete direct message', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'message_id' => $message->id,
            ]);

            return response()->json([
                'error' => 'Failed to delete message. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark messages in a conversation as read.
     *
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'error' => 'You are not a participant in this conversation'
            ], 403);
        }

        try {
            $updatedCount = $this->markConversationAsRead($conversation, $user->id);

            // Broadcast read receipt if messages were marked as read
            if ($updatedCount > 0) {
                broadcast(new DirectMessageRead($conversation, $user->id))->toOthers();
            }

            return response()->json([
                'success' => true,
                'messages_marked' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to mark messages as read', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);

            return response()->json([
                'error' => 'Failed to mark messages as read'
            ], 500);
        }
    }

    /**
     * Broadcast typing indicator.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $isTyping = $request->boolean('is_typing', true);

        broadcast(new UserTypingDM($conversation, $user, $isTyping))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Load more messages for infinite scroll pagination.
     * Returns messages older than the provided cursor (message ID).
     *
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function loadMoreMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'before_id' => 'required|integer|exists:direct_messages,id',
        ]);

        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json([
                'error' => 'You are not a participant in this conversation'
            ], 403);
        }

        try {
            // Get messages older than the cursor
            $messages = $conversation->messages()
                ->with('sender.profile')
                ->where('id', '<', $request->before_id)
                ->latest()
                ->take(self::MESSAGES_PER_PAGE)
                ->get()
                ->reverse()
                ->values();

            // Check if there are more messages to load
            $hasMore = $conversation->messages()
                ->where('id', '<', $messages->first()?->id ?? 0)
                ->exists();

            return response()->json([
                'success' => true,
                'messages' => $messages->map(fn($msg) => $this->formatMessageForResponse($msg)),
                'has_more' => $hasMore,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to load more messages', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);

            return response()->json([
                'error' => 'Failed to load messages'
            ], 500);
        }
    }

    /**
     * Get or create a conversation with a specific user.
     * Redirects to the conversation view for web requests.
     * Uses route model binding for cleaner URLs.
     *
     * @param User $user The user to start/get conversation with
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getConversationWith(User $user)
    {
        $currentUser = Auth::user();

        // Cannot start conversation with yourself
        if ($currentUser->id === $user->id) {
            return redirect()->route('dm.index')
                ->with('error', 'You cannot start a conversation with yourself.');
        }

        // Authorization: Check if users are friends
        if (!$currentUser->canDirectMessage($user)) {
            return redirect()->route('friends.index')
                ->with('error', 'You must be friends with this user to start a conversation.');
        }

        try {
            // Get or create the conversation
            $conversation = Conversation::findOrCreateBetween($currentUser->id, $user->id);

            return redirect()->route('dm.show', $conversation);

        } catch (\Exception $e) {
            \Log::error('Failed to get/create conversation', [
                'error' => $e->getMessage(),
                'user_id' => $currentUser->id,
                'other_user_id' => $user->id,
            ]);

            return redirect()->route('dm.index')
                ->with('error', 'Failed to start conversation. Please try again.');
        }
    }

    /**
     * Search messages within a conversation.
     * Returns messages matching the search query.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function searchMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        // Authorization: Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $query = $request->get('q', '');

        // Require minimum 2 characters for search
        if (strlen($query) < 2) {
            return response()->json(['messages' => []]);
        }

        try {
            $messages = $conversation->messages()
                ->with('sender.profile')
                ->where('content', 'like', "%{$query}%")
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn($m) => $this->formatMessageForResponse($m));

            return response()->json(['messages' => $messages]);

        } catch (\Exception $e) {
            \Log::error('Failed to search messages', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'query' => $query,
            ]);

            return response()->json([
                'error' => 'Failed to search messages'
            ], 500);
        }
    }

    /**
     * Format a message for JSON response.
     * Provides consistent message structure across all endpoints.
     *
     * @param DirectMessage $message
     * @return array
     */
    private function formatMessageForResponse(DirectMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'is_edited' => (bool) $message->is_edited,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
            'is_read' => $message->isRead(),
            'created_at' => $message->created_at->toIso8601String(),
            'sender' => [
                'id' => $message->sender->id,
                'username' => $message->sender->username,
                'display_name' => $message->sender->display_name,
                'avatar_url' => $message->sender->profile?->avatar_url,
            ],
        ];
    }

    /**
     * Mark all unread messages from the other user as read.
     * Returns the count of messages marked as read.
     *
     * @param Conversation $conversation
     * @param int $userId The user who is reading the messages
     * @return int Number of messages marked as read
     */
    private function markConversationAsRead(Conversation $conversation, int $userId): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
