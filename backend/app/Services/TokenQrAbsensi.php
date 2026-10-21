<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Carbon;

/**
 * Token QR harian milik akun guru.
 *
 * Isinya murni hasil perhitungan (guru, tanggal, slot waktu): tidak ada
 * tabel token, tidak ada tulis ke DB saat guru membuka halaman. Slot
 * berubah sendiri tiap `menit_ganti_qr`, jadi QR yang difoto seseorang dan
 * diteruskan ke teman di rumah menjadi hangat paling lama satu interval,
 * dan QR hari ini tidak pernah berlaku untuk esok hari.
 */
class TokenQrAbsensi
{
    protected const AWALAN = 'absqr';

    public static function menitGanti(): int
    {
        return max(5, (int) (Pengaturan::nilai('menit_ganti_qr', '10') ?? '10'));
    }

    /** Nomor slot dari waktu server, dihitung sejak tengah malam zona sekolah. */
    public static function slot(Carbon $sekarang): int
    {
        return intdiv($sekarang->secondsSinceMidnight(), self::menitGanti() * 60);
    }

    public static function generate(int $userId, ?Carbon $sekarang = null): string
    {
        $sekarang ??= now();

        return implode('|', [
            self::AWALAN,
            (string) $userId,
            $sekarang->toDateString(),
            (string) self::slot($sekarang),
            self::tanda((string) $userId, $sekarang->toDateString(), (string) self::slot($sekarang)),
        ]);
    }

    /**
     * Baca dan periksa token yang diisi siswa.
     *
     * Toleransi satu slot ke belakang menutup celah atas QR yang tersimpan di
     * layar guru tepat saat pergeseran slot, sementara token bulan/lama
     * tetap ditolak. Bentuk kembalinya `null` berarti ditolak dengan alasan
     * apa pun: penerbitnya cukup menampilkan satu pesan.
     *
     * @return array{user_id: int, slot: int}|null
     */
    public static function baca(string $token, ?Carbon $sekarang = null): ?array
    {
        $bagian = explode('|', trim($token));

        if (count($bagian) !== 5 || $bagian[0] !== self::AWALAN) {
            return null;
        }

        [$awalan, $userId, $tanggal, $slot, $hmac] = $bagian;

        if (! ctype_digit($userId) || ! ctype_digit($slot)) {
            return null;
        }

        $sekarang ??= now();

        if ($tanggal !== $sekarang->toDateString()) {
            return null;
        }

        $slotSekarang = self::slot($sekarang);
        $slotDiisikan = (int) $slot;

        if ($slotDiisikan !== $slotSekarang && $slotDiisikan !== $slotSekarang - 1) {
            return null;
        }

        if (! hash_equals(self::tanda($userId, $tanggal, $slot), $hmac)) {
            return null;
        }

        return ['user_id' => (int) $userId, 'slot' => $slotDiisikan];
    }

    protected static function tanda(string $userId, string $tanggal, string $slot): string
    {
        return hash_hmac('sha256', self::AWALAN.'|'.$userId.'|'.$tanggal.'|'.$slot, (string) config('app.key'));
    }
}
