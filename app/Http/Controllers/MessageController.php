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

        // Broadcast the new message to the receiver
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
            'status' => true,
        ]);
    }

    public function getAllConversations()
    {
        $user = JWTAuth::user();

        $conversations = Conversation::with(['lastMessage' => function ($query) {
            $query->select('id', 'content', 'created_at', 'conversation_id');
        }, 'receiver' => function ($query) {
            $query->select('id', 'name');
        }])
            ->where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->get();

        $conversations = $conversations->map(function ($conversation) {
            $lastMessage = $conversation->lastMessage ? [
                'content' => $conversation->lastMessage->content,
                'created_at' => $conversation->lastMessage->created_at,
                'conversation_id' => $conversation->lastMessage->conversation_id,
            ] : null;

            // Set the receiver name based on user2
            $receiverName = $conversation->receiver ? $conversation->receiver->name : null;

            return [
                'id' => $conversation->id,
                'user1_id' => $conversation->user1_id,
                'user2_id' => $conversation->user2_id,
                'last_message' => $lastMessage,
                'receiver_name' => $receiverName,
            ];
        });

        return response()->json(['data' => $conversations, 'status' => true]);
    }
}
