<?php

namespace Database\Seeders;

use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\WaliKelas;
use Illuminate\Database\Seeder;

class RombelSeeder extends Seeder
{
    /**
     * Bentuk rombel dari pasangan unik (kelas, tahun) pada arsip
     * wali_kelas dan riwayat_kelas. Idempoten.
     * (Pasangan dari pengampus sudah ditautkan saat migrasi convert;
     * kolom pasangan pengampus sudah dilepas sehingga tidak dibaca lagi.)
     */
    public function run(): void
    {
        $pasangan = WaliKelas::select('kelas_id', 'tahun_ajaran_id')->distinct()->get()
            ->concat(RiwayatKelas::select('kelas_id', 'tahun_ajaran_id')->distinct()->get())
            ->unique(fn ($row) => $row->kelas_id.'-'.$row->tahun_ajaran_id)
            ->values();

        foreach ($pasangan as $row) {
            Rombel::firstOrCreate(
                ['kelas_id' => $row->kelas_id, 'tahun_ajaran_id' => $row->tahun_ajaran_id],
                ['wali_guru_id' => WaliKelas::where('kelas_id', $row->kelas_id)
                    ->where('tahun_ajaran_id', $row->tahun_ajaran_id)
                    ->value('guru_id')],
            );
        }
    }
}
