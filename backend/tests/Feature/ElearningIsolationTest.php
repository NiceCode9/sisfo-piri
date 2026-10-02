<?php

use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliMurid;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);

    Storage::fake(Materi::DISK);
    Storage::fake(PengumpulanTugas::DISK);
});

/*
 * AkademikSeeder membentuk: Guru A = wali 7A + pengampu MTK di 7A/7B,
 * Guru D = wali 7C + pengampu MTK di 7C/7D. 7B dipakai sebagai kasus
 * "guru mencapai rombel karena mengampu mapel, bukan karena wali".
 */
function rombelKelas(string $nama): Rombel
{
    return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $nama))->firstOrFail();
}

function guruA(): Guru
{
    return Guru::where('nama', 'Guru A')->firstOrFail();
}

function guruD(): Guru
{
    return Guru::where('nama', 'Guru D')->firstOrFail();
}

/**
 * @return array{Siswa, User}
 */
function buatSiswa(Rombel $rombel, string $kode): array
{
    // Nama user dibuat eksplisit supaya pengujian "tidak boleh melihat nama
    // teman sekelas" tidak bisa lolos kebetulan karena nama factory sama.
    $user = User::factory()->create([
        'username' => "siswa-{$kode}",
        'name' => "Murid {$kode}",
    ]);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '95'.$kode,
        'nisn' => '0080995'.$kode,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
        'no_hp_orang_tua' => '0812000'.$kode,
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    return [$siswa, $user];
}

function buatTugas(Rombel $rombel, array $extra = []): Tugas
{
    return Tugas::create(array_merge([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
        'judul' => 'Tugas Uji Isolasi',
        'is_aktif' => true,
    ], $extra));
}

function buatMateri(Rombel $rombel, array $extra = []): Materi
{
    return Materi::create(array_merge([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
        'judul' => 'Materi Uji Isolasi',
        'tipe' => 'dokumen',
        'is_aktif' => true,
    ], $extra));
}

function simpanBerkas(string $path): string
{
    Storage::disk(Materi::DISK)->put($path, 'isi berkas');

    return $path;
}

/*
 * Temuan 1 (kritis): halaman tugas milik orang tua pernah memuat seluruh
 * pengumpulan rombel, sehingga nama, berkas, nilai, dan catatan guru milik
 * teman sekelas ikut tampil.
 */
test('orang tua hanya melihat pengumpulan anak sendiri', function () {
    $rombel = rombelKelas('7A');
    [$anak] = buatSiswa($rombel, '01');
    [$teman] = buatSiswa($rombel, '02');
    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;

    $tugas = buatTugas($rombel);

    PengumpulanTugas::create([
        'tugas_id' => $tugas->id,
        'siswa_id' => $anak->id,
        'nilai' => 80,
        'catatan_guru' => 'Catatan untuk anak',
    ]);
    PengumpulanTugas::create([
        'tugas_id' => $tugas->id,
        'siswa_id' => $teman->id,
        'nilai' => 95,
        'catatan_guru' => 'Catatan rahasia untuk teman',
    ]);

    $this->actingAs($ortu)->get(route('ortu.tugas.show', $tugas))
        ->assertOk()
        ->assertSee('Nilai: 80')
        ->assertSee('Catatan untuk anak')
        ->assertDontSee('Nilai: 95')
        ->assertDontSee($teman->user->name)
        ->assertDontSee('Catatan rahasia untuk teman');
});

/*
 * Temuan 4: cakupan rombel dulu hanya mashed dari wali kelas, lalu jatuh ke
 * "semua rombel" saat kosong. Guru yang mengampu mapel tapi bukan wali
 * kehilangan aksesnya, dan guru tanpa penugasan mendapat semua.
 */
test('guru mencapai rombel yang diampu mapel walau bukan wali', function () {
    $tugas = buatTugas(rombelKelas('7B'));
    $guru = guruA();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)
        ->get(route('admin.tugas.show', $tugas))
        ->assertOk();
});

test('guru ditolak pada rombel milik guru lain', function () {
    $tugas = buatTugas(rombelKelas('7C'));
    $guru = guruA();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.tugas.show', $tugas))->assertForbidden();
    $this->actingAs($guru->user)->get(route('admin.tugas.edit', $tugas))->assertForbidden();
    $this->actingAs($guru->user)->get(route('admin.tugas.nilai', $tugas))->assertForbidden();

    $this->actingAs($guru->user)->put(route('admin.tugas.update', $tugas), [
        'rombel_id' => $tugas->rombel_id,
        'mata_pelajaran_id' => $tugas->mata_pelajaran_id,
        'judul' => 'Dibajak',
    ])->assertForbidden();

    $this->actingAs($guru->user)->delete(route('admin.tugas.destroy', $tugas))->assertForbidden();

    expect($tugas->fresh()->judul)->toBe('Tugas Uji Isolasi');
});

