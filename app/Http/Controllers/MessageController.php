<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\MessageSent;

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

        return response()->json($messages);
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
        ]);

        // Broadcast the new message to the receiver
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
