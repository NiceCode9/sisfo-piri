<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\User;
use Closure;
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
 * Akun login diambil dari nomor telepon (`username = telp`, password default
 * adalah mentah `"password"`). NIP yang sudah terdaftar memperbarui data profil
 * sekaligus menyelaraskan akunnya: username diubah ke nomor telepon terbaru dan
 * password direset ke bawaan.
 */
class GuruImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /** Password bawaan setiap akun guru. */
    public const PASSWORD_BAWAAN = 'password';

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
        $telp = (string) $row['telp'];

        // Username = nomor telepon, jadi bentrok dengan akun lain gagal di
        // sini (bukan lewat exception unik di tengah transaksi).
        if (User::where('username', $telp)
            ->where('id', '!=', Guru::where('nip', $nip)->value('user_id') ?? 0)
            ->exists()) {
            $this->customFailures[] = "No. HP {$telp} sudah dipakai username akun lain.";

            return;
        }

        $profil = [
            'nama' => (string) $row['nama'],
            'jenis_kelamin' => (string) $row['jenis_kelamin'],
            'telp' => $telp,
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
                    $this->buatAkun($profil);
                } else {
                    $guru->user->update([
                        'username' => $profil['telp'],
                        'name' => $profil['nama'],
                        'password' => self::PASSWORD_BAWAAN,
                    ]);
                }
                $this->updated++;

                return;
            }

            $user = $this->buatAkun($profil);
            Guru::create($profil + ['user_id' => $user->id, 'nip' => $nip]);
            $this->imported++;
        });
    }

    private function buatAkun(array $profil): User
    {
        $user = User::create([
            'username' => $profil['telp'],
            'name' => $profil['nama'],
            'password' => self::PASSWORD_BAWAAN,
        ]);
        $user->assignRole('guru');

        return $user;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:50', $this->harusAngka('NIP harus angka (format kolom sebagai Teks di Excel).')],
            'telp' => ['required', 'string', 'max:50', $this->harusAngka('No. HP harus angka (format kolom sebagai Teks di Excel).')],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'is_aktif' => ['nullable', 'boolean'],
        ];
    }

    private function harusAngka(string $pesan): Closure
    {
        // Nama atribut dari Maatwebsite berprefiks per baris, jadi hanya isi
        // selnya yang dicek; nama kolom tidak relevan di sini.
        return function (string $attribute, mixed $value, callable $fail) use ($pesan): void {
            if (! preg_match('/^[0-9]+$/', (string) $value)) {
                $fail($pesan);
            }
        };
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
