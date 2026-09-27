<?php

namespace App\Exports;

use App\Models\CalonSiswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Export calon siswa yang sudah DITERIMA untuk Cosmic — admin mengisi kolom
 * nis + kelas (dan opsional tahun_ajaran) lalu import ulang lewat
 * admin/siswas/import (SiswaImport, mode upsert by nisn).
 */
class CalonDiterimaExport implements FromArray, WithColumnFormatting, WithHeadings
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
        $tahunAktif = TahunAjaran::aktif()->value('nama_tahun_ajaran');
        $daftarKelas = Kelas::orderBy('nama_kelas')->pluck('nama_kelas')->implode(' / ');

        $rows = CalonSiswa::with('tahunAjaran')
            ->where('status_pendaftaran', 'diterima')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn (CalonSiswa $c) => [
                '', // nis — diisi admin
                (string) $c->nisn,
                $c->nama_lengkap,
                '', // kelas — diisi admin (pilihan: '.$daftarKelas.')
                $c->tahunAjaran->nama_tahun_ajaran ?? $tahunAktif ?? '',
                $c->nama_ayah ?? '',
                $c->pekerjaan_ayah ?? '',
                $c->nama_ibu ?? '',
                $c->pekerjaan_ibu ?? '',
                $c->no_hp_orang_tua ?? '',
            ])->all();

        // Sisipkan 1 baris contoh sebagai panduan
        array_unshift($rows, [
            '1001',
            '0070012001',
            'Contoh Siswa Satu',
            $daftarKelas !== '' ? explode(' / ', $daftarKelas)[0] : '7A',
            $tahunAktif ?? '2026/2027',
            'Bapak Contoh',
            'Wiraswasta',
            'Ibu Contoh',
            'IRT',
            '081234567890',
        ]);

        return $rows;
    }
}
