<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan dampak sebelum timezone aplikasi diganti.
 *
 * Aplikasi dulu menulis `created_at` dan kolom tanggal/jam runtime memakai
 * jam UTC, sementara sekolah operates di WIB (UTC+7). Setelah konfigurasi
 * diganti, baris lama akan terbaca 7 jam lebih awal: baris yang jamnya
 * 17:00-23:59 UTC sebenarnya jatuh di hari berikutnya secara lokal.
 *
 * Perintah ini sengaja hanya membaca. Angkanya dipakai sebagai dasar
 * keputusan: jalankan migrasi koreksi, atau terima data lama dan hanya perbaiki
 * ke depan.
 */
#[Signature('zona-waktu:audit {--dari=UTC : Zona waktu yang dipakai data sekarang} {--ke=Asia/Jakarta : Zona waktu tujuan}')]
#[Description('Laporkan berapa baris yang akan bergeser waktu bila timezone aplikasi diubah (hanya baca)')]
class AuditZonaWaktu extends Command
{
    /**
     * Kolom `tanggal` yang ditulis runtime lewat `now()->toDateString()`.
     *
     * Daftar ini hasil penelusuran pemakaian `now()`, bukan tebakan: kolom
     * bertanda hubung diisi lewat seeder dengan literal, jadi nilainya tidak
     * bergantung timezone dan tidak ikut bergeser.
     *
     * @var array<string, list<string>>
     */
    protected array $kolomTanggalRuntime = [
        'absensis' => ['tanggal'],
        'calon_siswas' => ['tanggal_diterima'],
        'siswas' => ['tanggal_diterima'],
        'pembayarans' => ['tanggal_pembayaran', 'tanggal_jatuh_tempo', 'tanggal_bayar'],
        'rencana_angsuran' => ['tanggal_pembayaran', 'tanggal_jatuh_tempo', 'tanggal_bayar'],
    ];

