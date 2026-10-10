<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidasiKunciJawaban
{
    /**
     * Cek silang `correct_answer` terhadap `options` dan `type`.
     *
     * Tanpa cek ini, `single_choice` dengan kunci ["a","b"] atau kunci yang
     * tidak ada di opsi akan tersimpan dan menghasilkan skor 0 untuk SEMUA
     * siswa tanpa satu pun pesan error — guru mengira soal susah, bukan salah
     * konfigurasi. Aturan `array` biasa hanya memastikan bentuknya berupa
     * array, bukan isinya berarti.
     */
    protected function validasiKunciJawaban(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');

            // Uraian dinilai guru; tidak ada kunci jawaban per-option.
            if ($type === 'essay') {
                return;
            }

            $optionKeys = collect($this->input('options', []))
                ->pluck('key')
                ->filter(fn ($k) => $k !== null)
                ->map(fn ($k) => (string) $k)
                ->all();

            $answer = $this->input('correct_answer');

            if ($optionKeys === []) {
                $validator->errors()->add('options', 'Soal pilihan wajib punya minimal satu opsi.');

                return;
            }

            if (! is_array($answer) || $answer === []) {
                $validator->errors()->add('correct_answer', 'Tentukan kunci jawaban untuk soal ini.');

                return;
            }

            // Kunci harus string supaya perbandingan saat penilaian tidak
            // tersandung tipe (opsi datang dari DB sebagai string, payload
            // JSON bisa integer).
            $answer = array_values(array_map('strval', $answer));

            $hilang = array_diff($answer, $optionKeys);

            if ($hilang !== []) {
                $validator->errors()->add(
                    'correct_answer',
                    'Kunci jawaban tidak ada di daftar opsi: '.implode(', ', $hilang).'.'
                );

                return;
            }

            if ($type === 'single_choice' && count($answer) !== 1) {
                $validator->errors()->add('correct_answer', 'Soal pilihan tunggal hanya boleh punya satu kunci jawaban.');
            }

            if ($type === 'multiple_choice' && count($answer) < 2) {
                $validator->errors()->add('correct_answer', 'Soal pilihan jamak minimal punya dua kunci jawaban.');
            }

            if (count(array_unique($answer)) !== count($answer)) {
                $validator->errors()->add('correct_answer', 'Kunci jawaban ada yang berulang.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function aturanSoalDasar(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'options' => ['nullable', 'array', 'max:10'],
            'options.*.key' => ['required_with:options', 'string', 'max:2'],
            'options.*.text' => ['required_with:options', 'string', 'max:1000'],
            'correct_answer' => ['nullable', 'array', 'max:10'],
            'correct_answer.*' => ['nullable'],
            'score' => ['required', 'integer', 'min:1', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
