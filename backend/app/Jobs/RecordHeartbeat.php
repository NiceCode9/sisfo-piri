<?php

namespace App\Jobs;

use App\Models\ExamSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $sessionId) {}

    public function handle(): void
    {
        ExamSession::where('id', $this->sessionId)->update(['last_heartbeat_at' => now()]);
    }
}
