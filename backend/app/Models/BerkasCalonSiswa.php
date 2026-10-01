<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerkasCalonSiswa extends Model
{
    /**
     * Kolom berkas yang boleh diisi ulang (tidak termasuk sertifikat, yang
     * punya tabel sendiri).
     *
     * @var list<string>
     */
    public const UPLOADABLE = [
        'ijazah_path',
        'kk_path',
        'akta_path',
        'foto_path',
        'skl_path',
        'krm_path',
        'kip_path',
    ];

    /**
     * Nilai yang boleh tersimpan di `berkas_perlu_perbaikan`. `sertifikat`
     * ikut di sini karena admin bisa menandai sertifikat perlu diganti walau
     * kolomnya berada di tabel terpisah.
     *
     * @var list<string>
     */
    public const PERLU_PERBAIKAN = [
        'ijazah_path',
        'kk_path',
        'akta_path',
        'foto_path',
        'skl_path',
        'krm_path',
        'kip_path',
        'sertifikat',
    ];

    /**
     * Label ramah untuk setiap kolom berkas. Dipakai form admin, dashboard
     * siswa, dan pesan error — supaya nama kolom mentah tidak pernah bocor ke
     * pengguna.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'ijazah_path' => 'Ijazah',
        'kk_path' => 'Kartu Keluarga',
        'akta_path' => 'Akta Kelahiran',
        'foto_path' => 'Pas Foto',
        'skl_path' => 'Surat Keterangan Lulus',
        'krm_path' => 'Kartu Rencana Murid',
        'kip_path' => 'Kartu Indonesia Pintar',
        'sertifikat' => 'Sertifikat Prestasi',
    ];

    protected $fillable = [
        'calon_siswa_id',
        'ijazah_path',
        'kk_path',
        'akta_path',
        'foto_path',
        'skl_path',
        'krm_path',
        'kip_path',
        'catatan_berkas',
        'berkas_perlu_perbaikan',
        'alasan_penolakan',
        'status_verifikasi',
    ];

    protected $casts = [
        'berkas_perlu_perbaikan' => 'array',
        'status_verifikasi' => 'boolean',
    ];

    public function calonSiswa()
    {
        return $this->belongsTo(CalonSiswa::class);
    }

    /**
     * Daftar berkas yang masih perlu diunggah ulang oleh siswa.
     *
     * @return list<string>
     */
    public function berkasPerluDiunggahUlang(): array
    {
        return array_values(array_intersect(
            $this->berkas_perlu_perbaikan ?? [],
            self::UPLOADABLE,
        ));
    }

    public function perluPerbaikan(): bool
    {
        return ($this->berkas_perlu_perbaikan ?? []) !== [];
    }

    /**
     * Label dari daftar `berkas_perlu_perbaikan`, untuk pesan & daftar di UI.
     *
     * @return list<string>
     */
    public function labelYangPerluPerbaikan(): array
    {
        $daftar = array_map(
            fn (string $field): string => self::LABELS[$field] ?? $field,
            $this->berkas_perlu_perbaikan ?? [],
        );

        return array_values($daftar);
    }
}
