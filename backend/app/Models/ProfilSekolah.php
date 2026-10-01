<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilSekolah extends Model
{
    /**
     * Nilai profil yang masih berupa placeholder hasil seeder.
     *
     * `GANTI: ...` sengaja dibiarkan di database sebagai pengingat ke admin,
     * tapi tidak boleh bocor ke halaman publik: sempat muncul sebagai teks
     * "GANTI: (0274) ..." dan bahkan `href="tel:GANTI: ..."` yang benar-benar
     * rusak. Versi `*Bersih` di bawah mengubahnya jadi null supaya view bisa
     * menyembunyikan bagiannya.
     */
    public const PLACEHOLDER = '/^\s*(GANTI|TODO|ISI|DUMMY)\b[:\-]?/iu';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'alamat',
        'telp',
        'email',
        'website',
        'tahun_berdiri',
        'akreditasi',
        'logo_path',
        'foto_gedung_path',
        'sambutan',
        'visi',
        'misi',
        'nama_kepala',
        'foto_kepala_path',
        'maps_embed_url',
    ];

    protected $casts = [
        'misi' => 'array',
        'tahun_berdiri' => 'integer',
    ];

    /**
     * Ambil baris profil tunggal (singleton).
     */
    public static function aktif(): ?self
    {
        return static::first();
    }

    public function getAlamatBersihAttribute(): ?string
    {
        return $this->bersih($this->alamat);
    }

    public function getTelpBersihAttribute(): ?string
    {
        return $this->bersih($this->telp);
    }

    public function getEmailBersihAttribute(): ?string
    {
        return $this->bersih($this->email);
    }

    public function getWebsiteBersihAttribute(): ?string
    {
        return $this->bersih($this->website);
    }

    public function getSambutanBersihAttribute(): ?string
    {
        return $this->bersih($this->sambutan);
    }

    public function getVisiBersihAttribute(): ?string
    {
        return $this->bersih($this->visi);
    }

    public function getNamaKepalaBersihAttribute(): ?string
    {
        return $this->bersih($this->nama_kepala);
    }

    /**
     * Nomor telepon dalam format international untuk tautan `tel:`.
     *
     * Returns null kalau nomor kosong, masih placeholder, atau tidak punya
     * digit sama sekali — supaya view tidak membuat `href="tel:"` yang rusak.
     */
    public function getTelpTelAttribute(): ?string
    {
        $telp = $this->telp_bersih;

        if ($telp === null) {
            return null;
        }

        $digit = preg_replace('/\D+/', '', $telp);

        if ($digit === null || $digit === '') {
            return null;
        }

        return str_starts_with($digit, '0') ? '+62'.substr($digit, 1) : '+'.$digit;
    }

    /**
     * Nomor WhatsApp untuk `wa.me`: digit saja, tanpa tanda plus.
     */
    public function getWhatsappAttribute(): ?string
    {
        $telepon = $this->telp_tel;

        return $telepon === null
            ? null
            : ltrim(str_replace('+', '', $telepon), '0');
    }

    /**
     * Misi sekolah, sudah dibersihkan dari placeholder dan entri kosong.
     *
     * @return array<int, string>
     */
    public function getMisiBersihAttribute(): array
    {
        return collect(is_array($this->misi) ? $this->misi : [])
            ->map(fn ($misi) => $this->bersih(is_string($misi) ? $misi : null))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Ubah nilai kosong atau placeholder menjadi null.
     */
    private function bersih(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $nilai = trim($nilai);

        if ($nilai === '' || preg_match(self::PLACEHOLDER, $nilai)) {
            return null;
        }

        return $nilai;
    }
}
