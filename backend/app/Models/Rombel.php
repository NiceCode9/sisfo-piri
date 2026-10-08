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
     * Sengaja sempit. Konstanta ini dibaca modul e-learning dan tugas di
     * `MateriPolicy`, `TugasPolicy`, dan `BerkasController`, dan di sana
     * pencocokan role menggantikan cek permission. Menambah role di sini
     * karena kebutuhan satu modul berarti membuka modul lain, jadi tiap modul
     * punya daftarnya sendiri lewat `ROLE_ABSENSI_UNIVERSAL`.
     *
     * @var list<string>
     */
    public const ROLE_UNIVERSAL = ['super-admin', 'admin'];

    /**
     * Role yang boleh mencatat kehadiran di seluruh rombel.
     *
     * Guru piket bekerja di gerbang sekolah, bukan di satu kelas, sehingga
     * jangkauan berbasis penugasan akan membuatnya melihat nol rombel dan
     * mematikan fitur yang memang miliknya. Konsep ini hanya berlaku untuk
     * absensi; materi, tugas, dan berkas tetap memakai `ROLE_UNIVERSAL`.
     *
     * @var list<string>
     */
    public const ROLE_ABSENSI_UNIVERSAL = ['super-admin', 'admin', 'guru-piket'];

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

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    /**
     * Data historis yang menempel pada rombel ini, dipakai {@see
     * \App\Http\Controllers\Admin\RombelController::destroy()} untuk
     * menolak penghapusan yang akan menghapus histori secara cascade.
     *
     * Setiap relasi di sini memakai `cascadeOnDelete`, jadi "hapus rombel"
     * sama artinya "hapus seluruh kehadiran, materi, tugas, dan nilai ujian
     * kelas ini". Karena itu relasi ini bukan sekadar informasi.
     *
     * @return array<string, string>
     */
    public function historiAttached(): array
    {
        return [
            'pengampus' => 'penugasan guru',
            'absensis' => 'data kehadiran',
            'materis' => 'materi',
            'tugas' => 'tugas',
            'exams' => 'ujian CBT',
        ];
    }

    /**
     * ID siswa yang pernah menjadi anggota rombel ini.
     *
     * Sengaja tanpa filter `status`: pemanggilan ini dipakai untuk menyusun
     * rekap historis (kehadiran, nilai tugas, nilai ujian), dan siswa yang sudah
     * pindah kelas tetap punya catatan di kelas yang ditinggalkan. Menghilang
     * dari sana akan menghapus jejaknya.
     *
     * Untuk keperluan operasional — siapa yang boleh dicatat hadir hari ini,
     * siapa yang boleh dinilai — pakai {@see anggotaIdsAktif()} supaya siswa
     * yang sudah pindah tidak ikut terhitung di kelas barunya.
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
     * ID siswa yang sedang menjadi anggota rombel ini.
     *
     * Sama dengan {@see anggotaIds()} tetapi hanya baris `aktif`. Dipakai
     * wherever pencatatan harus jatuh pada kelas yang sedang berjalan: grid
     * absensi, validasi batch, gate scan QR, dan otorisasi berkas.
     *
     * @return array<int>
     */
    public function anggotaIdsAktif(): array
    {
        return RiwayatKelas::where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran_id', $this->tahun_ajaran_id)
            ->where('status', 'aktif')
            ->pluck('siswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * ID siswa untuk roster rekap.
     *
     * Untuk tahun berjalan memakai {@see anggotaIdsAktif()}: siswa yang sudah
     * pindah di tengah tahun tidak lagi dihitung sebagai anggota kelas ini,
     * dan baris absensinya pun tidak bisa dikoreksi lewat grid karena grid
     * hanya memuat anggota aktif. Untuk tahun historis memakai
     * {@see anggotaIds()} supaya rekap lama tetap menampilkan siswa yang
     * waktu itu masih di sini.
     *
     * @return array<int>
     */
    public function anggotaIdsUntukRekap(): array
    {
        return $this->tahun_ajaran_id === TahunAjaran::aktif()->value('id')
            ? $this->anggotaIdsAktif()
            : $this->anggotaIds();
    }

    /**
     * Rombel yang boleh dikelola oleh user: rombel yang diampu sebagai wali
     * kelas ATAU sebagai pengampu mata pelajaran.
     *
     * Akses bersifat fail-closed. Guru tanpa penugasan apa pun memperoleh nol
     * rombel, bukan seluruh rombel. User yang bukan guru dan bukan admin juga
     * nol — keadaan ini dulu terlewat karena fallback "kalau kosong, semua
     * rombel", yang membuat guru tanpa kelas bisa membaca tugas rombel mana
     * saja. Bypass hanya lewat daftar role universal, bukan lewat hasil kosong.
     *
     * @param  list<string>  $universal  Role yang boleh menembus seluruh rombel.
     *                                   Absensi memakai {@see ROLE_ABSENSI_UNIVERSAL}.
     */
    public function scopeTerjangkauUser(Builder $query, User $user, array $universal = self::ROLE_UNIVERSAL): Builder
    {
        if ($user->hasRole($universal)) {
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
     *
     * @param  list<string>  $universal  Role yang boleh menembus seluruh rombel.
     */
    public static function terjangkauOleh(User $user, ?int $rombelId, array $universal = self::ROLE_UNIVERSAL): bool
    {
        if ($rombelId === null) {
            return false;
        }

        return static::terjangkauUser($user, $universal)->whereKey($rombelId)->exists();
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
