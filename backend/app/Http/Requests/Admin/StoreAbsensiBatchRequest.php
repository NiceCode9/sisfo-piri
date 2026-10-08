<?php

namespace App\Http\Requests\Admin;

use App\Models\Rombel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiBatchRequest extends FormRequest
{
    /**
     * Otorisasi rombel, bukan hanya permission.
     *
     * Middleware `absensis.create` hanya menjawab "boleh mencatat absensi",
     * bukan "boleh mencatat di kelas ini". Tanpa cek jangkauan di sini, setiap
     * pemegang permission tersebut bisa POST batch untuk rombel mana pun
     * karena `rombel_id` hanya divalidasi `exists`.
     */
    public function authorize(): bool
    {
        return Rombel::terjangkauOleh(
            $this->user(),
            $this->input('rombel_id') === null ? null : (int) $this->input('rombel_id'),
            Rombel::ROLE_ABSENSI_UNIVERSAL,
        );
    }

    /**
     * Payload: status[siswa_id] = hadir|sakit|izin|alpa|terlambat.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rombel_id' => ['required', 'integer', 'exists:rombels,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array', 'min:1'],
            'status.*' => ['required', 'in:hadir,sakit,izin,alpa,terlambat'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rombel = Rombel::find($this->input('rombel_id'));

            if (! $rombel) {
                return;
            }

            // Hanya siswa yang masih aktif di rombel ini. Siswa yang sudah
            // pindah kelas punya baris riwayat di sini dengan status non-aktif
            // dan tidak boleh dicatat hadir lagi.
            $anggota = $rombel->anggotaIdsAktif();

            foreach ((array) $this->input('status', []) as $siswaId => $status) {
                if (! in_array((int) $siswaId, $anggota, true)) {
                    $validator->errors()->add("status.{$siswaId}", 'Siswa bukan anggota rombel ini.');
                }
            }
        });
    }
}
