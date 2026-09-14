<?php

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('guru dapat membuat tugas', function () {
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $guru = Guru::firstOrFail();
    $guru->user->assignRole('guru');

    $response = $this->actingAs($guru->user)->post(route('admin.tugas.store'), [
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas Uji',
        'deskripsi' => 'Kerjakan',
        'deadline' => now()->addDays(2)->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect(route('admin.tugas.index'));
    expect(Tugas::where('judul', 'Tugas Uji')->exists())->toBeTrue();
});

test('siswa dapat mengumpulkan tugas dan terlambat ditandai', function () {
    Storage::fake('public');
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $tugas = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Tugas Uji',
        'deadline' => now()->subDay(),
        'is_aktif' => true,
    ]);

    $user = User::factory()->create(['username' => 'siswa-tugas-uji']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9001',
        'nisn' => '008099003',
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);
    RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);

    $this->actingAs($user)->post(route('siswa.tugas.kumpul', $tugas), [
        'jawaban_text' => 'Jawaban saya',
        'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('siswa.tugas.show', $tugas));

    $kumpul = PengumpulanTugas::where('tugas_id', $tugas->id)->where('siswa_id', $siswa->id)->first();
    expect($kumpul)->not->toBeNull()->and($kumpul->is_terlambat)->toBeTrue();
    Storage::disk('public')->assertExists($kumpul->file_path);
});

test('guru dapat memberi nilai', function () {
    $rombel = Rombel::firstOrFail();
    $mapel = MataPelajaran::aktif()->firstOrFail();
    $tugas = Tugas::create(['rombel_id' => $rombel->id, 'mata_pelajaran_id' => $mapel->id, 'judul' => 'Tugas Uji', 'is_aktif' => true]);
    $user = User::factory()->create(['username' => 'siswa-nilai-uji']);
    $user->assignRole('siswa');
    $siswa = Siswa::create(['user_id' => $user->id, 'nis' => '9002', 'nisn' => '008099004', 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'kelas_id' => $rombel->kelas_id, 'is_aktif' => true]);
    RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);
    $kumpul = PengumpulanTugas::create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'jawaban_text' => 'Jawab']);

    $guru = Guru::firstOrFail();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->post(route('admin.tugas.nilai.simpan', $tugas), [
        'nilai' => [$kumpul->id => 85],
        'catatan_guru' => [$kumpul->id => 'Bagus'],
    ])->assertRedirect(route('admin.tugas.show', $tugas));

    expect($kumpul->fresh()->nilai)->toBe(85);
});

test('tugas tanpa judul ditolak', function () {
    $guru = Guru::firstOrFail();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->post(route('admin.tugas.store'), [
        'rombel_id' => Rombel::firstOrFail()->id,
        'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
        'judul' => '',
    ])->assertSessionHasErrors('judul');
});
