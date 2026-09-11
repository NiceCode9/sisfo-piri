<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SiswaImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): ?Siswa
    {
        $kelas = ! empty($row['kelas'])
            ? Kelas::where('nama_kelas', $row['kelas'])->first()
            : null;

        $tahun = ! empty($row['tahun_ajaran'])
            ? TahunAjaran::where('nama_tahun_ajaran', $row['tahun_ajaran'])->first()
            : TahunAjaran::aktif()->first();

        $siswa = DB::transaction(function () use ($row, $kelas, $tahun) {
            $nisn = (string) $row['nisn'];
            $user = User::create([
                'username' => $nisn,
                'name' => $row['nama'],
                'password' => $nisn,
            ]);
            $user->assignRole('siswa');

            $siswa = Siswa::create([
                'user_id' => $user->id,
                'nis' => isset($row['nis']) && $row['nis'] !== '' && $row['nis'] !== null ? (string) $row['nis'] : null,
                'nisn' => $nisn,
                'tahun_ajaran_id' => $tahun?->id,
                'kelas_id' => $kelas?->id,
                'tanggal_diterima' => now()->toDateString(),
                'is_aktif' => true,
                'nama_ayah' => $row['nama_ayah'] ?? null,
                'pekerjaan_ayah' => $row['pekerjaan_ayah'] ?? null,
                'nama_ibu' => $row['nama_ibu'] ?? null,
                'pekerjaan_ibu' => $row['pekerjaan_ibu'] ?? null,
                'no_hp_orang_tua' => $row['no_hp_orang_tua'] ?? null,
            ]);

            if ($kelas) {
                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahun?->id,
                    'status' => 'aktif',
                ]);
            }

            return $siswa;
        });

        $this->imported++;

        return $siswa;
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
            'nis' => ['nullable', 'unique:siswas,nis'],
            'nisn' => ['required', $digit10, 'unique:siswas,nisn', 'unique:users,username'],
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
