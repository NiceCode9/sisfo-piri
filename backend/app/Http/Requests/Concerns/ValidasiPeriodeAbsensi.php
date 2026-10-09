<?php

namespace App\Http\Requests\Concerns;

trait ValidasiPeriodeAbsensi
{
    /**
     * Nilai `periode` yang dipahami `AbsensiController::rentangPeriode()`.
     *
     * @var list<string>
     */
    public const PERIODE = ['minggu', 'bulan', 'ganjil', 'genap', 'tahun'];

    /**
     * Aturan untuk filter periode rekap kehadiran.
     *
     * Tanpa aturan ini `rentangPeriode()` berakhir pada cabang `default` yang
     * bermakna "tahun penuh". Filter ngawur seperti `?periode=ngawur` lalu
     * menampilkan angka rekap satu tahun penuh seolah-olah itu yang diminta —
     * jawaban yang salah tanpa error. `acuan` yang tidak bisa di-parse membuat
     * `Carbon::parse()` melempar exception dan halaman jadi 500.
     *
     * `acuan` hanya dipakai bersama `minggu` dan `bulan`; untuk periode
     * semester dan tahun penuh yang dipakai `tahun_ajaran_id`.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function aturanPeriodeAbsensi(): array
    {
        return [
            'periode' => ['nullable', 'string', 'in:'.implode(',', self::PERIODE)],
            'acuan' => ['nullable', 'string', 'date'],
        ];
    }

    /**
     * Pesan error untuk aturan periode rekap.
     *
     * @return array<string, string>
     */
    protected function pesanPeriodeAbsensi(): array
    {
        return [
            'periode.in' => 'Periode rekap tidak dikenal. Pilihan: '.implode(', ', self::PERIODE).'.',
            'acuan.date' => 'Tanggal acuan rekap tidak valid.',
        ];
    }
}
