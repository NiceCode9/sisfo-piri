<?php

namespace App\Jobs;

use App\Models\NotifikasiLog;
use App\Models\ProfilSekolah;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @return array<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(protected int $logId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = NotifikasiLog::find($this->logId);

        if (! $log || $log->status !== 'antri') {
            return;
        }

        $url = config('services.whatsapp.url');

        if (! $url) {
            $log->update(['respons' => 'Gateway belum dikonfigurasi (mode log-only).']);

            return;
        }

        try {
            $response = Http::timeout(15)->post($url, [
                'tujuan' => $log->tujuan,
                'pesan' => $log->pesan,
            ]);

            $log->update([
                'status' => $response->successful() ? 'terkirim' : 'gagal',
                'respons' => substr((string) $response->body(), 0, 1000),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Gateway menjawab '.$response->status());
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'gagal', 'respons' => substr($e->getMessage(), 0, 1000)]);

            throw $e;
        }
    }

    public static function namaSekolah(): string
    {
        return ProfilSekolah::aktif()?->nama_sekolah ?? 'Sekolah';
    }

    public static function pesanAlpa(string $nama, string $kelas, string $tanggal): string
    {
        return 'INFO '.static::namaSekolah().": {$nama} ({$kelas}) tercatat ALPA pada {$tanggal}. Mohon konfirmasi ke wali kelas.";
    }

    public static function pesanBelumHadir(string $nama, string $kelas, string $tanggal): string
    {
        return 'INFO '.static::namaSekolah().": {$nama} ({$kelas}) belum tercatat hadir pada {$tanggal}. Mohon konfirmasi ke wali kelas.";
    }
}
