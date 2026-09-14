<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Rombel extends Model
{
    use HasFactory;

    /**
     * Jangkar kelas berjalan: satu baris per pasangan kelas + tahun.
     * Modul histori (nilai/materi/absensi) merujuk ke sini, bukan ke
     * pasangan kolom lepas, agar histori per kelas-berjalan utuh.
     */
    protected $fillable = [
        'kelas_id',
        'tahun_ajaran_id',
        'wali_guru_id',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function waliGuru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'wali_guru_id');
    }

    public function pengampus(): HasMany
    {
        return $this->hasMany(Pengampu::class);
    }

    public function materis(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    /**
     * ID siswa anggota rombel (diturunkan dari riwayat kelas+tahun).
     *
     * @return array<int>
     */
    public function anggotaIds(): array
    {
        return RiwayatKelas::where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran_id', $this->tahun_ajaran_id)
            ->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Histori lengkap satu rombel: penugasan + wali + daftar siswa.
     *
     * @return array{penugasan: Collection, wali: ?Guru, siswa: Collection}
     */
    public function historiLengkap(): array
    {
        $this->loadMissing(['pengampus.guru', 'pengampus.mataPelajaran', 'waliGuru']);

        $siswa = Siswa::whereHas('riwayatKelas', fn ($q) => $q
            ->where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran_id', $this->tahun_ajaran_id)
        )->with('user')->orderBy('nis')->get();

        return [
            'penugasan' => $this->pengampus,
            'wali' => $this->waliGuru,
            'siswa' => $siswa,
        ];
    }
}