    public function handle(): int
    {
        $dari = (string) $this->option('dari');
        $ke = (string) $this->option('ke');

        $selisih = $this->selisihJam($dari, $ke);

        $this->newLine();
        $this->line('Zona waktu aplikasi sekarang : <fg=yellow>'.config('app.timezone').'</>');
        $this->line("Data diasumsikan ditulis dalam  : {$dari}");
        $this->line("Akan dibaca sebagai           : {$ke} (selisih {$selisih} jam)");
        $this->newLine();

        if ($selisih === 0) {
            $this->components->warn('Selisih nol — tidak ada baris yang bergeser.');
        }

        $jamBatas = $this->jamBatas($selisih);
        $this->line("Baris dengan jam >= <fg=yellow>{$jamBatas}</> pada kolom waktu lokal berarti sebenarnya sudah bergeser hari.");
        $this->newLine();

        $totalBergeser = 0;

        $this->components->twoColumnDetail('<fg=cyan>Kolom created_at / updated_at</>', 'baris bergeser');

        foreach ($this->tabelDenganTimestamp() as $tabel) {
            $baris = DB::table($tabel)->whereNotNull('created_at')->count();

            if ($baris === 0) {
                continue;
            }

            $geser = $this->hitungGeser($tabel, 'created_at', $jamBatas);

            $totalBergeser += $geser;

            $this->components->twoColumnDetail(
                $tabel.' ('.$baris.' baris)',
                $geser > 0 ? '<fg=red>'.$geser.'</>' : '0'
            );
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan>Kolom tanggal runtime</>', 'baris bergeser');

        foreach ($this->kolomTanggalRuntime as $tabel => $kolom) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            foreach ($kolom as $nama) {
                if (! Schema::hasColumn($tabel, $nama)) {
                    continue;
                }

                $geser = $this->hitungGeserTanggal($tabel, $nama, $jamBatas);
                $totalBergeser += $geser;

                $this->components->twoColumnDetail(
                    $tabel.'.'.$nama,
                    $geser > 0 ? '<fg=red>'.$geser.'</>' : '0'
                );
            }
        }

        $this->newLine();

        if ($totalBergeser === 0) {
            $this->components->info('Tidak ada baris yang perlu dikoreksi. Zona waktu bisa langsung diganti.');
        } else {
            $this->components->warn("Total {$totalBergeser} baris perlu dikoreksi sebelum atau sesaat setelah mengganti zona waktu.");
            $this->line('Tanyakan dulu: koreksi data lama, atau terima dan perbaiki ke depan?');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Tabel milik aplikasi ini saja yang punya kolom `created_at`.
     *
     * Server MySQL development dipakai bersama proyek lain dan listing
     * menampilkan seluruh database sebagai `nama_database.tabel`. Tanpa
     * penyaringan, audit ikut menghitung tabel `sarpras`, `sidocma`, `siakad`,
     * dan sejenisnya — yang jelas bukan milik aplikasi ini.
     *
     * @return list<string>
     */
    protected function tabelDenganTimestamp(): array
    {
        $database = $this->namaDatabase();

        return collect(Schema::getTableListing())
            ->map(fn (string $t) => $this->keNamaPanjang($t, $database))
            ->filter()
            ->filter(fn (string $t) => Schema::hasColumn($t, 'created_at'))
            ->values()
            ->all();
    }

    /**
     * Ambil nama tabel tanpa prefiks database; null bila tabel milik database
     * lain yang kebetulan terlihat dari server yang sama.
     */
    protected function keNamaPanjang(string $tabel, string $database): ?string
    {
        if (! str_contains($tabel, '.')) {
            return $tabel;
        }

        [$prefiks, $nama] = explode('.', $tabel, 2);

        return $prefiks === $database ? $nama : null;
    }

    protected function namaDatabase(): string
    {
        return (string) config('database.connections.'.config('database.default').'.database');
    }

    /**
     * Berapa baris pada kolom waktu yang jatuh di hari berikutnya secara lokal.
     */
    protected function hitungGeser(string $tabel, string $kolom, string $jamBatas): int
    {
        if (! Schema::hasColumn($tabel, $kolom)) {
            return 0;
        }

        return $this->ekspresiSql($kolom, $jamBatas, $tabel);
    }

    /**
     * Sama seperti {@see hitungGeser()} untuk kolom `DATE`.
     *
     * Kolom tanggal tidak menyimpan jam, jadi yang menentukan adalah apakah
     * ada baris pada tabel itu yang punya jam lewat batas — itulah yang membuat
     * tanggal tersebut salah hari.
     */
    protected function hitungGeserTanggal(string $tabel, string $kolom, string $jamBatas): int
    {
        if (Schema::hasColumn($tabel, 'jam_datang')) {
            return (int) DB::table($tabel)
                ->whereNotNull($kolom)
                ->whereNotNull('jam_datang')
                ->whereRaw($this->jamKolomSql('jam_datang').' >= ?', [$jamBatas])
                ->count();
        }

        return 0;
    }

    /**
     * Jumlah baris dengan jam >= batas pada kolom waktu tertentu.
     */
    protected function ekspresiSql(string $kolom, string $jamBatas, string $tabel): int
    {
        return (int) DB::table($tabel)
            ->whereNotNull($kolom)
            ->whereRaw($this->jamKolomSql($kolom).' >= ?', [$jamBatas])
            ->count();
    }

    /**
     * Ekspresi SQL untuk mengambil bagian jam, sesuai driver.
     *
     * MySQL dan SQLite tidak punya sintaks yang sama, dan perintah ini dipakai
     * manual di produksi sekaligus lokal, jadi keduanya harus jalan.
     */
    protected function jamKolomSql(string $kolom): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%H:%M:%S', {$kolom})"
            : "TIME({$kolom})";
    }

    /**
     * Jam batas tempat pergeseran +7 jam melewati tengah malam.
     */
    protected function jamBatas(int $selisihJam): string
    {
        $jam = 24 - abs($selisihJam);

        return sprintf('%02d:00:00', $jam);
    }

    /**
     * Selisih zona dalam jam, positif kalau `ke` lebih maju dari `dari`.
     */
    protected function selisihJam(string $dari, string $ke): int
    {
        $offsetDari = (new \DateTimeZone($dari))->getOffset(new \DateTimeImmutable('now'));
        $offsetKe = (new \DateTimeZone($ke))->getOffset(new \DateTimeImmutable('now'));

        return (int) round(($offsetKe - $offsetDari) / 3600);
    }
}
