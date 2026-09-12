<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    /**
     * Nilai default pengaturan sistem.
     */
    public function run(): void
    {
        Pengaturan::firstOrCreate(
            ['kunci' => 'batas_terlambat'],
            ['nilai' => '07:00', 'keterangan' => 'Batas jam datang (HH:MM); lewat dari ini tercatat terlambat.'],
        );
    }
}
