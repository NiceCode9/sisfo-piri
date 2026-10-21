<?php

use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TokenQrAbsensi;
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

if (! function_exists('rombelQrMandiri')) {
    function rombelQrMandiri(): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }
}

if (! function_exists('siswaQrMandiri')) {
    function siswaQrMandiri(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= rombelQrMandiri();

        $user = User::factory()->create(['username' => 'siswa-qr-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008020%04d', $n),
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

if (! function_exists('guruPemilikQr')) {
    /** Akun guru yang QR-nya dipakai. Yang dihitung hanya id akunnya. */
    function guruPemilikQr(string $nama = 'Guru QR Mandiri'): User
    {
        $user = User::factory()->create(['username' => 'guru-qr-mandiri-'.str()->random(6), 'name' => $nama]);
        $user->assignRole('guru');

        return $user;
    }
}

/** Membekukan jam sekolah pada menit (opsional detik) tertentu hari ini. */
if (! function_exists('jamSekolahUji')) {
    function jamSekolahUji(int $jam, int $menit, int $detik = 0): void
    {
        test()->travelTo(now()->startOfDay()->setTime($jam, $menit, $detik));
    }
}

test('token slot sekarang dan satu slot sebelumnya diterima', function () {
    $siswa = siswaQrMandiri('4001', 'Anak Mandiri Satu');
    $guru = guruPemilikQr();

    jamSekolahUji(7, 7);
    $tokenLama = TokenQrAbsensi::generate($guru->id);

    // Guru menampilkan QR saat 07:07; siswa memindai 07:12, tepat setelah
    // slot berganti. Toleransi satu slot menutup kasus QR yang masih di
    // layar ketika pergantian terjadi.
    jamSekolahUji(7, 12);
    $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $tokenLama,
    ])->assertOk()->assertJson(['status' => 'hadir']);
});

test('token dua slot lama hangus', function () {
    $siswa = siswaQrMandiri('4002', 'Anak Mandiri Dua');
    $guru = guruPemilikQr();

    // Interval standar 10 menit: token 07:05 sudah dua slot lebih tua di
    // 07:25 — foto yang diteruskan ke teman sekelas memang harus mati.
    jamSekolahUji(7, 5);
    $tokenLama = TokenQrAbsensi::generate($guru->id);

    jamSekolahUji(7, 25);
    $response = $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $tokenLama,
    ])->assertUnprocessable();

    expect($response->json('message'))->toContain('QR tidak berlaku')
        ->and(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('QR hari ini mati esok dan QR esok berlaku esok', function () {
    $siswaA = siswaQrMandiri('4003', 'Anak Mandiri Tiga');
    $siswaB = siswaQrMandiri('4004', 'Anak Mandiri Empat');
    $guru = guruPemilikQr();

    jamSekolahUji(7, 10);
    $tokenHariIni = TokenQrAbsensi::generate($guru->id);

    $this->travelTo(now()->addDay()->setTime(7, 10));

    $response = $this->actingAs($siswaA->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $tokenHariIni,
    ])->assertUnprocessable();
    expect($response->json('message'))->toContain('QR tidak berlaku');

    // QR esok, diterbitkan esok, wajar berlaku — keberlakuannya mengikuti
    // hari generator, bukan tanggal berlakunya alur.
    $this->actingAs($siswaB->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate($guru->id),
    ])->assertOk()->assertJson(['status' => 'hadir']);
});

test('token yang ditaman ditolak', function () {
    $siswa = siswaQrMandiri('4005', 'Anak Mandiri Lima');
    $guru = guruPemilikQr();

    jamSekolahUji(7, 10);
    $bagianToken = explode('|', TokenQrAbsensi::generate($guru->id));

    // Tanda tangan diganti dulu, lalu penerima di token diganti: dua-duanya
    // menargetkan jalur cek berbeda dan tidak boleh lolos keduanya.
    $bagianToken[4] = str_repeat('0', 64);
    $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => implode('|', $bagianToken),
    ])->assertUnprocessable();

    $guruAsing = guruPemilikQr('Guru Implan');
    $bagianToken[4] = hash_hmac('sha256', $bagianToken[0].'|'.$bagianToken[1].'|'.$bagianToken[2].'|'.$bagianToken[3], (string) config('app.key'));
    $bagianToken[1] = (string) $guruAsing->id;
    $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => implode('|', $bagianToken),
    ])->assertUnprocessable();

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('jendela sebelum buka ditolak dan jam buka tepat diterima', function () {
    $siswaA = siswaQrMandiri('4006', 'Anak Mandiri Enam');
    $siswaB = siswaQrMandiri('4007', 'Anak Mandiri Tujuh');
    $guru = guruPemilikQr();

    // Token sah — seluruh penolakan datang dari jendela, bukan dari token.
    jamSekolahUji(6, 59, 59);
    $tokenDini = TokenQrAbsensi::generate($guru->id);

    $response = $this->actingAs($siswaA->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $tokenDini,
    ])->assertUnprocessable();
    expect($response->json('message'))->toContain('belum dibuka');

    jamSekolahUji(7, 0);
    $this->actingAs($siswaB->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate($guru->id),
    ])->assertOk()->assertJson(['status' => 'hadir']);
});

