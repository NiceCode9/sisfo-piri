<?php

namespace App\Support;

use App\Models\Gelombang;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;

/**
 * Satu-satunya jawaban untuk pertanyaan "apakah pendaftaran sedang dibuka?".
 *
 * Sebelumnya pertanyaan ini dijawab di tiga tempat berbeda: badge hero,
 * peringatan di halaman pendaftaran, dan gate di `store()`. Semuanya
 * menghitung dari sumber yang berbeda sehingga bisa — dan pernah —
 * saling bertentangan: saat baris jadwal `pendaftaran` kosong, form tetap
 * terbuka (gate-nya permissive) tapi hero menulis "Pendaftaran Ditutup".
 *
 * Sekarang semua membaca objek ini. Statusnya dihitung hanya dari Gelombang,
 * bukan juga dari jadwal PPDB — dua sumber untuk satu fakta itu persis sumber
 * kebingungan yang sedang dihapus di sini.
 */
class StatusPendaftaran
{
    /** Ada gelombang terbuka yang belum penuh. */
    public const BUKA = 'buka';

    /** Semua gelombang ada, belum ada yang dibuka. */
    public const BELUM = 'belum';

    /** Ada yang terbuka tapi semuanya sudah penuh. */
    public const PENUH = 'penuh';

    /** Semua gelombang sudah lewat. */
    public const TUTUP = 'tutup';

    /**
     * Belum ada satu pun gelombang, atau belum ada tahun ajaran aktif.
     *
     * Pendaftaran tetap DIBUKA (permissive) supaya sekolah yang belum mengatur
     * batch tidak terkunci tanpa sengaja — sama seperti perilaku gate jadwal
     * yang diganti. Badge disembunyikan karena tidak ada yang bisa dijanjikan.
     */
    public const TANPA_JADWAL = 'tanpa-jadwal';

    /**
     * @param  Collection<int, Gelombang>  $tersedia  Gelombang yang boleh dipilih sekarang
     */
    public function __construct(
        public readonly string $state,
        public readonly ?Gelombang $gelombangBerikut = null,
        public readonly Collection $tersedia = new Collection,
        public readonly ?string $pesan = null,
    ) {}

    public static function tentukan(?TahunAjaran $tahun): self
    {
        if (! $tahun) {
            return new self(
                self::TANPA_JADWAL,
                pesan: 'Tahun ajaran aktif belum diatur.'
            );
        }

        $semua = Gelombang::semuaAktif($tahun);

        if ($semua->isEmpty()) {
            return new self(self::TANPA_JADWAL);
        }

        $sedangDibuka = $semua->filter(
            fn (Gelombang $g) => $g->tahapPendaftaran()?->berlangsung() ?? false
        );

        $tersedia = $sedangDibuka->reject(fn (Gelombang $g) => $g->kuotaPenuh())->values();

        if ($tersedia->isNotEmpty()) {
            return new self(self::BUKA, tersedia: $tersedia, pesan: 'Pendaftaran sedang dibuka.');
        }

        if ($sedangDibuka->isNotEmpty()) {
            return new self(self::PENUH, pesan: 'Kuota gelombang yang sedang dibuka sudah penuh.');
        }

        $belumMulai = $semua
            ->filter(fn (Gelombang $g) => $g->tahapPendaftaran()?->belumMulai() ?? false)
            ->sortBy(fn (Gelombang $g) => $g->tahapPendaftaran()->tanggal_mulai)
            ->values();

        if ($belumMulai->isNotEmpty()) {
            return self::untuk($belumMulai->first());
        }

        return new self(self::TUTUP, pesan: 'Pendaftaran sudah ditutup.');
    }

    /**
     * Status untuk satu gelombang, dipakai badge di kartu gelombang.
     *
     * Menghasilkan objek yang sama dengan `tentukan()`, jadi kartu dan badge hero
     * tidak mungkin berbeda pendapat tentang batch yang sama.
     */
    public static function untuk(?Gelombang $gelombang): self
    {
        if (! $gelombang) {
            return new self(self::TANPA_JADWAL);
        }

        $pendaftaran = $gelombang->tahapPendaftaran();

        if (! $pendaftaran) {
            return new self(self::TANPA_JADWAL, pesan: 'Jadwal pendaftaran belum ditetapkan.');
        }

        if ($pendaftaran->berlangsung()) {
            return $gelombang->kuotaPenuh()
                ? new self(self::PENUH, pesan: "Kuota {$gelombang->nama_gelombang} sudah penuh.")
                : new self(self::BUKA, tersedia: new Collection([$gelombang]), pesan: 'Pendaftaran sedang dibuka.');
        }

        if ($pendaftaran->belumMulai()) {
            return new self(
                self::BELUM,
                gelombangBerikut: $gelombang,
                pesan: 'Pendaftaran dibuka '.$pendaftaran->tanggal_mulai->translatedFormat('d M Y')
                    .' pada '.$gelombang->nama_gelombang.'.',
            );
        }

        return new self(self::TUTUP, pesan: "Pendaftaran {$gelombang->nama_gelombang} sudah ditutup.");
    }

    /**
     * Apakah pendaftaran harus diterima.
     *
     * `TANPA_JADWAL` termasuk `true`: sekolah yang belum mengatur gelombang
     * tidak boleh terkunci.
     */
    public function dibuka(): bool
    {
        return in_array($this->state, [self::BUKA, self::TANPA_JADWAL], true);
    }

    /**
     * Apakah badge status layak ditampilkan.
     *
     * False untuk `TANPA_JADWAL` — tidak ada yang bisa dijanjikan, jadi
     * menampilkan "Pendaftaran Ditutup" akan menyesatkan.
     */
    public function tampilkanBadge(): bool
    {
        return $this->state !== self::TANPA_JADWAL;
    }

    /**
     * Apakah pendaftar wajib memilih gelombang.
     */
    public function wajibPilihGelombang(): bool
    {
        return $this->state === self::BUKA;
    }

    public function teksBadge(): string
    {
        return match ($this->state) {
            self::BUKA => 'Pendaftaran Dibuka!',
            self::BELUM => $this->pesan,
            self::PENUH => 'Kuota Gelombang Penuh',
            self::TUTUP => 'Pendaftaran Ditutup',
            default => '',
        };
    }

    /**
     * Label pendek untuk badge status per gelombang.
     *
     * Berbeda dengan `teksBadge()` yang untuk badge besar di hero, ini hanya untuk
     * kartu batch: "Dibuka", "Penuh", "Ditutup".
     */
    public function labelGelombang(): string
    {
        return match ($this->state) {
            self::BUKA => 'Dibuka',
            self::BELUM => 'Belum Mulai',
            self::PENUH => 'Kuota Penuh',
            self::TUTUP => 'Ditutup',
            default => 'Belum Dijadwalkan',
        };
    }
}
