<?php

namespace App\Services;

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Absensi;
use App\Models\NotifikasiLog;
use App\Models\Rombel;
use App\Models\Siswa;

class NotifikasiAlpa
{
    /**
     * Antrekan notifikasi WA untuk siswa alpa pada satu rombel+tanggal.
     *
     * @param  array<int>  $siswaIds
     */
    public static function antrekan(int $rombelId, string $tanggal, array $siswaIds): void
    {
        if ($siswaIds === []) {
            return;
        }

        $rombel = Rombel::with('kelas')->find($rombelId);

        $siswas = Siswa::with(['user', 'waliMurids'])
            ->whereIn('id', $siswaIds)
            ->get();

        foreach ($siswas as $siswa) {
            foreach ($siswa->waliMurids->pluck('no_whatsapp')->filter()->unique() as $nomor) {
                $kunci = hash('sha256', "whatsapp|alpa|{$siswa->id}|{$rombelId}|{$tanggal}|{$nomor}");

                $log = NotifikasiLog::firstOrCreate(
                    ['kunci' => $kunci],
                    [
                        'tipe' => 'whatsapp',
                        'tujuan' => $nomor,
                        'pesan' => KirimNotifikasiWhatsapp::pesanAlpa(
                            $siswa->user?->name ?? '-',
                            $rombel?->kelas?->nama_kelas ?? '-',
                            $tanggal
                        ),
                    ],
                );

                if ($log->wasRecentlyCreated) {
                    KirimNotifikasiWhatsapp::dispatch($log->id);
                }
            }
        }
    }

    /**
     * ID siswa berstatus alpa pada satu rombel dan tanggal.
     *
     * @return array<int>
     */
    public static function siswaAlpa(int $rombelId, string $tanggal): array
    {
        return Absensi::where('rombel_id', $rombelId)
            ->whereDate('tanggal', $tanggal)
            ->where('status', 'alpa')
            ->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
