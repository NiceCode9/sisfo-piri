<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SiswaTemplateExport implements FromArray, WithHeadings, WithColumnFormatting
{
    /**
     * Kolom NIS/NISN diformat Teks agar angka 0 di depan tidak hilang.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'nis',
            'nisn',
            'nama',
            'kelas',
            'tahun_ajaran',
            'nama_ayah',
            'pekerjaan_ayah',
            'nama_ibu',
            'pekerjaan_ibu',
            'no_hp_orang_tua',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['1001', '0070012001', 'Contoh Siswa Satu', '7A', '2026/2027', 'Bapak Contoh', 'Wiraswasta', 'Ibu Contoh', 'IRT', '081234567890'],
            ['1002', '0070012002', 'Contoh Siswa Dua', '7B', '2026/2027', '', '', '', '', ''],
        ];
    }
}
