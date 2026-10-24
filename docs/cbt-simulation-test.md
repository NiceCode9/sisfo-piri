# Panduan Simulasi Pengujian CBT (Fitur yang Baru Diperbaiki)

Dokumen ini berisi langkah simulasi dan data seed lengkap untuk menguji perbaikan CBT:
resume browser, sinkronisasi waktu, idempotency pelanggaran, retry autosave,
monitoring heartbeat stale, cookie HttpOnly, dan CSP.

## 1. Prasyarat

```bash
cd backend
php artisan migrate:fresh
php artisan db:seed
cd ../cbt
npm run dev
```

Backend di `http://127.0.0.1:8000`, frontend di `http://127.0.0.1:5173`.

## 2. Seeder Simulasi (data lengkap)

Buat file `backend/database/seeders/CbtSimulationSeeder.php` dengan isi berikut,
lalu jalankan:

```bash
php artisan db:seed --class=CbtSimulationSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamToken;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CbtSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // Tahun ajaran aktif
        $tahun = TahunAjaran::firstOrCreate(
            ['nama_tahun_ajaran' => '2026/2027'],
            ['tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status_aktif' => true]
        );

        // Kelas & rombel
        $kelas = \App\Models\Kelas::firstOrCreate(['nama_kelas' => 'X-RPL-1', 'tingkat' => '10']);
        $rombel = Rombel::firstOrCreate(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $tahun->id]);

        // Mata pelajaran
        $mapel = MataPelajaran::firstOrCreate(
            ['kode' => 'MTK'],
            ['nama' => 'Matematika', 'kelompok' => 'A']
        );

        // Guru
        $guruUser = User::firstOrCreate(
            ['email' => 'guru.simulasi@sekolah.test'],
            ['name' => 'Guru Simulasi', 'username' => 'guru_simulasi', 'password' => Hash::make('password')]
        );
        $guruUser->assignRole('guru');
        $guru = Guru::firstOrCreate(
            ['user_id' => $guruUser->id],
            ['nip' => 'SIM001', 'nama' => 'Guru Simulasi', 'jenis_kelamin' => 'L', 'is_aktif' => true]
        );
        Pengampu::firstOrCreate([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'rombel_id' => $rombel->id,
        ]);

        // Siswa (2 akun untuk uji multi-session)
        foreach (['siswa1', 'siswa2'] as $i => $name) {
            $u = User::firstOrCreate(
                ['email' => "{$name}.simulasi@sekolah.test"],
                ['name' => ucfirst($name).' Simulasi', 'username' => $name.'_simulasi', 'password' => Hash::make('password')]
            );
            $u->assignRole('siswa');
        }

        // Bank soal
        $bank = QuestionBank::firstOrCreate(
            ['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'nama' => 'Bank Simulasi MTK'],
            ['deskripsi' => 'Bank soal untuk simulasi CBT', 'is_shared' => true, 'created_by' => $guruUser->id]
        );

        // Soal bank (10 butir: 4 single, 3 multiple, 3 essay)
        $templates = [
            ['type' => 'single_choice', 'text' => 'Hasil dari 7 × 8 adalah?', 'options' => [['key'=>'A','text'=>'54'],['key'=>'B','text'=>'56'],['key'=>'C','text'=>'64'],['key'=>'D','text'=>'72']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Bentuk sederhana dari 2x + 3x adalah?', 'options' => [['key'=>'A','text'=>'5x'],['key'=>'B','text'=>'6x'],['key'=>'C','text'=>'5x²'],['key'=>'D','text'=>'6x²']], 'correct' => ['A'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Akar dari 144 adalah?', 'options' => [['key'=>'A','text'=>'11'],['key'=>'B','text'=>'12'],['key'=>'C','text'=>'13'],['key'=>'D','text'=>'14']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'single_choice', 'text' => 'Keliling persegi sisi 5 cm?', 'options' => [['key'=>'A','text'=>'15 cm'],['key'=>'B','text'=>'20 cm'],['key'=>'C','text'=>'25 cm'],['key'=>'D','text'=>'10 cm']], 'correct' => ['B'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Manakah bilangan prima? (pilih 2)', 'options' => [['key'=>'A','text'=>'2'],['key'=>'B','text'=>'9'],['key'=>'C','text'=>'13'],['key'=>'D','text'=>'15']], 'correct' => ['A','C'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Sifat komutatif berlaku untuk?', 'options' => [['key'=>'A','text'=>'Penjumlahan'],['key'=>'B','text'=>'Pengurangan'],['key'=>'C','text'=>'Perkalian'],['key'=>'D','text'=>'Pembagian']], 'correct' => ['A','C'], 'score' => 10],
            ['type' => 'multiple_choice', 'text' => 'Bangun datar dengan 4 sisi sama?', 'options' => [['key'=>'A','text'=>'Persegi'],['key'=>'B','text'=>'Segitiga'],['key'=>'C','text'=>'Belah ketupat'],['key'=>'D','text'=>'Lingkaran']], 'correct' => ['A','C'], 'score' => 10],
            ['type' => 'essay', 'text' => 'Jelaskan langkah menyelesaikan persamaan 2x + 5 = 15.', 'options' => null, 'correct' => null, 'score' => 15],
            ['type' => 'essay', 'text' => 'Jika harga awal Rp100.000 didiskon 20%, berapa harga akhir? Jelaskan.', 'options' => null, 'correct' => null, 'score' => 15],
            ['type' => 'essay', 'text' => 'Hitung luas persegi 8 cm x 8 cm dan jelaskan rumusnya.', 'options' => null, 'correct' => null, 'score' => 15],
        ];

        foreach ($templates as $order => $tpl) {
            ExamQuestion::firstOrCreate(
                ['question_bank_id' => $bank->id, 'question_text' => $tpl['text']],
                ['exam_id' => null, 'source_question_id' => null, 'question_image' => null, 'type' => $tpl['type'], 'options' => $tpl['options'], 'correct_answer' => $tpl['correct'], 'score' => $tpl['score'], 'order' => $order + 1]
            );
        }

        // Ujian (snapshot 10 soal dari bank)
        $exam = Exam::firstOrCreate(
            ['name' => 'Simulasi CBT MTK', 'rombel_id' => $rombel->id],
            ['mata_pelajaran_id' => $mapel->id, 'description' => 'Ujian simulasi untuk perbaikan CBT', 'duration_minutes' => 30, 'available_from' => now()->subDay(), 'available_until' => now()->addDays(7), 'max_violation_count' => 3, 'shuffle_questions' => true, 'shuffle_options' => true, 'status' => 'published', 'created_by' => $guruUser->id]
        );

        $bankQuestions = ExamQuestion::where('question_bank_id', $bank->id)->orderBy('order')->limit(10)->get();
        foreach ($bankQuestions as $bq) {
            ExamQuestion::firstOrCreate(
                ['exam_id' => $exam->id, 'source_question_id' => $bq->id],
                ['question_bank_id' => $bank->id, 'question_text' => $bq->question_text, 'question_image' => $bq->question_image, 'type' => $bq->type, 'options' => $bq->options, 'correct_answer' => $bq->correct_answer, 'score' => $bq->score, 'order' => $bq->order]
            );
        }

        // Token ujian
        ExamToken::firstOrCreate(
            ['exam_id' => $exam->id, 'token' => 'SIMCBT2026'],
            ['active_from' => now()->subHour(), 'active_until' => now()->addDays(7), 'max_usage' => null, 'is_active' => true, 'created_by' => $guruUser->id]
        );
    }
}
```

