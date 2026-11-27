<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $conversationSummary
     * @param  array<int>  $recipientIds
     */
    public function __construct(
        public int $conversationId,
        public array $message,
        public array $conversationSummary,
        public array $recipientIds = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PresenceChannel('chat.conversation.' . $this->conversationId),
        ];

        foreach ($this->recipientIds as $recipientId) {
            $channels[] = new PrivateChannel('chat.user.' . $recipientId);
            $channels[] = new PrivateChannel('users.' . $recipientId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message' => $this->message,
            'conversation' => $this->conversationSummary,
        ];
    }
}
