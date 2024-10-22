<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use Tymon\JWTAuth\Facades\JWTAuth;

class MessageController extends Controller
{
    // Fetch messages between two users
    public function index($userId, $receiverId)
    {
        $messages = Message::where(function ($query) use ($userId, $receiverId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($userId, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $userId);
        })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'message' => $messages,
            'status' => true,
        ]);
    }

    // Store a new message
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
        ]);
        $content = $request->input('content');
        $message = Message::create([
            'content' => $content,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'conversation_id' => $request->conversation_id
        ]);

        return response()->json([
            'message' => $message,
            'status' => true,
        ]);
    }

    public function getAllConversations()
    {
        $user = JWTAuth::user();
        $userId = $user->id;

        $conversations = Conversation::with(['lastMessage' => function ($query) {
            $query->select('messages.id', 'messages.content', 'messages.created_at', 'messages.conversation_id');
        }, 'user1' => function ($query) {
            $query->select('id', 'name', 'avatar');
        }, 'user2' => function ($query) {
            $query->select('id', 'name', 'avatar');
        }])
            ->where('conversations.user1_id', $userId)
            ->orWhere('conversations.user2_id', $userId)
            ->get()
            ->sortByDesc(function ($conversation) {
                return $conversation->lastMessage ? $conversation->lastMessage->created_at : null;
            });

        $conversations = $conversations->map(function ($conversation) use ($userId) {
            $lastMessage = $conversation->lastMessage ? [
                'content' => $conversation->lastMessage->content,
                'created_at' => $conversation->lastMessage->created_at,
                'conversation_id' => $conversation->lastMessage->conversation_id,
            ] : null;

            // Determine the receiver ID, name, and avatar
            $isUser1 = $conversation->user1_id === $userId;
            $receiver = $isUser1 ? $conversation->user2 : $conversation->user1;

            $receiverId = $receiver ? $receiver->id : null;
            $receiverName = $receiver ? $receiver->name : null;
            $receiverAvatar = $receiver ? $receiver->avatar : null;

            return [
                'id' => $conversation->id,
                'user1_id' => $userId, // Always the logged-in user
                'user2_id' => $receiverId, // The other user in the conversation
                'last_message' => $lastMessage,
                'receiver_name' => $receiverName,
                'receiver_avatar' => $receiverAvatar, // Include the receiver's avatar
            ];
        });

        return response()->json(['data' => $conversations->values(), 'status' => true]);
    }
}