test('pemisah hadir dan terlambat tepat di detik batas', function () {
    $siswaHadir = siswaQrMandiri('4008', 'Anak Mandiri Tepat');
    $siswaTelat = siswaQrMandiri('4009', 'Anak Mandiri Telat');

    jamSekolahUji(7, 14, 59);
    $this->actingAs($siswaHadir->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate(guruPemilikQr()->id),
    ])->assertOk()->assertJson(['status' => 'hadir']);

    jamSekolahUji(7, 15, 0);
    $this->actingAs($siswaTelat->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate(guruPemilikQr()->id),
    ])->assertOk()->assertJson(['status' => 'terlambat']);
});

test('detik terakhir jendela diterima, detik penutupan ditolak', function () {
    $siswa = siswaQrMandiri('4010', 'Anak Mandiri Menjelang Tutup');
    $guru = guruPemilikQr();

    jamSekolahUji(7, 44, 59);
    $token = TokenQrAbsensi::generate($guru->id);

    $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $token,
    ])->assertOk()->assertJson(['status' => 'terlambat']);

    // 07:45 tidak menutup detik terakhir token (slotnya belum berganti),
    // jadi penolakan ini murni pekerjaan jendela jam.
    jamSekolahUji(7, 45, 0);
    $response = $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => $token,
    ])->assertUnprocessable();

    expect($response->json('message'))->toContain('ditutup')
        ->and($siswa->absensis()->count())->toBe(1)
        ->and($siswa->absensis()->firstOrFail()->status)->toBe('terlambat');
});

test('siswa tanpa rombel aktif ditolak', function () {
    $siswa = siswaQrMandiri('4011', 'Anak Tanpa Rombel');
    $siswa->riwayatKelas()->delete();

    jamSekolahUji(7, 10);
    $response = $this->actingAs($siswa->user)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate(guruPemilikQr()->id),
    ])->assertUnprocessable();

    expect($response->json('message'))->toContain('belum terdaftar')
        ->and(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('akun role siswa tanpa baris data siswa ditolak', function () {
    $akunKosong = User::factory()->create();
    $akunKosong->assignRole('siswa');

    jamSekolahUji(7, 10);
    $response = $this->actingAs($akunKosong)->postJson(route('siswa.absensi.scan.store'), [
        'token' => TokenQrAbsensi::generate(guruPemilikQr()->id),
    ])->assertUnprocessable();

    expect($response->json('message'))->toContain('belum terhubung data siswa');
});

test('halaman scan siswa memuat jendela dan kelasnya', function () {
    $siswa = siswaQrMandiri('4012', 'Anak Mandiri Tampilan');

    $this->actingAs($siswa->user)->get(route('siswa.absensi.scan'))
        ->assertOk()
        ->assertSee('Absen Mandiri')
        ->assertSee('07:00')
        ->assertSee('07:15')
        ->assertSee('07:45')
        ->assertSee('7A');
});

test('halaman scan siswa memperlihatkan baris yang sudah tercatat', function () {
    $rombel = rombelQrMandiri();
    $siswa = siswaQrMandiri('4013', 'Anak Mandiri Sudah Tercatat', $rombel);

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    $this->actingAs($siswa->user)->get(route('siswa.absensi.scan'))
        ->assertOk()
        ->assertSee('sudah tercatat')
        ->assertSee('sakit');
});

test('halaman QR guru terbuka untuk guru dan terkunci bagi akun siswa', function () {
    $guru = guruPemilikQr();
    $siswa = siswaQrMandiri('4014', 'Anak Mandiri Usil');

    $this->actingAs($guru)->get(route('guru.absensi-qr'))
        ->assertOk()
        ->assertSee('QR Untuk Dipindai Siswa')
        ->assertSee('<svg', false);

    $this->actingAs($siswa->user)->get(route('guru.absensi-qr'))->assertForbidden();
});

test('refresh menata QR dari interval pengaturan', function () {
    $guru = guruPemilikQr();

    Pengaturan::updateOrCreate(['kunci' => 'menit_ganti_qr'], ['nilai' => '15']);

    jamSekolahUji(7, 7);

    $this->actingAs($guru)->get(route('guru.absensi-qr.refresh'), ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJson(['interval_menit' => 15, 'ganti_pada' => '07:15'])
        ->assertJsonStructure(['qr']);
});
