<?php

/**
 * Dashboard siswa.
 *
 * Dua hal yang dijaga di sini. Pertama, angka ringkasan harus sama dengan
 * halaman Riwayat karena keduanya membaca `RiwayatSiswa::ringkas()`. Kedua,
 * akun yang belum jadi siswa tidak boleh 500 — pendaftar masih membuka
 * dashboard ini lewat RuteBeranda.
 */

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

/**
 * Siswa di kelas 7A dengan RiwayatKelas aktif — tanpa itu
 * `Rombel::untukSiswa()` kosong dan dashboard tidak akan menghitung apa pun.
 *
 * @return array{0: User, 1: Siswa}
 */
function siswaDasbor(?string $kelasNama = '7A', string $nis = '5301'): array
{
    $kelas = Kelas::where('nama_kelas', $kelasNama)->firstOrFail();
    $tahun = TahunAjaran::aktif()->first();

    $user = User::factory()->create(['username' => 'siswa-dash-'.$nis, 'name' => 'Murid '.$nis]);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => $nis,
        'nisn' => '0099'.$nis,
        'tahun_ajaran_id' => $tahun->id,
        'kelas_id' => $kelas->id,
        'is_aktif' => true,
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $kelas->id,
        'tahun_ajaran_id' => $tahun->id,
        'status' => 'aktif',
    ]);

    return [$user, $siswa];
}

test('dashboard siswa terbuka dan menampilkan ringkasan', function () {
    [$user] = siswaDasbor();

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Kehadiran')
        ->assertSee('Tugas Belum Dikerjakan')
        ->assertSee('Kelas Diikuti');
});

test('halaman pendaftaran terpisah dan memuat isi PPDB', function () {
    [$user] = siswaDasbor();

    $this->actingAs($user)
        ->get(route('siswa.pendaftaran'))
        ->assertOk()
        ->assertSee('Pendaftaran');
});

test('akun tanpa baris siswa mendapat empty state, bukan error', function () {
    // Pendaftar yang belum ditempatkan tetap membuka dashboard lewat
    // RuteBeranda. Kalau `RiwayatSiswa::untukSiswa(null)` tidak dijaga, ini 500.
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Belum ada data akademik')
        ->assertSee(route('siswa.pendaftaran'), false);
});

test('tugas yang belum dikumpulkan tampil, yang sudah tidak', function () {
    [$user, $siswa] = siswaDasbor();
    $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
        ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
        ->firstOrFail();
    $mapel = MataPelajaran::factory()->create();

    $belum = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas Belum Dikerjakan',
        'is_aktif' => true,
    ]);

    $sudah = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas Sudah Dikerjakan',
        'is_aktif' => true,
    ]);

    PengumpulanTugas::create([
        'tugas_id' => $sudah->id,
        'siswa_id' => $siswa->id,
        'nilai' => 85,
    ]);

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Tugas Belum Dikerjakan')
        ->assertDontSee('Tugas Sudah Dikerjakan');
});

test('pengumuman aktif tampil, yang nonaktif tidak', function () {
    [$user, $siswa] = siswaDasbor();

    Pengumuman::create([
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'judul' => 'Pengumuman Aktif Untuk Murid',
        'isi' => 'Isi pengumuman.',
        'tanggal_pengumuman' => now(),
        'status_aktif' => true,
    ]);

    Pengumuman::create([
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'judul' => 'Pengumuman Nonaktif',
        'isi' => 'Jangan tampil.',
        'tanggal_pengumuman' => now(),
        'status_aktif' => false,
    ]);

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Pengumuman Aktif Untuk Murid')
        ->assertDontSee('Pengumuman Nonaktif');
});

test('ujian published yang belum diambil tampil di dashboard', function () {
    [$user, $siswa] = siswaDasbor();
    $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
        ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
        ->firstOrFail();

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Exam::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'name' => 'Ujian Menunggu Murid',
        'duration_minutes' => 60,
        'max_violation_count' => 3,
        'status' => 'published',
        'available_until' => now()->addDays(3),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertSee('Ujian CBT Tersedia')
        ->assertSee('Ujian Menunggu Murid');
});

test('ujian yang sudah dikerjakan atau belum dipublikasikan tidak tampil', function () {
    [$user, $siswa] = siswaDasbor();
    $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
        ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
        ->firstOrFail();
    $mapelId = MataPelajaran::factory()->create()->id;

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $draft = Exam::create([
        'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapelId,
        'name' => 'Ujian Masih Draft', 'duration_minutes' => 60,
        'max_violation_count' => 3, 'status' => 'draft', 'created_by' => $admin->id,
    ]);

    $kedaluwarsa = Exam::create([
        'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapelId,
        'name' => 'Ujian Sudah Lewat', 'duration_minutes' => 60,
        'max_violation_count' => 3, 'status' => 'published',
        'available_until' => now()->subDay(), 'created_by' => $admin->id,
    ]);

    $sudahDikerjakan = Exam::create([
        'rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapelId,
        'name' => 'Ujian Sudah Dikerjakan', 'duration_minutes' => 60,
        'max_violation_count' => 3, 'status' => 'published',
        'available_until' => now()->addDays(3), 'created_by' => $admin->id,
    ]);

    // Sesi yang sudah ada berarti ujian itu sudah diambil.
    $token = ExamToken::create([
        'exam_id' => $sudahDikerjakan->id,
        'token' => 'TOKEN-'.uniqid(),
        'active_from' => now()->subHour(),
        'active_until' => now()->addHour(),
        'created_by' => $admin->id,
    ]);

    ExamSession::create([
        'exam_id' => $sudahDikerjakan->id,
        'exam_token_id' => $token->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'expected_end_at' => now()->addHour(),
        'status' => 'ongoing',
    ]);

    $html = $this->actingAs($user)->get(route('siswa.dashboard'))->assertOk()->getContent();

    // Tiga-dua jenis di bawah tidak boleh muncul di dashboard. Kalau filter
    // loosened dari gate join(), siswa akan melihat ujian yang ditolak begitu
    // ia memasukkan token.
    expect($html)->not->toContain('Ujian Masih Draft')
        ->and($html)->not->toContain('Ujian Sudah Lewat')
        ->and($html)->not->toContain('Ujian Sudah Dikerjakan');
});

test('ujian di kelas lain tidak muncul di dashboard siswa', function () {
    [$user] = siswaDasbor('7A', '5301');
    $rombelLain = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7B'))
        ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
        ->firstOrFail();

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Exam::create([
        'rombel_id' => $rombelLain->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'name' => 'Ujian Kelas Tetangga', 'duration_minutes' => 60,
        'max_violation_count' => 3, 'status' => 'published',
        'available_until' => now()->addDays(3), 'created_by' => $admin->id,
    ]);

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertDontSee('Ujian Kelas Tetangga');
});

test('tugas di kelas lain tidak dihitung sebagai tugas siswa', function () {
    [$user] = siswaDasbor('7A', '5301');
    $rombelLain = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7B'))
        ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
        ->firstOrFail();

    Tugas::create([
        'rombel_id' => $rombelLain->id,
        'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
        'judul' => 'Tugas Kelas Tetangga', 'is_aktif' => true,
    ]);

    $this->actingAs($user)
        ->get(route('siswa.dashboard'))
        ->assertOk()
        ->assertDontSee('Tugas Kelas Tetangga');
});

test('orang tua dan admin tidak bisa membuka dashboard siswa', function () {
    $ortu = User::factory()->create();
    $ortu->assignRole('orang-tua');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($ortu)->get(route('siswa.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('siswa.dashboard'))->assertForbidden();
});
