<?php

namespace App\Http\Requests\Admin;

use App\Models\Rombel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

            $anggota = $rombel->anggotaIds();

            foreach ((array) $this->input('status', []) as $siswaId => $status) {
                if (! in_array((int) $siswaId, $anggota, true)) {
                    $validator->errors()->add("status.{$siswaId}", 'Siswa bukan anggota rombel ini.');
                }
            }
        });
    }
}
