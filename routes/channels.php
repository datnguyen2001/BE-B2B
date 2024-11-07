<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

Broadcast::channel('private-chat.{sender_id}-{receiver_id}', function ($user, $sender_id, $receiver_id) {
    if (in_array($user->id, [$sender_id, $receiver_id])) {
        return new PrivateChannel('private-chat.' . min($sender_id, $receiver_id) . '-' . max($sender_id, $receiver_id));
    }
    return false;
});

Broadcast::channel('notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id; // Chỉ cho phép người dùng có ID khớp truy cập kênh
});

Broadcast::channel('messenger.{id}', function ($user, $id) {
    $conversation = \App\Models\Conversation::find($id);

    return $conversation && ($conversation->user1_id === $user->id || $conversation->user2_id === $user->id);
});
