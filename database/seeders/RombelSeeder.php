<?php

namespace Database\Seeders;

use App\Models\Pengampu;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\WaliKelas;
use Illuminate\Database\Seeder;

class RombelSeeder extends Seeder
{
    /**
     * Bentuk rombel dari pasangan unik (kelas, tahun) yang sudah ada
     * di pengampus, wali_kelas, dan riwayat_kelas. Idempoten.
     * (Migrasi hanya mengurus data lama; seeder mengurus data seed.)
     */
    public function run(): void
    {
        $pasangan = Pengampu::select('kelas_id', 'tahun_ajaran_id')->distinct()->get()
            ->concat(WaliKelas::select('kelas_id', 'tahun_ajaran_id')->distinct()->get())
            ->concat(RiwayatKelas::select('kelas_id', 'tahun_ajaran_id')->distinct()->get())
            ->unique(fn ($row) => $row->kelas_id.'-'.$row->tahun_ajaran_id)
            ->values();

        foreach ($pasangan as $row) {
            $rombel = Rombel::firstOrCreate(
                ['kelas_id' => $row->kelas_id, 'tahun_ajaran_id' => $row->tahun_ajaran_id],
                ['wali_guru_id' => WaliKelas::where('kelas_id', $row->kelas_id)
                    ->where('tahun_ajaran_id', $row->tahun_ajaran_id)
                    ->value('guru_id')],
            );

            Pengampu::where('kelas_id', $row->kelas_id)
                ->where('tahun_ajaran_id', $row->tahun_ajaran_id)
                ->whereNull('rombel_id')
                ->update(['rombel_id' => $rombel->id]);
        }
    }
}
