<?php

namespace App\Services;

/**
 * Token permanen di balik QR kartu pelajar.
 *
 * Ditandatangani HMAC agar siswa tidak bisa menebak atau mengarang token
 * siswa lain hanya dengan mengubah id di URL/QR.
 */
class TokenKartuSiswa
{
    public const AWALAN = 'abskartu';

    public static function generate(int $siswaId): string
    {
        return implode('|', [self::AWALAN, (string) $siswaId, self::tanda((string) $siswaId)]);
    }

    public static function baca(string $token): ?int
    {
        $bagian = explode('|', $token);

        if (count($bagian) !== 3 || $bagian[0] !== self::AWALAN || ! ctype_digit($bagian[1])) {
            return null;
        }

        if (! hash_equals(self::tanda($bagian[1]), $bagian[2])) {
            return null;
        }

        return (int) $bagian[1];
    }

    protected static function tanda(string $siswaId): string
    {
        return hash_hmac('sha256', self::AWALAN.'|'.$siswaId, (string) config('app.key'));
    }
}
