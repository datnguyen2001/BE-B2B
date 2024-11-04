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
    public $avatar;
    public $sender_name;
    public $type;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $receiver_id,$avatar, $sender_name, $type)
    {
        $this->message = $message;
        $this->receiver_id = $receiver_id;
        $this->avatar = $avatar;
        $this->sender_name = $sender_name;
        $this->type = $type;
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
