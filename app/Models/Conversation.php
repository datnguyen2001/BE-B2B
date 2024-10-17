<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user1_id', 'user2_id'];

    public function lastMessage()
    {
        return $this->hasOne(Message::class)
            ->select('messages.id', 'messages.content', 'messages.created_at', 'messages.conversation_id')
            ->latestOfMany();
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }
}
