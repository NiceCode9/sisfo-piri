<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GuruTemplateExport implements FromArray, WithColumnFormatting, WithHeadings
{
    /**
     * Kolom NIP diformat Teks agar digit di depan tidak hilang.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'nip',
            'nama',
            'jenis_kelamin',
            'telp',
            'alamat',
            'is_aktif',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            ['196501011990031001', 'Contoh Guru Satu', 'L', '081234567890', 'Jl Contoh 1', 1],
            ['197001012000042002', 'Contoh Guru Dua', 'P', '', '', 1],
        ];
    }
}