## 3. Akun uji

- Guru: `guru_simulasi` / `password`
- Siswa 1: `siswa1_simulasi` / `password`
- Siswa 2: `siswa2_simulasi` / `password`
- Token ujian: `SIMCBT2026`

## 4. Langkah simulasi perbaikan

### A. Cookie HttpOnly (tidak ada token di localStorage)
1. Login sebagai siswa di frontend.
2. Buka DevTools -> Application -> Local Storage.
3. Pastikan tidak ada key `cbt_auth_token`.
4. Buka tab Network, cek request `/api/cbt/exam/active` membawa cookie `cbt_auth_token`.

### B. Resume setelah browser ditutup
1. Siswa login, masuk token `SIMCBT2026`, mulai ujian.
2. Jawab 2-3 soal, tunggu status "Tersimpan otomatis".
3. Tutup browser/tab sepenuhnya.
4. Buka browser lagi, akses `http://127.0.0.1:5173`.
5. Siswa seharusnya langsung masuk ExamRoom tanpa diminta login/token lagi.
6. Jika waktu ujian sudah habis, siswa diarahkan ke `/finished?reason=time_up`.

### C. Sinkronisasi waktu (timer tidak ikut jam lokal)
1. Sebelum login, ubah jam OS laptop +10 menit.
2. Login dan mulai ujian.
3. Timer sisa waktu tetap mengikuti server (tidak bertambah 10 menit).
4. Kembalikan jam OS ke normal.

### D. Idempotency pelanggaran
1. Selama ujian, buka DevTools Console.
2. Jalankan berulang:
   ```js
   fetch('/api/cbt/exam/violation', {
     method: 'POST',
     credentials: 'include',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({ exam_session_id: 1, type: 'tab_blur', client_request_id: 'abc-123' })
   })
   ```
3. `violation_count` hanya bertambah sekali, meski request diulang dengan `client_request_id` yang sama.

### E. Retry autosave
1. Mulai ujian, jawab satu soal.
2. Matikan backend sejenak (stop `php artisan serve`) lalu jawab soal lain.
3. Status soal menjadi "Gagal menyimpan, mengulang...".
4. Hidupkan backend lagi; jawaban yang tertunda akan terkirim ulang otomatis.

### F. Monitoring heartbeat stale
1. Login sebagai guru, buka monitoring ujian.
2. Biarkan satu siswa tidak mengirim heartbeat > 60 detik (tutup tab tanpa selesai).
3. Kolom `is_stale` menjadi `true` dan summary `stale` bertambah.

### G. CSP
1. Buka frontend, cek Console.
2. Tidak boleh ada error CSP yang memblokir aset utama (script, style, font, gambar).
3. Coba inject script dari domain lain; harus diblokir CSP.

## 5. Cleanup

```bash
cd backend
php artisan migrate:fresh
php artisan db:seed
```

Gunakan akun dan token di atas untuk simulasi ulang.