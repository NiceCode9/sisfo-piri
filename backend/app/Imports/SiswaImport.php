<?php

namespace App\Imports;

use App\Models\CalonSiswa;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import data siswa dengan dua mode sekaligus:
 *
 * 1. Mode)Pembuatan baru (import manual dari master) —NISN belum ada di `siswas`.
 *    User dibuat `username=nisn`, `password=nisn`.
 * 2. Mode Penempatan (import hasil export calon diterima) — NISN sudah ada di `siswas`
 *    karena sudah `terima` saat PPDB. Baris ini hanya MENGISI `nis`, `kelas_id`,
 *    `tahun_ajaran_id`, dan field ortu yang masih kosong. Password TIDAK diubah
 *    (akun pendaftar tetap bisa login) dan tidak membuat User/duplicate.
 *
 * Membership rombel diturunkan dari `RiwayatKelas(kelas_id, tahun_ajaran_id)`
 * yang cocok dengan `Rombel(kelas_id, tahun_ajaran_id)` — jadi cukup isi kolom
 * `kelas` pada Excel, tidak perlu kolom rombel.
 */
class SiswaImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public int $updated = 0;

    /**
     * Kegagalan yang tidak bisa ditangani aturan statis (mis. NIS bentrok dengan
     * siswa lain). Dikumpulkan manual karena pada mode ToCollection Maatwebsite
     * hanya mencatat kegagalan dari tahap validasi.
     *
     * @var array<int, string>
     */
    public array $customFailures = [];

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $row = array_map(
                fn ($v) => is_string($v) ? trim($v) : $v,
                $row->toArray()
            );

            $this->simpanBaris($row);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function simpanBaris(array $row): void
    {
        $nisn = (string) ($row['nisn'] ?? '');
        $nama = (string) ($row['nama'] ?? '');
        $nis = ! empty($row['nis']) ? (string) $row['nis'] : null;

        $kelas = ! empty($row['kelas'])
            ? Kelas::where('nama_kelas', $row['kelas'])->first()
            : null;

        $tahun = ! empty($row['tahun_ajaran'])
            ? TahunAjaran::where('nama_tahun_ajaran', $row['tahun_ajaran'])->first()
            : TahunAjaran::aktif()->first();

        $ortu = array_filter([
            'nama_ayah' => $row['nama_ayah'] ?? null,
            'pekerjaan_ayah' => $row['pekerjaan_ayah'] ?? null,
            'nama_ibu' => $row['nama_ibu'] ?? null,
            'pekerjaan_ibu' => $row['pekerjaan_ibu'] ?? null,
            'no_hp_orang_tua' => $row['no_hp_orang_tua'] ?? null,
        ], fn ($v) => ! empty($v));

        // Validasi manual: NIS harus unik dan tidak boleh milik siswa lain.
        if ($nis !== null) {
            $pemakai = Siswa::where('nis', $nis)
                ->when(
                    ($ada = Siswa::where('nisn', $nisn)->first()) !== null,
                    fn ($q) => $q->where('id', '!=', $ada->id)
                )
                ->exists();

            if ($pemakai) {
                $this->customFailures[] = "NIS {$nis} sudah dipakai siswa lain.";

                return;
            }
        }

        DB::transaction(function () use ($nisn, $nama, $nis, $kelas, $tahun, $ortu) {
            $existing = Siswa::where('nisn', $nisn)->first();

            // MODE 2 — Penempatan: sudah ada siswa dari PPDB, hanya lengkapi.
            if ($existing) {
                $existing->fill(array_merge(
                    $nis !== null ? ['nis' => $nis] : [],
                    $kelas !== null ? ['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun?->id ?? $existing->tahun_ajaran_id] : [],
                    // isi field ortu hanya bila masih kosong (data PPDB lebihECU)
                    $this->ortuKosong($existing, $ortu)
                ));
                $existing->save();

                if ($existing->user_id && $nama !== '' && $existing->user?->name !== $nama) {
                    $existing->user->update(['name' => $nama]);
                }

                if ($kelas) {
                    RiwayatKelas::firstOrCreate(
                        ['siswa_id' => $existing->id, 'kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun?->id],
                        ['status' => 'aktif']
                    );
                }

                $this->updated++;

                return;
            }

            // MODE 1 — Pembuatan baru: User + Siswa + RiwayatKelas.
            $calon = CalonSiswa::where('nisn', $nisn)->first();
            $user = $calon?->user_id
                ? $calon->user
                : User::create([
                    'username' => $nisn,
                    'name' => $nama,
                    'password' => $nisn,
                ]);

            if ($calon?->user_id) {
                $user->assignRole('siswa');
            } else {
                $user->assignRole('siswa');
            }

            $siswa = Siswa::create(array_merge([
                'calon_siswa_id' => $calon?->id,
                'user_id' => $user->id,
                'nis' => $nis,
                'nisn' => $nisn,
                'tahun_ajaran_id' => $tahun?->id,
                'kelas_id' => $kelas?->id,
                'tanggal_diterima' => now()->toDateString(),
                'is_aktif' => true,
            ], $ortu));

            if ($kelas) {
                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahun?->id,
                    'status' => 'aktif',
                ]);
            }

            $this->imported++;
        });
    }

    /**
     * Field ortu dari Excel hanya dipakai untuk kolom yang masih kosong di DB.
     *
     * @param  array<string, mixed>  $ortu
     * @return array<string, mixed>
     */
    private function ortuKosong(Siswa $siswa, array $ortu): array
    {
        $final = [];
        foreach ($ortu as $kolom => $nilai) {
            if (empty($siswa->{$kolom})) {
                $final[$kolom] = $nilai;
            }
        }

        return $final;
    }

    public function rules(): array
    {
        // Sel Excel numerik terbaca sebagai integer, jadi validasi memakai
        // string hasil casting (bukan rule string) agar tidak gagal palsu.
        $digit10 = function (string $attribute, mixed $value, callable $fail): void {
            if (! preg_match('/^[0-9]{10}$/', (string) $value)) {
                $fail('NISN harus 10 digit angka (format kolom sebagai Teks di Excel).');
            }
        };

        return [
            'nis' => ['nullable', 'string', 'max:20'],
            'nisn' => ['required', $digit10],
            'nama' => ['required', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', 'exists:kelas,nama_kelas'],
            'tahun_ajaran' => ['nullable', 'string', 'exists:tahun_ajarans,nama_tahun_ajaran'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:50'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:50'],
            'no_hp_orang_tua' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nisn' => 'NISN',
            'nama' => 'Nama',
            'kelas' => 'Kelas',
            'tahun_ajaran' => 'Tahun Ajaran',
        ];
    }
}
