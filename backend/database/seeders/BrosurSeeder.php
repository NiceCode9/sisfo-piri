<?php

namespace Database\Seeders;

use App\Models\Brosur;
use Illuminate\Database\Seeder;

class BrosurSeeder extends Seeder
{
    /**
     * Baris awal tanpa file — admin upload gambar sekali via halaman Brosur.
     */
    public function run(): void
    {
        $items = [
            ['title' => 'Brosur SPMB', 'desc' => 'Info lengkap program, keunggulan, dan biaya pendidikan', 'order' => 1],
            ['title' => 'Alur Pendaftaran', 'desc' => 'Panduan langkah demi langkah proses pendaftaran', 'order' => 2],
        ];

        foreach ($items as $item) {
            Brosur::firstOrCreate(['title' => $item['title']], $item + ['is_active' => true]);
        }
    }
}
