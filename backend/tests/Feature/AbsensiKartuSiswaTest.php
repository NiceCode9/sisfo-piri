<?php

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NotifikasiLog;
use App\Models\Pengampu;
use App\Models\Pengaturan;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TokenKartuSiswa;
use Carbon\Carbon;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

if (! function_exists('rombelAbsensiKartu')) {
    function rombelAbsensiKartu(string $kelas = '7A'): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaAbsensiKartu')) {
    function siswaAbsensiKartu(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= rombelAbsensiKartu();

        $user = User::factory()->create(['username' => 'siswa-kartu-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008040%04d', $n),
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'kelas_id' => $rombel->kelas_id,
            'is_aktif' => true,
        ]);

        RiwayatKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $rombel->kelas_id,
            'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
            'status' => 'aktif',
        ]);

        return $siswa;
    }
}

if (! function_exists('guruAbsensiKartu')) {
    function guruAbsensiKartu(?Rombel $wali = null, ?Rombel $ampu = null, string $nama = 'Guru Absensi'): User
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create(['username' => 'guru-kartu-'.$n, 'name' => $nama]);
        $user->assignRole('guru');

        $guru = Guru::create([
            'user_id' => $user->id,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'is_aktif' => true,
        ]);

        $wali?->update(['wali_guru_id' => $guru->id]);

        if ($ampu !== null) {
            Pengampu::create([
                'guru_id' => $guru->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
                'rombel_id' => $ampu->id,
            ]);
        }

        return $user;
    }
}

if (! function_exists('guruTanpaKelasAbsensi')) {
    function guruTanpaKelasAbsensi(): User
    {
        return guruAbsensiKartu(nama: 'Guru Tanpa Kelas');
    }
}

if (! function_exists('jamAbsensiKartu')) {
    function jamAbsensiKartu(int $jam, int $menit): void
    {
        test()->travelTo(now()->startOfDay()->setTime($jam, $menit));
    }
}

test('rombel aktif siswa dibaca dari riwayat kelas, bukan kelas_id', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7001', 'Anak Riwayat', $rombel);

    $kelasLain = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $siswa->update(['kelas_id' => $kelasLain->id]);

    expect(Rombel::aktifUntukSiswa($siswa)?->id)->toBe($rombel->id);

    $siswa->riwayatKelas()->update(['status' => 'pindah']);

    expect(Rombel::aktifUntukSiswa($siswa))->toBeNull();
});

test('token kartu siswa hanya berlaku untuk id yang ditandatangani', function () {
    $siswa = siswaAbsensiKartu('7002', 'Anak Token');
    $token = TokenKartuSiswa::generate($siswa->id);

    expect(TokenKartuSiswa::baca($token))->toBe($siswa->id)
        ->and(TokenKartuSiswa::baca($token.'x'))->toBeNull()
        ->and(TokenKartuSiswa::baca('abskartu|'.$siswa->id.'|palsu'))->toBeNull()
        ->and(TokenKartuSiswa::baca('bukan-token'))->toBeNull();
});

test('kartu pelajar memuat QR token siswa', function () {
    $siswa = siswaAbsensiKartu('7003', 'Anak Kartu');

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('admin.siswas.kartu', $siswa))
        ->assertOk()
        ->assertSee('id="qr-kartu"', false)
        ->assertSee('<svg', false);
});

test('wali kelas bisa scan kartu siswa di kelasnya', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7101', 'Anak Wali', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    jamAbsensiKartu(7, 10);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertOk()->assertJson(['status' => 'hadir']);

    $baris = Absensi::where(['siswa_id' => $siswa->id, 'rombel_id' => $rombel->id])->firstOrFail();

    expect($baris->metode)->toBe('qr')
        ->and($baris->dicatat_oleh)->toBe($guru->id)
        ->and(substr((string) $baris->jam_datang, 0, 5))->toBe('07:10');
});

test('pengampu mapel bisa scan kartu siswa di kelas yang diampu', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7102', 'Anak Ampu', $rombel);
    $guru = guruAbsensiKartu(ampu: $rombel);

    jamAbsensiKartu(7, 10);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertOk();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('guru di luar kelas siswa ditolak', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7103', 'Anak Luar', $rombel);
    $guru = guruTanpaKelasAbsensi();

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertForbidden();

    expect(Absensi::count())->toBe(0);
});

test('scan sebelum buka dan setelah tutup ditolak', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7104', 'Anak Jendela', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    jamAbsensiKartu(6, 59);
    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertStatus(422);

    jamAbsensiKartu(7, 46);
    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertStatus(422);

    expect(Absensi::count())->toBe(0);
});

