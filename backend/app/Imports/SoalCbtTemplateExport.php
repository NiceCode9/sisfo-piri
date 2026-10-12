<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template Excel untuk import soal CBT.
 *
 * Isinya sengaja berisi contoh yang nyata — termasuk satu soal bergambar — bukan
 * header kosong saja. Tanpa contoh, guru tidak tahu kolom `gambar` harus diisi
 * apa, dan format itu tidak bisa ditebak dari nama kolom.
 *
 * Sengaja TIDAK memakai `WithTitle`/`ShouldAutoTitle`: baris judul akan
 * menggeser header ke baris kedua, sedangkan importer membaca baris pertama
 * sebagai heading (`WithHeadingRow`).
 *
 * @implements FromArray<int, array<int, mixed>>
 */
class SoalCbtTemplateExport implements FromArray, WithColumnWidths, WithStyles
{
    /** @var list<string> */
    public const KOLOM = ['teks', 'tipe', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci', 'skor', 'gambar'];

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            self::KOLOM,
            // Contoh 1 — pilihan tunggal, tanpa gambar.
            ['Berapakah hasil 7 x 8?', 'single', '54', '56', '58', '60', 'b', '5', ''],
            // Contoh 2 — pilihan jamak: kunci dipisah titik koma.
            ['Pilih semua bilangan prima dari 2, 9, 13, dan 15.', 'multiple', '2', '9', '13', '15', 'a;c', '10', ''],
            // Contoh 3 — uraian. Kolom opsi dan kunci dikosongkan.
            ['Jelaskan langkah menghitung luas persegi dengan sisi 8 cm.', 'essay', '', '', '', '', '', '15', ''],
            // Contoh 4 — soal bergambar. Kolom `gambar` berisi PATH DI DALAM
            // paket ZIP, bukan nama berkas bebas. Berkas gambarnya sendiri harus
            // ikut dikemas di folder `gambar/` dalam zip yang sama.
            [
                'Perhatikan grafik berikut. Pada tahun berapa jumlah PJL = 120?',
                'single', '2020', '2021', '2022', '2023', 'c', '10',
                'gambar/contoh-01.png',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']],
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 60,
            'B' => 12,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 18,
            'G' => 10,
            'H' => 8,
            'I' => 30,
        ];
    }
}
