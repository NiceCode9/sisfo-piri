<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Cbt\ExamSessionController;
use App\Models\ExamSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Menutup sesi ujian yang ditinggalkan siswa.
 *
 * Tanpa perintah ini, `status` hanya berubah saat siswa sendiri memanggil
 * endpoint (atau guru memaksa lewat monitoring). Siswa yang menutup laptop di
 * tengah ujian menyisakan baris `ongoing` selamanya: monitoring menampilkan
 * `remaining_seconds` negatif, ringkasan "sedang ujian" tidak pernah turun, dan
 * `GET /exam/active` mengembalikan sesi yang sudah tidak aktif sehingga frontend
 * membuka ujian yang sudah lewat.
 */
#[Signature('cbt:expire-sessions {--dry-run : Tampilkan sesi yang akan ditutup tanpa menulis}')]
#[Description('Finalisasi sesi CBT yang sudah melewati expected_end_at')]
class ExpireExamSessions extends Command
{
    public function handle(ExamSessionController $controller): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Batas toleransi 5 detik sama dengan yang dipakai
        // ExamSessionController::resolveOngoingSession, supaya tidak ada dua
        // proses yang berdebat mengenai sesi yang sama.
        $batas = Carbon::now()->subSeconds(5);

        $kandidat = ExamSession::with('user')
            ->where('status', 'ongoing')
            ->where('expected_end_at', '<', $batas)
            ->orderBy('expected_end_at')
            ->limit(500)
            ->get();

        if ($kandidat->isEmpty()) {
            $this->info('Tidak ada sesi yang perlu ditutup.');

            return self::SUCCESS;
        }

        $ditutup = 0;

        foreach ($kandidat as $sesi) {
            if ($dryRun) {
                $this->line(sprintf(
                    '  [dry-run] #%d %s — %s',
                    $sesi->id,
                    $sesi->user?->name ?? '-',
                    $sesi->expected_end_at?->toDateTimeString() ?? '-',
                ));
                $ditutup++;

                continue;
            }

            $controller->finalizeSession($sesi, 'time_up', 'expired');
            $ditutup++;
        }

        $this->info("{$ditutup} sesi ujian ditutup".($dryRun ? ' (dry-run, tidak ada yang ditulis)' : '').'.');

        return self::SUCCESS;
    }
}
