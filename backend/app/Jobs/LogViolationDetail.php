<?php

namespace App\Jobs;

use App\Models\ExamViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogViolationDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Pelanggaran dihitung di luar antrean (ViolationController menaikkan
     * violation_count secara sinkron), tetapi rinciannya lewat job ini. Kalau
     * job hilang, angka naik tanpa bukti yang bisa diekspor lewat
     * violations.csv, sehingga keduanya tidak bisa dicocokkan. Job yang gagal
     * total wajib meninggalkan jejak di log.
     */
    public int $tries = 5;

    public int $timeout = 10;

    /**
     * @var list<int>
     */
    public array $backoff = [3, 10, 30, 60];

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

    public function failed(?\Throwable $exception): void
    {
        Log::error('LogViolationDetail gagal-total, rincian pelanggaran tidak tercatat.', [
            'exam_session_id' => $this->sessionId,
            'type' => $this->type,
            'attempts' => $this->tries,
            'error' => $exception?->getMessage(),
        ]);
    }
}
