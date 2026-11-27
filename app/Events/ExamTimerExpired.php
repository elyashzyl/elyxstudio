<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamTimerExpired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $examId,
        public int $attemptId,
        public string $message = 'Exam time has expired.'
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam.attempt.' . $this->attemptId),
            new PrivateChannel('exam.' . $this->examId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'exam.expired';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'exam_id' => $this->examId,
            'attempt_id' => $this->attemptId,
            'message' => $this->message,
        ];
    }
}
