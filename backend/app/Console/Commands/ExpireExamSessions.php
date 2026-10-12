<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Cbt\ExamSessionController;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Support\PaketSoal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
    /** Paket import yang belum di-submit dibuang setelah 24 jam. */
    public const JAM_IMPORT = 24;

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

        // Sapu paket import DI AWAL. Kalau diletakkan setelah pengembalian dini di
        // bawah, paket import tidak akan pernah dibuang pada hari tanpa sesi
        // kadaluarsa — justru saat paling tidak ada aktivitas.
        $paket = $this->sapuPaketImport(self::JAM_IMPORT, $dryRun);

        if ($paket > 0) {
            $this->info("{$paket} paket import lama dibuang".($dryRun ? ' (dry-run, tidak ada yang dihapus)' : '').'.');
        }

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

    /**
     * Paket import yang tidak pernah di-submit — guru menutup browser atau
     * abandon di tengah review — dibuang setelah 24 jam.
     *
     * Import sengaja stateless: tidak ada baris database yang menandai paket itu
     * sudah selesai, jadi satu-satunya penandanya adalah usia foldernya.
     *
     * Penting: hanya folder yang sudah TUBAH lebih dari 24 jam. Tanpa pemeriksaan
     * usia, paket yang sedang direview guru ikut terhapus.
     */
    private function sapuPaketImport(int $jam, bool $dryRun): int
    {
        $disk = Storage::disk(ExamQuestion::DISK);
        $batas = now()->subHours($jam)->getTimestamp();
        $terhapus = 0;

        foreach ($disk->directories(ExamQuestion::IMPORT_DIR) as $direktori) {
            // `directories()` mengembalikan path relatif LENGKAP
            // ("cbt/import/<token>"), bukan hanya token-nya.
            $token = basename(rtrim($direktori, '/'));
            $sRelative = $direktori.'/'.PaketSoal::MANIFEST;

            // Paket tanpa manifest berarti ekstraksinya belum selesai — biarkan
            // siklus berikutnya yang membersihkan.
            if (! $disk->exists($sRelative)) {
                continue;
            }

            if ($disk->lastModified($sRelative) > $batas) {
                continue;
            }

            if (! $dryRun) {
                $disk->deleteDirectory($direktori);
            }

            $terhapus++;
        }

        return $terhapus;
    }
}
