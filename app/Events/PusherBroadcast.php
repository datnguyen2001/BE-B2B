<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PusherBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public int $sender_id;
    public int $receiver_id;

    /**
     * Create a new event instance.
     */
    public function __construct(string $message, int $sender_id, int $receiver_id)
    {
        $this->message = $message;
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = 'private-chat.' . min($this->sender_id, $this->receiver_id) . '-' . max($this->sender_id, $this->receiver_id);

        return [new Channel($channelName)];
    }

    public function broadcastAs(): string
    {
        return 'chat';
    }
}
