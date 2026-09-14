<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Database\Seeder;

class DemoElearningSeeder extends Seeder
{
    /**
     * 6 materi + 4 tugas + ~32 pengumpulan untuk uji E-Learning.
     * Idempoten via judul & tugas_id+siswa_id.
     */
    public function run(): void
    {
        $tahun = TahunAjaran::aktif()->firstOrFail();
        $rombel = Rombel::where('tahun_ajaran_id', $tahun->id)->firstOrFail();
        $mapels = MataPelajaran::aktif()->orderBy('kode')->get();
        $guru = Guru::firstOrFail();

        $materis = [
            ['judul' => 'Demo Materi Dokumen 1', 'tipe' => 'dokumen', 'file_path' => 'materi/demo-dokumen-1.pdf'],
            ['judul' => 'Demo Materi Dokumen 2', 'tipe' => 'dokumen', 'file_path' => 'materi/demo-dokumen-2.pdf'],
            ['judul' => 'Demo Materi Video File', 'tipe' => 'video', 'file_path' => 'materi/demo-video.mp4'],
            ['judul' => 'Demo Materi Video Link', 'tipe' => 'video', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['judul' => 'Demo Materi Link 1', 'tipe' => 'link', 'url' => 'https://example.com/materi-1'],
            ['judul' => 'Demo Materi Link 2', 'tipe' => 'link', 'url' => 'https://example.com/materi-2'],
        ];

        foreach ($materis as $i => $row) {
            Materi::firstOrCreate(
                ['judul' => $row['judul']],
                [
                    'rombel_id' => $rombel->id,
                    'mata_pelajaran_id' => $mapels[$i % $mapels->count()]->id,
                    'guru_id' => $guru->id,
                    'deskripsi' => 'Materi demo E-Learning',
                    'tipe' => $row['tipe'],
                    'file_path' => $row['file_path'] ?? null,
                    'url' => $row['url'] ?? null,
                    'is_aktif' => true,
                ]
            );
        }

        $tugasData = [
            ['judul' => 'Demo Tugas Lewat 1', 'deadline' => now()->subDay()],
            ['judul' => 'Demo Tugas Lewat 2', 'deadline' => now()->subHours(2)],
            ['judul' => 'Demo Tugas Akan Datang 1', 'deadline' => now()->addDays(3)],
            ['judul' => 'Demo Tugas Akan Datang 2', 'deadline' => now()->addDays(5)],
        ];

        $tugasList = [];
        foreach ($tugasData as $i => $row) {
            $tugasList[] = Tugas::firstOrCreate(
                ['judul' => $row['judul']],
                [
                    'rombel_id' => $rombel->id,
                    'mata_pelajaran_id' => $mapels[$i % $mapels->count()]->id,
                    'guru_id' => $guru->id,
                    'deskripsi' => 'Tugas demo',
                    'deadline' => $row['deadline'],
                    'is_aktif' => true,
                ]
            );
        }

        $siswas = Siswa::where('is_aktif', true)->limit(32)->get();
        $nilaiPool = [75, 80, 85, 90, 95, 78, null, null];

        foreach ($tugasList as $idx => $tugas) {
            $batch = $siswas->slice($idx * 8, 8);
            foreach ($batch as $j => $siswa) {
                $nilai = $nilaiPool[$j % count($nilaiPool)];
                PengumpulanTugas::updateOrCreate(
                    ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
                    [
                        'jawaban_text' => 'Jawaban demo '.$siswa->nis,
                        'file_path' => 'tugas/demo-'.$tugas->id.'-'.$siswa->id.'.pdf',
                        'nilai' => $nilai,
                        'is_terlambat' => $tugas->deadline ? now()->greaterThan($tugas->deadline) : false,
                    ]
                );
            }
        }
    }
}
