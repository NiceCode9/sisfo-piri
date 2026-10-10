<?php

namespace App\Models;

use App\Http\Controllers\Admin\PengaturanController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pengaturan extends Model
{
    /**
     * Kunci yang aman di-cache, dengan TTL.
     *
     * Hanya nilai acuan yang jarang berubah. `cek_belum_hadir_terakhir`
     * sengaja TIDAK ada di sini: `CekBelumHadir` menulis lalu membacanya di
     * run yang sama sebagai penanda self-gating, jadi cache membuat penanda
     * itu basi dan perintah itu jalan lebih dari sekali sehari.
     *
     * @var list<string>
     */
    public const KUNCI_CACHE = [
        'batas_terlambat',
        'jam_cek_belum_hadir',
        'semester_ganjil_mulai',
        'semester_ganjil_selesai',
        'semester_genap_mulai',
        'semester_genap_selesai',
    ];

    public const TTL_CACHE = 300;

    protected $fillable = [
        'kunci',
        'nilai',
        'keterangan',
    ];

    /**
     * Cache key untuk sebuah pengaturan.
     */
    public static function cacheKey(string $kunci): string
    {
        return 'pengaturan:'.$kunci;
    }

    /**
     * Buang cache seluruh kunci yang di-cache.
     *
     * Dipanggil setelah admin mengubah pengaturan lewat
     * {@see PengaturanController}. Nilai yang
     * tidak masuk daftar cache tidak perlu dibuang.
     */
    public static function flushCache(): void
    {
        foreach (self::KUNCI_CACHE as $kunci) {
            Cache::forget(self::cacheKey($kunci));
        }
    }

    /**
     * Baca nilai pengaturan.
     *
     * Kunci yang terdaftar di {@see KUNCI_CACHE} diambil dari cache sehingga
     * jalur panas seperti scan QR tidak melakukan satu query per permintaan.
     * Sisanya selalu dibaca langsung, karena nilainya bisa berubah di dalam
     * proses yang sedang berjalan.
     */
    public static function nilai(string $kunci, ?string $default = null): ?string
    {
        if (! in_array($kunci, self::KUNCI_CACHE, true)) {
            return static::where('kunci', $kunci)->first()?->nilai ?? $default;
        }

        return Cache::remember(
            self::cacheKey($kunci),
            self::TTL_CACHE,
            fn () => static::where('kunci', $kunci)->first()?->nilai
        ) ?? $default;
    }
}