test('scan lewat batas terlambat menghasilkan terlambat', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7105', 'Anak Telat', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    jamAbsensiKartu(7, 20);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertOk()->assertJson(['status' => 'terlambat']);
});

test('scan ulang tidak mengubah jam datang pertama', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7106', 'Anak Ulang', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);
    $kode = TokenKartuSiswa::generate($siswa->id);

    jamAbsensiKartu(7, 10);
    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), ['kode' => $kode])->assertOk();

    jamAbsensiKartu(7, 20);
    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), ['kode' => $kode])->assertOk();

    $baris = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($baris->status)->toBe('hadir')
        ->and(substr((string) $baris->jam_datang, 0, 5))->toBe('07:10');
});

test('scan tidak menimpa baris manual', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7107', 'Anak Izin', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    Absensi::create([
        'siswa_id' => $siswa->id,
        'rombel_id' => $rombel->id,
        'tanggal' => today()->toDateString(),
        'status' => 'izin',
        'metode' => 'manual',
        'dicatat_oleh' => $guru->id,
    ]);

    jamAbsensiKartu(7, 10);
    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertOk();

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('izin');
});

test('scan menerima NISN sebagai fallback', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7109', 'Anak NISN', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    jamAbsensiKartu(7, 10);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => $siswa->nisn,
    ])->assertOk();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('scan menolak token rusak dan siswa nonaktif', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7110', 'Anak Nonaktif', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => 'abskartu|'.$siswa->id.'|palsu',
    ])->assertStatus(422);

    $siswa->update(['is_aktif' => false]);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertStatus(422);

    expect(Absensi::count())->toBe(0);
});

test('halaman guru menampilkan kelas terakhir dan daftar hari ini', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7108', 'Anak Panel', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    jamAbsensiKartu(7, 10);

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertOk();

    $this->actingAs($guru)->get(route('guru.absensi'))
        ->assertOk()
        ->assertSee('Anak Panel')
        ->assertSee('Belum tercatat')
        ->assertSee('Hadir');
});

test('balapan pada unique tidak berakhir sebagai 500', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7111', 'Anak Balapan', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);
    $this->travelTo(now()->startOfDay()->setTime(7, 10));
    $payload = ['kode' => TokenKartuSiswa::generate($siswa->id)];

    $balapan = false;

    Absensi::creating(function () use (&$balapan, $rombel, $siswa) {
        if ($balapan) {
            return;
        }

        $balapan = true;

        DB::table('absensis')->insert([
            'rombel_id' => $rombel->id,
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_datang' => '06:30:00',
            'metode' => 'qr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), $payload)
        ->assertOk()
        ->assertJson(['status' => 'hadir']);

    expect(Absensi::where('siswa_id', $siswa->id)->count())->toBe(1);

    $absensi = Absensi::where('siswa_id', $siswa->id)->firstOrFail();
    expect(substr((string) $absensi->jam_datang, 0, 5))->toBe('06:30');

    Absensi::flushEventListeners();
});

test('scan tengah malam ditolak karena jendela belum dibuka', function () {
    $this->travelTo(Carbon::parse('2026-10-09 17:30:00', 'UTC'));

    expect(now()->toDateString())->toBe('2026-10-10');

    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7112', 'Anak Tengah Malam', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $response = $this->actingAs($guru)->postJson(route('guru.absensi.scan'), [
        'kode' => TokenKartuSiswa::generate($siswa->id),
    ])->assertUnprocessable();

    expect($response->json('message'))->toContain('belum dibuka')
        ->and(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('scan dibatasi rate limit', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7113', 'Anak Rate Limit', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $this->travelTo(now()->startOfDay()->setTime(7, 10));
    $payload = ['kode' => TokenKartuSiswa::generate($siswa->id)];

    for ($i = 0; $i < 120; $i++) {
        $this->actingAs($guru)->postJson(route('guru.absensi.scan'), $payload)->assertOk();
    }

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), $payload)->assertStatus(429);
});

test('batas terlambat dibaca dari cache dan dibuang saat pengaturan disimpan', function () {
    Cache::flush();
    Pengaturan::flushCache();

    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7114', 'Anak Cache', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $this->travelTo(now()->startOfDay()->setTime(7, 10));
    $payload = ['kode' => TokenKartuSiswa::generate($siswa->id)];

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), $payload)
        ->assertOk()->assertJson(['status' => 'hadir']);

    $query = 0;
    DB::listen(function ($q) use (&$query) {
        if (str_contains($q->sql, 'pengaturans') && in_array('batas_terlambat', (array) $q->bindings, true)) {
            $query++;
        }
    });

    $this->actingAs($guru)->postJson(route('guru.absensi.scan'), $payload)->assertOk();

    expect($query)->toBe(0);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)->put(route('admin.pengaturans.update'), [
        'jam_buka_absen' => '06:45',
        'batas_terlambat' => '07:00',
    ])->assertSessionHas('success');

    expect(Pengaturan::nilai('batas_terlambat'))->toBe('07:00');
});
test('wali kelas bisa mencatat manual di kelasnya', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7201', 'Anak Manual', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $this->actingAs($guru)->postJson(route('guru.absensi.manual'), [
        'siswa_id' => $siswa->id,
        'status' => 'hadir',
    ])->assertOk();

    $baris = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($baris->metode)->toBe('manual')
        ->and($baris->dicatat_oleh)->toBe($guru->id)
        ->and($baris->jam_datang)->not->toBeNull();
});

