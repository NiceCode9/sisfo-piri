<?php

namespace App\Imports;

use App\Models\Guru;
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
 * Import data guru dari Excel.
 *
 * Akun login diambil dari NIP (`username = nip`, `password = nip`). NIP yang
 * sudah terdaftar hanya memperbarui data profil — password tidak diubah agar
 * akun lama tetap bisa login.
 */
class GuruImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public int $updated = 0;

    /**
     * Kegagalan yang tidak bisa ditangani aturan statis, dikumpulkan manual
     * karena pada mode ToCollection Maatwebsite hanya mencatat kegagalan
     * dari tahap validasi.
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
            $this->simpanBaris(array_map(
                fn ($v) => is_string($v) ? trim($v) : $v,
                $row->toArray()
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function simpanBaris(array $row): void
    {
        $nip = (string) $row['nip'];
        $profil = [
            'nama' => (string) $row['nama'],
            'jenis_kelamin' => (string) $row['jenis_kelamin'],
            'telp' => ! empty($row['telp']) ? (string) $row['telp'] : null,
            'alamat' => ! empty($row['alamat']) ? (string) $row['alamat'] : null,
            // Sel kosong, "0", dan tak terbaca tetap dipisah dari benar (1).
            'is_aktif' => isset($row['is_aktif']) ? filter_var($row['is_aktif'], FILTER_VALIDATE_BOOLEAN) : true,
        ];

        DB::transaction(function () use ($nip, $profil) {
            $guru = Guru::where('nip', $nip)->first();

            if ($guru) {
                $guru->update($profil);

                // Guru lama yang belum punya akun tetap dibuatkan akunnya.
                if (! $guru->user_id) {
                    $this->buatAkun($nip, $profil['nama']);
                } elseif ($guru->user?->name !== $profil['nama']) {
                    $guru->user->update(['name' => $profil['nama']]);
                }
                $this->updated++;

                return;
            }

            $user = $this->buatAkun($nip, $profil['nama']);
            Guru::create($profil + ['user_id' => $user->id, 'nip' => $nip]);
            $this->imported++;
        });
    }

    private function buatAkun(string $nip, string $nama): User
    {
        $user = User::create([
            'username' => $nip,
            'name' => $nama,
            'password' => $nip,
        ]);
        $user->assignRole('guru');

        return $user;
    }

    public function rules(): array
    {
        $digits = function (string $attribute, mixed $value, callable $fail): void {
            if (! preg_match('/^[0-9]+$/', (string) $value)) {
                $fail('NIP harus angka (format kolom NIP sebagai Teks di Excel).');
            }
        };

        return [
            'nip' => ['required', 'string', 'max:50', $digits],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'telp' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'is_aktif' => ['nullable', 'boolean'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nip' => 'NIP',
            'nama' => 'Nama',
            'jenis_kelamin' => 'Jenis kelamin',
            'telp' => 'Telp',
            'is_aktif' => 'Aktif',
        ];
    }
}
