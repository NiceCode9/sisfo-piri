<?php

namespace App\Console\Commands;

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Absensi;
use App\Models\NotifikasiLog;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('absensi:cek-belum-hadir {--tanggal= : Tanggal yang diperiksa (Y-m-d), default hari ini}')]
#[Description('Kirim pengingat untuk siswa yang belum absen (self-gating via pengaturan jam)')]
class CekBelumHadir extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $manual = (bool) $this->option('tanggal');
        $tanggal = $this->option('tanggal') ?: now()->toDateString();

        // Mode terjadwal: hanya jalan pada jam yang diatur admin (±5 menit)
        // dan sekali sehari. Mode manual (--tanggal) selalu jalan.
        if (! $manual && ! $this->bolehJalanTerjadwal($tanggal)) {
            $this->line('Belum waktunya (diatur: '.Pengaturan::nilai('jam_cek_belum_hadir', '08:00').').');

            return self::SUCCESS;
        }

        $tahunAktif = TahunAjaran::aktif()->first();

        if (! $tahunAktif) {
            $this->fail('Tahun ajaran aktif belum diatur.');

            return self::FAILURE;
        }

        $sudahAda = Absensi::where('tanggal', $tanggal)->pluck('siswa_id')->all();
        $terkirim = 0;

        $rombels = Rombel::with('kelas')->where('tahun_ajaran_id', $tahunAktif->id)->get();

        foreach ($rombels as $rombel) {
            $siswas = Siswa::with(['user', 'waliMurids'])
                ->whereIn('id', $rombel->anggotaIds())
                ->whereNotIn('id', $sudahAda)
                ->where('is_aktif', true)
                ->get();

            foreach ($siswas as $siswa) {
                foreach ($siswa->waliMurids->pluck('no_whatsapp')->filter()->unique() as $nomor) {
                    $log = NotifikasiLog::create([
                        'tipe' => 'whatsapp',
                        'tujuan' => $nomor,
                        'pesan' => KirimNotifikasiWhatsapp::pesanBelumHadir(
                            $siswa->user?->name ?? '-',
                            $rombel->kelas->nama_kelas ?? '-',
                            $tanggal
                        ),
                    ]);

                    KirimNotifikasiWhatsapp::dispatch($log->id);
                    $terkirim++;
                }
            }
        }

        if (! $manual) {
            Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => $tanggal]);
        }

        $this->info("{$terkirim} pengingat belum-hadir dibuat untuk {$tanggal}.");

        return self::SUCCESS;
    }

    protected function bolehJalanTerjadwal(string $tanggal): bool
    {
        if (Pengaturan::nilai('cek_belum_hadir_terakhir') === $tanggal) {
            return false;
        }

        $jamTarget = Pengaturan::nilai('jam_cek_belum_hadir', '08:00');
        $sekarang = now()->format('H:i');

        return abs(strtotime($sekarang) - strtotime($jamTarget)) <= 300;
    }
}
