<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gelombang extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran_id',
        'nama_gelombang',
        'nomor_urut',
        'badge',
        'tanggal_buka',
        'tanggal_tutup',
        'tanggal_tes',
        'tanggal_pengumuman',
        'kuota',
        'terisi',
        'diskon_persen',
        'keuntungan',
        'keterangan',
        'warna_border',
        'is_aktif',
    ];

    protected $casts = [
        'tanggal_buka' => 'date',
        'tanggal_tutup' => 'date',
        'tanggal_tes' => 'date',
        'tanggal_pengumuman' => 'date',
        'keuntungan' => 'array',
        'is_aktif' => 'boolean',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function calonSiswas(): HasMany
    {
        return $this->hasMany(CalonSiswa::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function scopeSedangBerlangsung($query)
    {
        $hari = now()->startOfDay();

        return $query->whereDate('tanggal_buka', '<=', $hari)
            ->whereDate('tanggal_tutup', '>=', $hari);
    }

    /**
     * Gelombang aktif untuk satu tahun ajaran yang jendelanya sedang terbuka.
     *
     * Hanya ini yang boleh dipilih pendaftar. Gelombang yang belum mulai atau
     * sudah lewat tanggal tutup tidak ditampilkan, dan tidak dianggap tersedia
     * walau `is_aktif` masih true.
     */
    public static function terbuka(?TahunAjaran $tahun): Collection
    {
        if (! $tahun) {
            return new Collection;
        }

        return static::where('tahun_ajaran_id', $tahun->id)
            ->aktif()
            ->sedangBerlangsung()
            ->orderBy('nomor_urut')
            ->get();
    }

    /**
     * Sisa kursi.
     *
     * Kuota <= 0 berarti tidak dibatasi, jadi vacancy tidak boleh dibatasi
     * angka nol (yang akan membuat semua pendaftar ditolak).
     */
    public function getSisaKursiAttribute(): ?int
    {
        if ($this->kuota <= 0) {
            return null;
        }

        return max(0, $this->kuota - $this->terisi);
    }

    public function kuotaPenuh(): bool
    {
        return $this->kuota > 0 && $this->terisi >= $this->kuota;
    }

    /**
     * Ambil satu gelombang dengan baris terkunci.
     *
     * Dipakai saat mengecek kuota: `KuotaPendaftaran` punya helper serupa, dan
     * tanpa lock dua pendaftar pada detik yang sama bisa sama-sama lolos
     * pemeriksaan kuota lalu membuat counter melewati batas.
     */
    public static function kunci(int $gelombangId): ?self
    {
        return self::whereKey($gelombangId)->lockForUpdate()->first();
    }

    /**
     * Apakah pendaftar boleh memilih gelombang ini.
     */
    public function bisaMasuk(): bool
    {
        return $this->is_aktif
            && $this->tanggal_buka->copy()->startOfDay()->lte(now()->startOfDay())
            && $this->tanggal_tutup->copy()->endOfDay()->gte(now()->startOfDay())
            && ! $this->kuotaPenuh();
    }

    /**
     * Diskon dalam persen, dijepit di 0-100.
     *
     * Sengaja tidak memakai accessor `diskon_persen` supaya tidak menimpa kolom
     * aslinya: diskon di luar rentang itu membuat tagihan negatif atau diskon
     * lebih dari 100%, jadi yang dipakai perhitungan adalah nilai terjepit ini.
     */
    public function getDiskonEfektifAttribute(): int
    {
        return (int) max(0, min(100, (int) $this->diskon_persen));
    }

    public function getPersentaseAttribute(): float
    {
        if ($this->kuota <= 0) {
            return 0;
        }

        return round($this->terisi / $this->kuota * 100, 1);
    }
}
