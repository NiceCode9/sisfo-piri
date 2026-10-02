<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Rombel extends Model
{
    use HasFactory;

    /**
     * Role yang boleh melihat seluruh rombel tanpa perlu penugasan.
     *
     * @var list<string>
     */
    public const ROLE_UNIVERSAL = ['super-admin', 'admin'];

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
     * Rombel yang boleh dikelola oleh user: rombel yang diampu sebagai wali
     * kelas ATAU sebagai pengampu mata pelajaran.
     *
     * Akses bersifat fail-closed. Guru tanpa penugasan apa pun memperoleh nol
     * rombel, bukan seluruh rombel. User yang bukan guru dan bukan admin juga
     * nol —KEADAAN ini dulu terlewat karena fallback "kalau kosong, semua
     * rombel", yang membuat guru tanpa kelas bisa membaca tugas rombel mana
     * saja. Bypass hanya lewat ROLE_UNIVERSAL, bukan lewat hasil kosong.
     */
    public function scopeTerjangkauUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole(self::ROLE_UNIVERSAL)) {
            return $query;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        if ($guru === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($guru) {
            $q->where('wali_guru_id', $guru->id)
                ->orWhereIn('id', Pengampu::where('guru_id', $guru->id)->select('rombel_id'));
        });
    }

    /**
     * Padanan {@see scopeTerjangkauUser()} untuk satu rombel, dipakai policy
     * dan controller yang memeriksa satu baris saja.
     */
    public static function terjangkauOleh(User $user, ?int $rombelId): bool
    {
        if ($rombelId === null) {
            return false;
        }

        return static::terjangkauUser($user)->whereKey($rombelId)->exists();
    }

    /**
     * Rombel milik seorang siswa, dibaca dari kelas + tahun berjalan.
     *
     * Sebagian lama memakai `first()?->id` (hanya satu baris) dan sebagian
     * memakai `pluck('id')` (semua baris) untuk hal yang sama, sehingga
     * siswa yang ada di lebih dari satu rombel terlihat bisa menjangkau
     * materirombel yang bukan miliknya. Semua pemanggil kini memakai
     * satu definisi ini.
     *
     * @return array<int>
     */
    public static function untukSiswa(?Siswa $siswa): array
    {
        if ($siswa === null) {
            return [];
        }

        return static::where('kelas_id', $siswa->kelas_id)
            ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Histori lengkap satu rombel: penugasan + wali + daftar siswa.
     *
     * @return array{penugasan: Collection, wali: ?Guru, siswa: Collection}
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

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
