<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifyUser implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiver_id;
    public $sender_avatar;
    public $sender_name;
    public $type;
    public $id;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $receiver_id,$sender_avatar, $sender_name, $type,$id)
    {
        $this->message = $message;
        $this->receiver_id = $receiver_id;
        $this->sender_avatar = $sender_avatar;
        $this->sender_name = $sender_name;
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('notifications.'.$this->receiver_id);
    }

    public function broadcastAs()
    {
        return 'user-notification';
    }
}
