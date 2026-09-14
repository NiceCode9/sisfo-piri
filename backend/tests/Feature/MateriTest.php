<?php

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\User;
use App\Models\WaliMurid;
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

if (! function_exists('rombelMateriUji')) {
    function rombelMateriUji(): Rombel
    {
        return Rombel::with(['kelas', 'tahunAjaran'])->firstOrFail();
    }
}

if (! function_exists('mapelMateriUji')) {
    function mapelMateriUji(): MataPelajaran
    {
        return MataPelajaran::aktif()->firstOrFail();
    }
}

test('tamu tidak dapat membuka materi', function () {
    $this->get(route('admin.materis.index'))->assertRedirect(route('login'));
});

test('guru dapat menambah materi dokumen', function () {
    Storage::fake('public');
    $guru = Guru::firstOrFail();
    $user = $guru->user;
    $user->assignRole('guru');

    $response = $this->actingAs($user)->post(route('admin.materis.store'), [
        'rombel_id' => rombelMateriUji()->id,
        'mata_pelajaran_id' => mapelMateriUji()->id,
        'judul' => 'Materi Dokumen',
        'tipe' => 'dokumen',
        'file' => UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('admin.materis.index'));
    $materi = Materi::where('judul', 'Materi Dokumen')->first();
    expect($materi)->not->toBeNull()->and($materi->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($materi->file_path);
});

test('materi link tanpa url ditolak', function () {
    $guru = Guru::firstOrFail();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->post(route('admin.materis.store'), [
        'rombel_id' => rombelMateriUji()->id,
        'mata_pelajaran_id' => mapelMateriUji()->id,
        'judul' => 'Materi Link',
        'tipe' => 'link',
    ])->assertSessionHasErrors('url');
});

test('siswa hanya melihat materi rombel sendiri', function () {
    $rombel = rombelMateriUji();
    $user = User::factory()->create(['username' => 'siswa-materi-uji']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9001',
        'nisn' => '008099001',
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
    ]);
    RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);

    Materi::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => mapelMateriUji()->id,
        'judul' => 'Untuk Rombel Ini',
        'tipe' => 'link',
        'url' => 'https://example.com',
        'is_aktif' => true,
    ]);

    $this->actingAs($user)->get(route('siswa.materi.index'))
        ->assertOk()->assertSee('Untuk Rombel Ini');
});

test('ortu hanya melihat materi anak', function () {
    $rombel = rombelMateriUji();
    $user = User::factory()->create(['username' => 'siswa-ortu-materi']);
    $user->assignRole('siswa');
    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '9002',
        'nisn' => '008099002',
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
        'no_hp_orang_tua' => '081200009002',
    ]);
    RiwayatKelas::create(['siswa_id' => $siswa->id, 'kelas_id' => $rombel->kelas_id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);
    $ortu = WaliMurid::where('siswa_id', $siswa->id)->first()->user;

    Materi::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => mapelMateriUji()->id,
        'judul' => 'Untuk Anak',
        'tipe' => 'link',
        'url' => 'https://example.com',
        'is_aktif' => true,
    ]);

    $this->actingAs($ortu)->get(route('ortu.materi.index'))
        ->assertOk()->assertSee('Untuk Anak');
});
