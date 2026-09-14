<?php

namespace App\Jobs;

use App\Models\ExamViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogViolationDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $sessionId,
        public string $type,
        public ?array $meta = null
    ) {}

    public function handle(): void
    {
        ExamViolation::create([
            'exam_session_id' => $this->sessionId,
            'type' => $this->type,
            'meta' => $this->meta,
            'occurred_at' => now(),
        ]);
    }
}
