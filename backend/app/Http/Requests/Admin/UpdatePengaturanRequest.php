<?php

namespace App\Http\Requests\Admin;

use App\Models\Pengaturan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batas_terlambat' => ['sometimes', 'required', 'date_format:H:i'],
            'jam_buka_absen' => ['sometimes', 'required', 'date_format:H:i'],
            'jam_tutup_absen' => ['sometimes', 'required', 'date_format:H:i'],
            'menit_ganti_qr' => ['sometimes', 'required', 'integer', 'min:5', 'max:120'],
            'jam_cek_belum_hadir' => ['sometimes', 'required', 'date_format:H:i'],
            'semester_ganjil_mulai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_ganjil_selesai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_genap_mulai' => ['sometimes', 'required', 'date_format:m-d'],
            'semester_genap_selesai' => ['sometimes', 'required', 'date_format:m-d'],
            'maintenance_mode' => ['sometimes', 'required', 'boolean'],
            'maintenance_pesan' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Perbandingan teks H:i sah karena format di atas sudah mengunci jenis
        // nilainya. Tanpa cek relasi ini, satu kesalahan input membunuh semua
        // scan di esok pagi dengan gejala yang tidak jelas di mana pun.
        $validator->after(function (Validator $validator) {
            $buka = $this->nilaiAkhir('jam_buka_absen', '07:00');
            $batas = $this->nilaiAkhir('batas_terlambat', '07:15');
            $tutup = $this->nilaiAkhir('jam_tutup_absen', '07:45');

            if (! ($buka < $batas && $batas < $tutup)) {
                $validator->errors()->add('jam_buka_absen', 'Urutan jam tidak masuk akal: jam buka harus lebih awal dari batas terlambat, dan batas terlambat lebih awal dari jam tutup.');
            }
        });
    }

    /**
     * Nilai yang berlaku setelah simpan: input baru bila dikirim, nilai DB
     * bila tidak, karena cek relasi harus menilai bentuk akhirnya.
     */
    protected function nilaiAkhir(string $kunci, string $bawaan): string
    {
        $nilai = $this->input($kunci);

        return $nilai === null
            ? (Pengaturan::nilai($kunci, $bawaan) ?? $bawaan)
            : (string) $nilai;
    }
}
