<?php

namespace App\Support;

use App\Models\CalonSiswa;
use App\Models\Pembayaran;
use App\Models\PembayaranLainnya;
use App\Models\RencanaAngsuran;
use Illuminate\Support\Str;

/**
 * Penomoran dokumen PPDB yang bebas bentrok.
 *
 * Angka/business key ini berformat `PREFIX-TAHUN-XXXX` dan kolomnya UNIQUE.
 * Versi lama memakai `count() + 1` sehingga bentrok begitu ada baris terhapus
 * (count() turun → nomor yang sudah dipakai dibuat ulang → unique violation → 500).
 *
 * Pola di sini: baris insert dulu memakai placeholder acak (unik), lalu kolom
 * diisi ulang berdasarkan `id` auto-increment yang dijamin unik oleh database.
 * Format akhir tetap sama dengan sebelumnya, mis. `PPDB-2026-0007`.
 */
class Penomor
{
    /**
     * Nilai sementara yang dipakai saat insert, dijamin unik per baris.
     */
    public static function placeholder(string $prefix): string
    {
        return strtoupper($prefix).'-'.Str::upper(Str::random(12));
    }

    public static function calon(CalonSiswa $calon): void
    {
        $calon->update(['no_pendaftaran' => self::format('PPDB', $calon->id)]);
    }

    public static function pembayaran(Pembayaran $pembayaran): void
    {
        $pembayaran->update(['kode_pembayaran' => self::format('PAY', $pembayaran->id)]);
    }

    public static function angsuran(RencanaAngsuran $angsuran): void
    {
        $angsuran->update(['kode_angsuran' => self::format('ANG', $angsuran->id)]);
    }

    public static function pembayaranLainnya(PembayaranLainnya $pembayaran): void
    {
        $pembayaran->update(['kode_pembayaran' => self::format('LNN', $pembayaran->id)]);
    }

    private static function format(string $prefix, int $id): string
    {
        return sprintf('%s-%s-%04d', $prefix, date('Y'), $id);
    }
}
