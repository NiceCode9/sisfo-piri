<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class GelombangTahap extends Model
{
    protected $table = 'gelombang_tahapan';

    /**
     * Jenis tahap. Nilai enum ini pernah disamakan dengan `jadwal_ppdbs.tipe`
     * (tabel itu sudah dihapus); status pendaftaran kini hanya membaca tahap
     * ini, jadi daftar ini adalah satu-satunya kosakata jadwal PPDB.
     */
    public const TIPE = [
        'pendaftaran' => 'Pendaftaran',
        'verifikasi' => 'Verifikasi Berkas',
        'tes' => 'Tes Seleksi',
        'pengumuman' => 'Pengumuman Hasil',
        'daftar_ulang' => 'Daftar Ulang',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'gelombang_id',
        'tipe',
        'nama_tahap',
        'urutan',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function gelombang(): BelongsTo
    {
        return $this->belongsTo(Gelombang::class);
    }

    public function scopeTipe(Builder $query, string $tipe): Builder
    {
        return $query->where('tipe', $tipe);
    }

    public function scopePendaftaran(Builder $query): Builder
    {
        return $query->where('tipe', 'pendaftaran');
    }

    public function scopeSedangBerlangsung(Builder $query): Builder
    {
        $hari = now()->startOfDay();

        return $query->whereDate('tanggal_mulai', '<=', $hari)
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')
                ->orWhereDate('tanggal_selesai', '>=', $hari));
    }

    public function getLabelTipeAttribute(): string
    {
        return self::TIPE[$this->tipe] ?? $this->tipe;
    }

    /**
     * Tanggal berakhir tahap.
     *
     * `tanggal_selesai` NULL berarti tahap berlangsung satu hari, jadi sama
     * dengan tanggal mulainya. Model ini yang menahan konvensi itu supaya view
     * tidak perlu memeriksa NULL satu per satu.
     */
    public function tanggalAkhir(): Carbon
    {
        return $this->tanggal_selesai?->copy() ?? $this->tanggal_mulai->copy();
    }

    public function berlangsung(): bool
    {
        $hari = now()->startOfDay();

        return $this->tanggal_mulai->copy()->startOfDay()->lte($hari)
            && $this->tanggalAkhir()->endOfDay()->gte($hari);
    }

    public function sudahLewat(): bool
    {
        return $this->tanggalAkhir()->endOfDay()->lt(now()->startOfDay());
    }

    public function belumMulai(): bool
    {
        return $this->tanggal_mulai->copy()->startOfDay()->gt(now()->startOfDay());
    }

    /**
     * Apakah tahap ini punya rentang beberapa hari (bukan tahap satu hari).
     */
    public function punyaJendela(): bool
    {
        return $this->tanggal_selesai !== null
            && ! $this->tanggal_selesai->isSameDay($this->tanggal_mulai);
    }
}