test('manual izin tidak mengisi jam datang', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7202', 'Anak Manual Izin', $rombel);
    $guru = guruAbsensiKartu(ampu: $rombel);

    $this->actingAs($guru)->postJson(route('guru.absensi.manual'), [
        'siswa_id' => $siswa->id,
        'status' => 'izin',
    ])->assertOk();

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->jam_datang)->toBeNull();
});

test('manual menolak baris yang sudah tercatat', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7203', 'Anak Sudah', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    Absensi::create([
        'siswa_id' => $siswa->id,
        'rombel_id' => $rombel->id,
        'tanggal' => today()->toDateString(),
        'status' => 'hadir',
        'metode' => 'qr',
        'dicatat_oleh' => $guru->id,
    ]);

    $this->actingAs($guru)->postJson(route('guru.absensi.manual'), [
        'siswa_id' => $siswa->id,
        'status' => 'izin',
    ])->assertStatus(422);

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir');
});

test('manual di luar kelas ditolak', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7204', 'Anak Manual Luar', $rombel);
    $guru = guruTanpaKelasAbsensi();

    $this->actingAs($guru)->postJson(route('guru.absensi.manual'), [
        'siswa_id' => $siswa->id,
        'status' => 'hadir',
    ])->assertForbidden();

    expect(Absensi::count())->toBe(0);
});

test('manual alpa mengantrekan notifikasi WhatsApp', function () {
    Queue::fake();

    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7205', 'Anak Alpa', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $siswa->waliMurids()->create([
        'user_id' => User::factory()->create()->id,
        'no_whatsapp' => '081234567890',
    ]);

    $this->actingAs($guru)->postJson(route('guru.absensi.manual'), [
        'siswa_id' => $siswa->id,
        'status' => 'alpa',
    ])->assertOk();

    Queue::assertPushed(KirimNotifikasiWhatsapp::class);
    expect(NotifikasiLog::where('tipe', 'whatsapp')->exists())->toBeTrue();
});
test('cari hanya mengembalikan siswa dari kelas wali dan pengampu', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7301', 'Anak Cari', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);
    $lain = siswaAbsensiKartu('7302', 'Anak Kelas Lain', rombelAbsensiKartu('7B'));

    $this->actingAs($guru)->get(route('guru.absensi.cari', ['q' => 'Anak']))
        ->assertOk()
        ->assertSee('Anak Cari')
        ->assertDontSee('Anak Kelas Lain');
});

test('pilih membuka kelas siswa dan menyimpan session', function () {
    $rombel = rombelAbsensiKartu();
    $siswa = siswaAbsensiKartu('7303', 'Anak Pilih', $rombel);
    $guru = guruAbsensiKartu(wali: $rombel);

    $this->actingAs($guru)->post(route('guru.absensi.pilih'), [
        'siswa_id' => $siswa->id,
    ])->assertRedirect(route('guru.absensi', ['siswa_id' => $siswa->id, 'mode' => 'semua']));

    expect(session('absensi_rombel_id'))->toBe($rombel->id);

    $this->actingAs($guru)->get(route('guru.absensi', ['siswa_id' => $siswa->id, 'mode' => 'semua']))
        ->assertOk()
        ->assertSee('Anak Pilih')
        ->assertSee('data-terpilih="1"', false);
});

test('pilih siswa di luar jangkauan ditolak', function () {
    $siswa = siswaAbsensiKartu('7304', 'Anak Pilih Luar', rombelAbsensiKartu('7B'));
    $guru = guruAbsensiKartu(wali: rombelAbsensiKartu());

    $this->actingAs($guru)->post(route('guru.absensi.pilih'), [
        'siswa_id' => $siswa->id,
    ])->assertForbidden();
});
