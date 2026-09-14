<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TugasRekapExport implements FromArray, WithHeadings
{
    /**
     * @param  array{rombel: mixed, tugasList: mixed, siswas: mixed, matriks: array, rataPerSiswa: array}  $rekap
     */
    public function __construct(protected array $rekap) {}

    public function headings(): array
    {
        $head = ['Nama', 'NIS'];
        foreach ($this->rekap['tugasList'] as $tugas) {
            $head[] = $tugas->judul;
        }
        $head[] = 'Rata-rata';

        return $head;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->rekap['siswas'] as $siswa) {
            $row = [$siswa->user->name ?? '-', $siswa->nis ?? $siswa->nisn ?? '-'];
            foreach ($this->rekap['tugasList'] as $tugas) {
                $p = $this->rekap['matriks'][$siswa->id][$tugas->id] ?? null;
                $row[] = $p?->nilai ?? '-';
            }
            $row[] = $this->rekap['rataPerSiswa'][$siswa->id] ?? '-';
            $rows[] = $row;
        }

        return $rows;
    }
}
