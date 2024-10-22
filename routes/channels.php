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
