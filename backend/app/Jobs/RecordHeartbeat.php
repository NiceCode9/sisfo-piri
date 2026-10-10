<?php

namespace App\Jobs;

use App\Models\ExamSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecordHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jejak heartbeat adalah satu-satunya bukti bahwa siswa masih menyalakan
     * tab ujiannya; tanpa retry, satu kedipan jaringan membuat monitoring
     * sempat menandai siswa offline.
     */
    public int $tries = 3;

    public int $timeout = 10;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15];

    public function __construct(public int $sessionId) {}

    public function handle(): void
    {
        ExamSession::where('id', $this->sessionId)->update(['last_heartbeat_at' => now()]);
    }

    /**
     * Kalau job ini hilang total, indikator "online" di monitoring diam-diam
     * menyesatkan guru karena tidak ada yang gagal keras. Log eksplisit membuat
     * itu terlihat di monitoring log.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::warning('RecordHeartbeat gagal-total, jejak heartbeat sesi hilang.', [
            'exam_session_id' => $this->sessionId,
            'attempts' => $this->tries,
            'error' => $exception?->getMessage(),
        ]);
    }
}
