<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rombel>
 *
 * Prasyarat untuk factory Exam dan test isolasi CBT (ExamPolicy memanggil
 * Rombel::terjangkauOleh, yang butuh rombel nyata untuk diuji).
 *
 * @method static Rombel wali(Guru $guru)
 */
class RombelFactory extends Factory
{
    protected $model = Rombel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'wali_guru_id' => null,
        ];
    }

    public function wali(Guru $guru): static
    {
        return $this->state(fn (array $attributes): array => ['wali_guru_id' => $guru->id]);
    }

    public function kelas(Kelas $kelas): static
    {
        return $this->state(fn (array $attributes): array => ['kelas_id' => $kelas->id]);
    }

    public function tahunAjaran(TahunAjaran $tahun): static
    {
        return $this->state(fn (array $attributes): array => ['tahun_ajaran_id' => $tahun->id]);
    }
}
