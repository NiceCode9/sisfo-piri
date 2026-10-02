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
        'kuota',
        'terisi',
        'diskon_persen',
        'keuntungan',
        'keterangan',
        'warna_border',
        'is_aktif',
    ];

    protected $casts = [
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

    /**
     * Tahapan (fase) gelombang ini, sudah terurut untuk ditampilkan.
     */
    public function tahapan(): HasMany
    {
        return $this->hasMany(GelombangTahap::class)->orderBy('urutan');
    }

    /**
     * Tahap pendaftaran — satu-satunya yang menentukan apakah gelombang bisa
     * dipilih pendaftar.
     */
    public function tahapPendaftaran(): ?GelombangTahap
    {
        return $this->tahapan->firstWhere('tipe', 'pendaftaran');
    }

    /**
     * Tanpa tahap pendaftaran, gelombang tidak punya jadwal dan tidak akan
     * pernah bisa dipilih.
     */
    public function punyaJadwalPendaftaran(): bool
    {
        return $this->tahapan->contains('tipe', 'pendaftaran');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Gelombang yang tahap pendaftarannya sedang berlangsung.
     *
     * Diimplementasikan lewat `whereHas` ke tabel tahap, bukan perbandingan
     * kolom tanggal langsung, supaya tanggal hanya ada di satu tempat.
     */
    public function scopeSedangBerlangsung($query)
    {
        return $query->whereHas(
            'tahapan',
            fn ($tahap) => $tahap->pendaftaran()->sedangBerlangsung()
        );
    }

    /**
     * Gelombang aktif untuk satu tahun ajaran yang jendelanya sedang terbuka.
     *
     * Hanya ini yang boleh dipilih pendaftar. Gelombang yang belum mulai atau
     * sudah lewat tanggal tutup tidak ditampilkan, dan tidak dianggap tersedia
     * walau `is_aktif` masih true.
     *
     * `with('tahapan')` dipakai karena hampir setiap pemanggil butuh tahap
     * pendaftaran (untuk tanggal) maupun semua tahap (untuk timeline) — tanpa
     * eager load ini setiap gelombang memicu query sendiri.
     */
    public static function terbuka(?TahunAjaran $tahun): Collection
    {
        if (! $tahun) {
            return new Collection;
        }

        return static::where('tahun_ajaran_id', $tahun->id)
            ->aktif()
            ->sedangBerlangsung()
            ->with('tahapan')
            ->orderBy('nomor_urut')
            ->get();
    }

    /**
     * Semua gelombang aktif untuk satu tahun ajaran, termasuk yang belum mulai
     * dan yang sudah lewat. Dipakai untuk timeline publik, karena calon perlu
     * tahu batch berikutnya kapan dibuka.
     */
    public static function semuaAktif(?TahunAjaran $tahun): Collection
    {
        if (! $tahun) {
            return new Collection;
        }

        return static::where('tahun_ajaran_id', $tahun->id)
            ->aktif()
            ->with('tahapan')
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
     *
     * Tahap ikut di-eager-load supaya pengecekan tanggal di `bisaMasuk()`
     * terjadi di dalam transaksi yang sama dengan lock baris induk.
     */
    public static function kunci(int $gelombangId): ?self
    {
        return self::whereKey($gelombangId)->with('tahapan')->lockForUpdate()->first();
    }

    /**
     * Apakah pendaftar boleh memilih gelombang ini.
     *
     * Gelombang tanpa tahap `pendaftaran` tidak punya jadwal sama sekali,
     * jadi tidak bisa dipilih.
     */
    public function bisaMasuk(): bool
    {
        $pendaftaran = $this->tahapPendaftaran();

        return $this->is_aktif
            && $pendaftaran !== null
            && $pendaftaran->berlangsung()
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
