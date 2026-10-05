<?php

namespace App\Actions\Siswa;

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

/**
 * Naikkan, tinggalkan, atau luluskan siswa dari satu tahun ajaran ke berikutnya.
 *
 * Dipisah dari controller supaya wizard "Tahun Ajaran Baru" dan menu
 * "Kenaikan Kelas" memakai satu implementasi.
 *
 * Idempoten: siswa yang sudah punya baris riwayat di tahun tujuan dilewati.
 * Wizard tahun ajaran baru bisa dijalankan berulang selama penyiapan, dan
 * hasil yang salah harus bisa diperbaiki tanpa harus membatalkan semua.
 */
class ProsesKenaikanAction
{
    /**
     * Kenaikan hanya bermakna kalau rombel tahun tujuan sudah ada. Tanpa
     * pemeriksaan ini wizard bisa dijalankan lebih dulu, dan siswa akan
     * menunjuk `kelas_id` yang tidak punya rombel — sehingga lenyap dari rekap
     * absensi, nilai tugas, dan nilai ujian tanpa ada yang memberitahu.
     *
     * @return array<int, int> id kelas tujuan yang belum punya rombel
     */
    public function kelasTujuanTanpaRombel(array $pemetaan, int $tahunTujuanId): array
    {
        $idKelas = collect($pemetaan)
            ->filter(fn ($tujuan) => $tujuan !== '' && $tujuan !== 'LULUS')
            ->map(fn ($tujuan) => (int) $tujuan)
            ->unique()
            ->values();

        if ($idKelas->isEmpty()) {
            return [];
        }

        return $idKelas->reject(
            fn ($id) => Rombel::where('kelas_id', $id)->where('tahun_ajaran_id', $tahunTujuanId)->exists()
        )->all();
    }

    /**
     * @param  array<int|string, string>  $pemetaan  kelas asal => kelas tujuan ('LULUS' atau '')
     * @param  array<int|string, string>  $override  siswa => 'ikuti'|'tinggal'|'lulus'
     * @return array{rinci: array<string, array<string, int>>, dilewati:int, naik:int, tinggal:int, lulus:int}
     */
    public function jalankan(int $tahunAsalId, int $tahunTujuanId, array $pemetaan, array $override = []): array
    {
        if ($tahunAsalId === $tahunTujuanId) {
            throw new \InvalidArgumentException('Tahun asal dan tujuan tidak boleh sama.');
        }

        return DB::transaction(function () use ($tahunAsalId, $tahunTujuanId, $pemetaan, $override) {
            $rinci = [];
            $dilewati = 0;
            $total = ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];

            $siswas = Siswa::with('kelas')
                ->where('is_aktif', true)
                ->where('tahun_ajaran_id', $tahunAsalId)
                ->lockForUpdate()
                ->get();

            foreach ($siswas as $siswa) {
                if (RiwayatKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $tahunTujuanId)
                    ->exists()
                ) {
                    $dilewati++;

                    continue;
                }

                $aksi = $override[$siswa->id] ?? 'ikuti';
                $tujuan = $pemetaan[$siswa->kelas_id] ?? '';

                if ($aksi === 'ikuti') {
                    if ($tujuan === 'LULUS') {
                        $aksi = 'lulus';
                    } elseif ($tujuan !== '') {
                        $aksi = 'naik';
                    } else {
                        $dilewati++;

                        continue;
                    }
                }

                $kunci = $siswa->kelas->nama_kelas ?? '?';
                $rinci[$kunci] ??= ['naik' => 0, 'tinggal' => 0, 'lulus' => 0];
                $rinci[$kunci][$aksi]++;
                $total[$aksi]++;

                $this->tulis($siswa, $aksi, (int) $tujuan, $tahunTujuanId);
            }

            return ['rinci' => $rinci, 'dilewati' => $dilewati] + $total;
        });
    }

    private function tulis(Siswa $siswa, string $aksi, int $tujuan, int $tahunTujuanId): void
    {
        if ($aksi === 'naik') {
            RiwayatKelas::create([
                'siswa_id' => $siswa->id,
                'kelas_id' => $tujuan,
                'tahun_ajaran_id' => $tahunTujuanId,
                'status' => 'aktif',
            ]);
            $siswa->update(['kelas_id' => $tujuan, 'tahun_ajaran_id' => $tahunTujuanId]);

            return;
        }

        if ($aksi === 'tinggal') {
            RiwayatKelas::create([
                'siswa_id' => $siswa->id,
                'kelas_id' => $siswa->kelas_id,
                'tahun_ajaran_id' => $tahunTujuanId,
                'status' => 'mengulang',
            ]);
            $siswa->update(['tahun_ajaran_id' => $tahunTujuanId]);

            return;
        }

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $siswa->kelas_id,
            'tahun_ajaran_id' => $tahunTujuanId,
            'status' => 'lulus',
        ]);
        $siswa->update(['is_aktif' => false]);
    }

    /**
     * Pasangan kelas tujuan otomatis: tingkat + 1 dengan huruf sama, atau
     * LULUS bila kelas asal sudah tingkat tertinggi.
     *
     * @return array<int, string>
     */
    public function petaOtomatis(): array
    {
        $semuaKelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();
        $tingkatAkhir = $semuaKelas->pluck('tingkat')->map(fn ($t) => (int) $t)->max();

        $peta = [];

        foreach ($semuaKelas as $kelas) {
            if ((int) $kelas->tingkat === $tingkatAkhir) {
                $peta[$kelas->id] = 'LULUS';

                continue;
            }

            $huruf = preg_replace('/^\d+/', '', (string) $kelas->nama_kelas);
            $pasangan = $semuaKelas->first(
                fn ($k) => (int) $k->tingkat === (int) $kelas->tingkat + 1
                    && preg_replace('/^\d+/', '', (string) $k->nama_kelas) === $huruf
            );

            $peta[$kelas->id] = $pasangan?->id ? (string) $pasangan->id : '';
        }

        return $peta;
    }
}
