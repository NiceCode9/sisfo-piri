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
     * Buang pilihan "belum dicatat" sebelum validasi.
     *
     * `<select>` selalu mengirim nilai untuk setiap siswa, jadi siswa yang
     * tidak disentuh guru terkirim sebagai string kosong. Kalau nilai kosong
     * itu ikut disimpan, membuka grid hari yang belum ada absensinya lalu
     * menekan Simpan akan menulis `hadir` untuk seluruh kelas — absensi palsu
     * terbentuk tanpa error dan tanpa jejak. Baris kosong berarti "tidak
     * dicatat", jadi dibuang di sini dan tidak pernah menyentuh database.
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        if (! is_array($status)) {
            return;
        }

        $this->merge([
            'status' => array_filter($status, fn ($nilai) => $nilai !== null && $nilai !== ''),
        ]);
    }

    /**
     * Payload: status[siswa_id] = hadir|sakit|izin|alpa|terlambat.
     *
     * Kunci yang dihapus {@see prepareForValidation()} tidak ikut divalidasi,
     * jadi siswa yang dibiarkan "belum dicatat" tidak menggagalkan simpanan.
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
            // Nullable di sini karena tidak bisa diketahui lebih dulu dari
            // aturan statis: field ini hanya wajib bila ada baris yang statusnya
            // berubah. Syarat itu ditegakkan di controller, yang baru tahu mana
            // yang koreksi setelah membandingkan nilai lama dan baru.
            'alasan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status kehadiran untuk minimal satu siswa.',
            'status.min' => 'Pilih status kehadiran untuk minimal satu siswa.',
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