test('guru tanpa penugasan tidak melihat rombel siapa pun', function () {
    $tugas = buatTugas(rombelKelas('7A'));

    $user = User::factory()->create(['username' => 'guru-tanpa-kelas']);
    $user->assignRole('guru');
    Guru::create([
        'user_id' => $user->id,
        'nip' => '196912121998121003',
        'nama' => 'Guru Tanpa Kelas',
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);

    $this->actingAs($user)->get(route('admin.tugas.index'))->assertOk()->assertDontSee($tugas->judul);
    $this->actingAs($user)->get(route('admin.tugas.show', $tugas))->assertForbidden();
    $this->actingAs($user)->get(route('admin.tugas.rekap'))->assertOk();
});

test('daftar tugas dan materi dibatasi rombel yang boleh diakses', function () {
    $milikSaya = buatTugas(rombelKelas('7A'), ['judul' => 'Tugas Milik Guru A']);
    $milikDia = buatTugas(rombelKelas('7D'), ['judul' => 'Tugas Milik Guru D']);
    $materiSaya = buatMateri(rombelKelas('7B'), ['judul' => 'Materi Milik Guru A']);
    $materiDia = buatMateri(rombelKelas('7D'), ['judul' => 'Materi Milik Guru D']);

    $guru = guruA();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.tugas.index'))
        ->assertOk()
        ->assertSee($milikSaya->judul)
        ->assertDontSee($milikDia->judul);

    $this->actingAs($guru->user)->get(route('admin.materis.index'))
        ->assertOk()
        ->assertSee($materiSaya->judul)
        ->assertDontSee($materiDia->judul);
});

/*
 * Temuan 5: is_aktif hanya dijaga di halaman index, jadi tugas/materi yang
 * sudah di-nonaktifkan masih bisa dibuka dan dikumpulkan lewat URL.
 */
test('siswa tidak dapat membuka atau mengumpulkan tugas non-aktif', function () {
    $rombel = rombelKelas('7A');
    [$siswa, $user] = buatSiswa($rombel, '11');
    $tugas = buatTugas($rombel, ['is_aktif' => false]);

    $this->actingAs($user)->get(route('siswa.tugas.show', $tugas))->assertNotFound();
    $this->actingAs($user)->get(route('siswa.tugas.index'))->assertOk()->assertDontSee($tugas->judul);

    $this->actingAs($user)->post(route('siswa.tugas.kumpul', $tugas), [
        'jawaban_text' => 'Menyusup',
    ])->assertNotFound();

    expect(PengumpulanTugas::where('tugas_id', $tugas->id)->exists())->toBeFalse();
});

test('orang tua tidak dapat membuka materi non-aktif', function () {
    $rombel = rombelKelas('7A');
    [$anak] = buatSiswa($rombel, '12');
    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;
    $materi = buatMateri($rombel, ['is_aktif' => false]);

    $this->actingAs($ortu)->get(route('ortu.materi.show', $materi))->assertNotFound();
});

/*
 * Temuan 2 (kritis): berkas E-Learning dulu ditulis ke disk `public`, jadi
 * URL /storage/... bisa diambil tanpa login.
 */
test('berkas tugas hanya dapat diunduh oleh pemilik, wali, atau guru pengampu', function () {
    $rombel = rombelKelas('7A');
    [$anak, $userAnak] = buatSiswa($rombel, '21');
    [$teman, $userTeman] = buatSiswa($rombel, '22');
    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;
    $ortuLain = WaliMurid::where('siswa_id', $teman->id)->firstOrFail()->user;

    $tugas = buatTugas($rombel);
    $path = simpanBerkas('tugas/jawaban.pdf');
    $pengumpulan = PengumpulanTugas::create([
        'tugas_id' => $tugas->id,
        'siswa_id' => $anak->id,
        'file_path' => $path,
    ]);

    $this->actingAs($userAnak)->get(route('elearning.pengumpulan.berkas', $pengumpulan))->assertOk();
    $this->actingAs($ortu)->get(route('elearning.pengumpulan.berkas', $pengumpulan))->assertOk();

    $this->actingAs($userTeman)->get(route('elearning.pengumpulan.berkas', $pengumpulan))->assertForbidden();
    $this->actingAs($ortuLain)->get(route('elearning.pengumpulan.berkas', $pengumpulan))->assertForbidden();

    Storage::disk(PengumpulanTugas::DISK)->assertExists($path);
});

test('tamu tidak dapat mengunduh berkas tugas', function () {
    $rombel = rombelKelas('7A');
    [$anak] = buatSiswa($rombel, '23');
    $tugas = buatTugas($rombel);
    $pengumpulan = PengumpulanTugas::create([
        'tugas_id' => $tugas->id,
        'siswa_id' => $anak->id,
        'file_path' => simpanBerkas('tugas/tamu.pdf'),
    ]);

    // Diperiksa sebelum `actingAs` apa pun: `actingAs` menetap untuk sisa
    // test, sehingga pemeriksaan tamu harus benar-benar tanpa sesi.
    $this->get(route('elearning.pengumpulan.berkas', $pengumpulan))->assertRedirect(route('login'));
});

test('halaman tugas tidak lagi menautkan storage publik', function () {
    $rombel = rombelKelas('7A');
    [$anak] = buatSiswa($rombel, '31');
    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;
    $tugas = buatTugas($rombel);
    $path = simpanBerkas('tugas/jawaban.pdf');
    PengumpulanTugas::create(['tugas_id' => $tugas->id, 'siswa_id' => $anak->id, 'file_path' => $path]);

    $html = $this->actingAs($ortu)->get(route('ortu.tugas.show', $tugas))->assertOk()->getContent();

    expect($html)->not->toContain('/storage/');
    expect($html)->toContain(route('elearning.pengumpulan.berkas', $pengumpulan ?? PengumpulanTugas::where('tugas_id', $tugas->id)->firstOrFail()));
});

test('berkas materi hanya dapat diunduh pihak yang berhak', function () {
    $rombel = rombelKelas('7A');
    [$siswa, $userSiswa] = buatSiswa($rombel, '41');
    [$orangLain, $userOrangLain] = buatSiswa(rombelKelas('7D'), '42');
    $materi = buatMateri($rombel, ['file_path' => simpanBerkas('materi/materi.pdf')]);

    $this->actingAs($userSiswa)->get(route('elearning.materi.berkas', $materi))->assertOk();
    $this->actingAs($userOrangLain)->get(route('elearning.materi.berkas', $materi))->assertForbidden();

    $guru = guruA();
    $guru->user->assignRole('guru');
    $this->actingAs($guru->user)->get(route('elearning.materi.berkas', $materi))->assertOk();
});

/*
 * Temuan 6: materi yang dibuat admin tidak punya baris `gurus`, sehingga
 * guru_id kosong. Ini perilaku yang disengaja, bukan bug yang perlu
 * "diperbaiki" dengan mengisi wali rombel.
 */
test('materi oleh admin tidak dikaitkan ke guru, oleh guru memakai dirinya sendiri', function () {
    $rombel = rombelKelas('7A');
    $mapel = MataPelajaran::aktif()->firstOrFail();

    $admin = User::factory()->create(['username' => 'admin-elearning']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('materis.create', 'materis.view');

    $this->actingAs($admin)->post(route('admin.materis.store'), [
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Materi Oleh Admin',
        'tipe' => 'link',
        'url' => 'https://example.com',
    ])->assertRedirect(route('admin.materis.index'));

    expect(Materi::where('judul', 'Materi Oleh Admin')->firstOrFail()->guru_id)->toBeNull();

    $guru = guruA();
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->post(route('admin.materis.store'), [
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'judul' => 'Materi Oleh Guru',
        'tipe' => 'link',
        'url' => 'https://example.com',
    ])->assertRedirect(route('admin.materis.index'));

    expect(Materi::where('judul', 'Materi Oleh Guru')->firstOrFail()->guru_id)->toBe($guru->id);
});

/*
 * Temuan 7: unggah 10 MB tanpa throttle.
 */
test('unggah tugas dibatasi throttle', function () {
    $rombel = rombelKelas('7A');
    [$siswa, $user] = buatSiswa($rombel, '51');
    $tugas = buatTugas($rombel);

    for ($i = 0; $i < 12; $i++) {
        $this->actingAs($user)->post(route('siswa.tugas.kumpul', $tugas), [
            'jawaban_text' => "Percobaan {$i}",
        ])->assertRedirect();
    }

    $this->actingAs($user)->post(route('siswa.tugas.kumpul', $tugas), [
        'jawaban_text' => 'Melewati batas',
    ])->assertStatus(429);
});

/*
 * Temuan 9: area orang tua tidak punya tab navigasi sama sekali, sehingga
 * halaman materi dan tugas tidak bisa dijangkau.
 */
test('area orang tua punya navigasi menuju materi dan tugas', function () {
    $rombel = rombelKelas('7A');
    [$anak] = buatSiswa($rombel, '61');
    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;

    $this->actingAs($ortu)->get(route('ortu.dashboard'))
        ->assertOk()
        ->assertSee(route('ortu.materi.index'))
        ->assertSee(route('ortu.tugas.index'));
});
