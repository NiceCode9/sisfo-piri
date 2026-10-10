<?php

namespace App\Exports;

use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Template impor absensi harian.
 *
 * Bentuknya sengaja menempel grid manual: satu baris per siswa untuk satu
 * tanggal. Dengan begitu hasil impor tidak bisa dibedakan dari input biasa —
 * termasuk soal aturan "status kosong berarti tidak dicatat".
 *
 * Kolom NIS diformat Teks karena Excel rewrite 04001 menjadi 4001, dan siswa
 * yang salah tercatat lebih berbahaya daripada impor yang ditolak.
 */
class AbsensiTemplateExport implements FromArray, WithColumnFormatting, WithHeadings
{
    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['rombel', 'tahun_ajaran', 'tanggal', 'nis', 'status'];
    }

    /**
     * Contoh memakai data yang benar-benar ada, jadi template bisa langsung
     * dicoba sebelum diisi sungguhan.
     *
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        [$rombel, $tahun] = $this->contohRombel();
        $nis = (string) ($rombel
            ? Siswa::where('kelas_id', $rombel->kelas_id)
                ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
                ->orderBy('nis')
                ->value('nis')
            : '');

        $kelas = $rombel?->kelas->nama_kelas ?? '7A';
        $namaTahun = $tahun?->nama ?? '';
        $hari = now()->toDateString();

        return [
            [$kelas, $namaTahun, $hari, $nis ?: '0001', 'hadir'],
            [$kelas, $namaTahun, $hari, $nis ?: '0001', 'sakit'],
            // Status kosong berarti "tidak dicatat", sama seperti grid manual.
            [$kelas, $namaTahun, $hari, $nis ?: '0001', ''],
        ];
    }

    /**
     * @return array{0: ?Rombel, 1: ?TahunAjaran}
     */
    private function contohRombel(): array
    {
        $tahun = TahunAjaran::aktif()->first();

        if ($tahun === null) {
            return [null, null];
        }

        $rombel = Rombel::where('tahun_ajaran_id', $tahun->id)
            ->with('kelas')
            ->orderBy('kelas_id')
            ->first();

        return [$rombel, $tahun];
    }
}
