<?php

namespace App\Http\Requests\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProsesKenaikanWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Payload wizard: pemetaan[kelas_asal_id] = kelas_tujuan_id|'LULUS'|'',
     * override[siswa_id] = 'ikuti'|'tinggal'|'lulus'.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tahun_asal_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'tahun_tujuan_id' => ['required', 'integer', 'exists:tahun_ajarans,id', 'different:tahun_asal_id'],
            'pemetaan' => ['required', 'array', 'min:1'],
            'pemetaan.*' => ['nullable'],
            'override' => ['sometimes', 'array'],
            'override.*' => ['in:ikuti,tinggal,lulus'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $asal = (int) $this->input('tahun_asal_id');

            foreach ((array) $this->input('pemetaan', []) as $kelasAsalId => $tujuan) {
                if (! is_scalar($kelasAsalId) || ! Kelas::where('id', $kelasAsalId)->exists()) {
                    $validator->errors()->add('pemetaan', "Kelas asal {$kelasAsalId} tidak dikenal.");

                    continue;
                }

                if ($tujuan === '' || $tujuan === null || $tujuan === 'LULUS') {
                    continue;
                }

                if (! is_scalar($tujuan) || ! Kelas::where('id', $tujuan)->exists()) {
                    $validator->errors()->add("pemetaan.{$kelasAsalId}", 'Kelas tujuan tidak dikenal.');
                }
            }

            $siswaValid = Siswa::where('is_aktif', true)
                ->where('tahun_ajaran_id', $asal)
                ->pluck('id')->map(fn ($id) => (string) $id)->all();

            foreach ((array) $this->input('override', []) as $siswaId => $aksi) {
                if (! in_array((string) $siswaId, $siswaValid, true)) {
                    $validator->errors()->add("override.{$siswaId}", 'Siswa tidak aktif pada tahun asal.');
                }
            }
        });
    }
}
