<?php

namespace App\Actions\Rombel;

use App\Models\Pengampu;
use App\Models\Rombel;
use Illuminate\Support\Facades\DB;

/**
 * Bentuk rombel tahun tujuan beserta wali dan penugasannya dari tahun sumber.
 *
 * Dipisah dari controller supaya menu "Salin Tahun" dan wizard "Tahun Ajaran
 * Baru" memakai satu implementasi. Logikanya tidak pernah diduplikasi — saat
 * dipindahkan ke sini, `RombelController::prosesSalin()` berubah menjadi
 * pemanggil tipis.
 *
 * Idempoten: rombel dan penugasan yang sudah ada di tahun tujuan dilewati dan
 * dilaporkan, bukan ditulis ulang. Kenaikan kelas sering dijalankan berkali-kali
 * saat tahun ajaran baru disiapkan, jadi ini bukan detail kecil.
 *
 * Yang TIDAK ikut: anggota siswa. Keanggotaan rombel diturunkan dari
 * `RiwayatKelas` dan move dicatat oleh kenaikan kelas atau penerimaan PPDB —
 * bukan menyalin, karena memindahkan siswa adalah keputusan sendiri.
 */
class SalinRombelAction
{
    /**
     * @return array{rombelDisalin:int, rombelDilewati:int, tugasDisalin:int, tugasDilewati:int, rombel:int, penugasan:int}
     */
    public function jalankan(int $tahunSumberId, int $tahunTujuanId): array
    {
        if ($tahunSumberId === $tahunTujuanId) {
            throw new \InvalidArgumentException('Tahun sumber dan tujuan tidak boleh sama.');
        }

        return DB::transaction(function () use ($tahunSumberId, $tahunTujuanId) {
            $hasil = [
                'rombelDisalin' => 0,
                'rombelDilewati' => 0,
                'tugasDisalin' => 0,
                'tugasDilewati' => 0,
                'rombel' => 0,
                'penugasan' => 0,
            ];

            $sumber = Rombel::with('pengampus')->where('tahun_ajaran_id', $tahunSumberId)->get();

            foreach ($sumber as $r) {
                $baru = Rombel::firstOrCreate(
                    ['kelas_id' => $r->kelas_id, 'tahun_ajaran_id' => $tahunTujuanId],
                    ['wali_guru_id' => $r->wali_guru_id]
                );

                $baru->wasRecentlyCreated ? $hasil['rombelDisalin']++ : $hasil['rombelDilewati']++;

                foreach ($r->pengampus as $p) {
                    $penugasan = Pengampu::firstOrCreate(
                        ['mata_pelajaran_id' => $p->mata_pelajaran_id, 'rombel_id' => $baru->id],
                        ['guru_id' => $p->guru_id]
                    );

                    $penugasan->wasRecentlyCreated ? $hasil['tugasDisalin']++ : $hasil['tugasDilewati']++;
                }
            }

            $hasil['rombel'] = Rombel::where('tahun_ajaran_id', $tahunTujuanId)->count();
            $hasil['penugasan'] = Pengampu::whereIn(
                'rombel_id',
                Rombel::where('tahun_ajaran_id', $tahunTujuanId)->pluck('id')
            )->count();

            return $hasil;
        });
    }
}
