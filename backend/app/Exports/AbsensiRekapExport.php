<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiRekapExport implements FromArray, WithHeadings
{
    /**
     * @param  array{rombel: mixed, siswas: mixed, matriks: array, tanggals: array, rinci: bool, mulai: string, selesai: string}  $rekap
     */
    public function __construct(protected array $rekap) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $kepala = ['Nama', 'NIS'];

        if ($this->rekap['rinci']) {
            foreach ($this->rekap['tanggals'] as $tgl) {
                $kepala[] = $tgl;
            }
        }

        return array_merge($kepala, ['H', 'S', 'I', 'A', 'T', '%']);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $baris = [];

        foreach ($this->rekap['siswas'] as $siswa) {
            $data = $this->rekap['matriks'][$siswa->id];
            $row = [
                $siswa->user->name ?? '-',
                $siswa->nis ?? $siswa->nisn ?? '-',
            ];

            if ($this->rekap['rinci']) {
                foreach ($this->rekap['tanggals'] as $tgl) {
                    $row[] = $data['perTanggal']->get($tgl)?->status ?? '-';
                }
            }

            $row[] = $data['hitung']['hadir'];
            $row[] = $data['hitung']['sakit'];
            $row[] = $data['hitung']['izin'];
            $row[] = $data['hitung']['alpa'];
            $row[] = $data['hitung']['terlambat'];
            $row[] = $data['persen'];

            $baris[] = $row;
        }

        return $baris;
    }
}
