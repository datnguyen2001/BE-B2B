<?php

namespace App\Http\Controllers;

use App\Events\NotifyMessenger;
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

        broadcast(new NotifyMessenger($message->content,$message->conversation_id))->toOthers();

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


            $unreadMessageCount = Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();
            return [
                'id' => $conversation->id,
                'user1_id' => $userId,
                'user2_id' => $receiverId, // The other user in the conversation
                'last_message' => $lastMessage,
                'receiver_name' => $receiverName,
                'receiver_avatar' => $receiverAvatar, // Include the receiver's avatar
                'unread_message_count' => $unreadMessageCount,
            ];
        });

        return response()->json(['data' => $conversations->values(), 'status' => true]);
    }

    public function createConversations(Request $request)
    {
        $user1_id = $request->get('user1_id');
        $user2_id = $request->get('user2_id');
        $data = Conversation::where(function ($query) use ($user1_id, $user2_id) {
            $query->where('user1_id', $user1_id)
                ->where('user2_id', $user2_id);
        })->orWhere(function ($query) use ($user1_id, $user2_id) {
            $query->where('user1_id', $user2_id)
                ->where('user2_id', $user1_id);
        })->first();
        if ($data){
            return response()->json(['status' => true,'data'=>$data, 'message' => 'Cuộc hội thoại đã tồn tại.']);
        }else{
            $conversations = new Conversation();
            $conversations->user1_id = $user1_id;
            $conversations->user2_id = $user2_id;
            $conversations->save();

            return response()->json(['status' => true,'data'=>$conversations, 'message' => 'Tạo cuộc hội thoại thành công.']);
        }

    }

    public function markAsRead($userId, $conversationId)
    {
        Message::where('receiver_id', $userId)
            ->where('conversation_id', $conversationId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['status' => true, 'message' => 'Messages marked as read.']);
    }
}
