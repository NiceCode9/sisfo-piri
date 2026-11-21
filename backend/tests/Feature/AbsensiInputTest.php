<?php

use App\Exports\AbsensiTemplateExport;
use App\Imports\AbsensiImport;
use App\Models\Absensi;
use App\Models\AbsensiRiwayat;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\RiwayatKelas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AkademikSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

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

if (! function_exists('buatAnggotaRombel')) {
    function buatAnggotaRombel(string $nis, string $nama, ?Rombel $rombel = null): Siswa
    {
        static $n = 0;
        $n++;

        $rombel ??= Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', '7A'))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();

        $user = User::factory()->create(['username' => 'siswa-absen-'.$n, 'name' => $nama]);
        $user->assignRole('siswa');

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'nisn' => sprintf('008010%04d', $n),
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

if (! function_exists('rombelUjiAbsensi')) {
    function rombelUjiAbsensi(?string $kelas = '7A'): Rombel
    {
        return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
            ->where('tahun_ajaran_id', TahunAjaran::aktif()->first()->id)
            ->firstOrFail();
    }

    /**
     * Guru yang menjadi wali dari satu rombel, dengan hak input absensi.
     *
     * Memberi `absensis.create` ke wali sengaja: itulah konfigurasi yang
     * membuat penjaga scoping bermakna. Role yang punya `absensis.create`
     * saat ini semuanya universal, jadi tanpa langkah ini uji scoping akan
     * selalu lolos bukan karena guard-nya bekerja.
     */
    function waliUjiAbsensi(Rombel $rombel): User
    {
        $user = User::factory()->create([
            'username' => 'wali-absensi-'.$rombel->kelas->nama_kelas,
            'name' => 'Wali '.$rombel->kelas->nama_kelas,
        ]);
        $user->assignRole('guru');
        $user->givePermissionTo('absensis.create');

        $guru = Guru::create([
            'user_id' => $user->id,
            'nama' => 'Wali '.$rombel->kelas->nama_kelas,
            'jenis_kelamin' => 'L',
            'is_aktif' => true,
        ]);

        $rombel->update(['wali_guru_id' => $guru->id]);

        return $user;
    }
}

test('tamu tidak dapat membuka absensi', function () {
    $this->get(route('admin.absensis.index'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka absensi', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.absensis.index'))->assertForbidden();
});

test('grid menampilkan siswa rombel', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())->get(route('admin.absensis.index', ['rombel_id' => $rombel->id]))
        ->assertOk()->assertSee('Anak Absen Satu');
});

test('siswa tanpa catatan tampil sebagai belum dicatat', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // Select tidak boleh terisi "hadir" secara diam-diam. Kalau iya, membuka
    // grid lalu menekan Simpan akan mencatat seluruh kelas hadir.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Belum dicatat')
        ->assertSee('<option value="" selected', false);
});

test('siswa yang sudah punya catatan menampilkan status tercatatnya', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();

    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $hari,
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    // Memakai "Belum dicatat" sebagai nilai kosong tidak boleh ikut menimpa
    // pilihan yang memang sudah tercatat.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
    ]))
        ->assertOk()
        ->assertSee('<option value="sakit" selected', false)
        ->assertDontSee('<option value="" selected', false);
});

test('simpan grid tanpa pilihan tidak membuat baris hadir', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $hari = now()->toDateString();

    // Semua dropdown masih "belum dicatat" dan form tetap mengirim nilainya
    // sebagai string kosong. Tidak boleh ada yang tersimpan sebagai hadir.
    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => '', $s2->id => ''],
    ])->assertSessionHasErrors('status');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(0);
});

test('siswa yang dibiarkan kosong tidak menghalangi siswa lain tersimpan', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $s3 = buatAnggotaRombel('4003', 'Anak Absen Tiga', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => 'hadir', $s2->id => '', $s3->id => 'sakit'],
    ])->assertSessionHas('success');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s1->id)->exists())->toBeTrue()
        ->and(Absensi::where('siswa_id', $s3->id)->exists())->toBeTrue()
        ->and(Absensi::where('siswa_id', $s2->id)->exists())->toBeFalse();
});

test('tombol tandai semua hadir tersedia sebagai pintasan cepat', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    // Jalur cepat "semua hadir" harus tetap ada, tapi sebagai aksi eksplisit.
    $this->actingAs(superAdmin())->get(route('admin.absensis.index', ['rombel_id' => $rombel->id]))
        ->assertOk()
        ->assertSee('Tandai semua hadir')
        ->assertSee('data-tandai-semua="hadir"', false);
});

test('tanggal pencatatan ngawur ditolak tanpa membuat halaman 500', function () {
    $rombel = rombelUjiAbsensi();
    buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => 'bukan-tanggal']))
        ->assertSessionHasErrors('tanggal')
        ->assertStatus(302);
});

test('tanggal masa depan ditolak', function () {
    $rombel = rombelUjiAbsensi();

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.index', [
            'rombel_id' => $rombel->id,
            'tanggal' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('tanggal');

});

test('penanda cek-belum-hadir tidak ikut di-cache', function () {
    Cache::flush();
    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => null]);

    // Perintah ini menulis penanda lalu membacanya di run yang sama sebagai
    // self-gating. Kalau `Pengaturan::nilai()` meng-cache semua kunci, penanda
    // itu jadi basi dan perintah jalan berulang.
    expect(Pengaturan::KUNCI_CACHE)->not->toContain('cek_belum_hadir_terakhir');

    Pengaturan::updateOrCreate(['kunci' => 'cek_belum_hadir_terakhir'], ['nilai' => '2026-10-10']);

    expect(Pengaturan::nilai('cek_belum_hadir_terakhir'))->toBe('2026-10-10');
});

test('batch manual tersimpan dan dapat diperbarui', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Absen Dua', $rombel);
    $hari = now()->toDateString();

    $payload = [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$s1->id => 'hadir', $s2->id => 'sakit'],
    ];

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)
        ->assertRedirect(route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => $hari]));

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s2->id)->first()->status)->toBe('sakit')
        ->and(Absensi::where('siswa_id', $s1->id)->first()->metode)->toBe('manual');

    // Pencatatan pertama bukan koreksi, jadi tidak ada jejaknya.
    expect(AbsensiRiwayat::count())->toBe(0);

    // Memperbarui baris yang sudah ada adalah koreksi: butuh alasan dan
    // selalu meninggalkan jejak.
    $payload['status'] = [$s1->id => 'izin', $s2->id => 'sakit'];
    $payload['alasan'] = 'Salah input jam masuk';

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), $payload)->assertSessionHas('success');

    expect(Absensi::where('tanggal', $hari)->count())->toBe(2)
        ->and(Absensi::where('siswa_id', $s1->id)->first()->status)->toBe('izin');

    // Hanya s1 yang berubah; s2 disentuh tapi nilainya sama sehingga bukan koreksi.
    expect(AbsensiRiwayat::count())->toBe(1);

    $jejak = AbsensiRiwayat::firstOrFail();

    expect($jejak->siswa_id)->toBe($s1->id)
        ->and($jejak->status_sebelum)->toBe('hadir')
        ->and($jejak->status_sesudah)->toBe('izin')
        ->and($jejak->alasan)->toBe('Salah input jam masuk')
        ->and($jejak->dicatat_oleh)->not->toBeNull();
});

test('mengubah absensi yang sudah tercatat tanpa alasan ditolak', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    // Tanpa alasan, koreksi ditolak dan baris lama tidak boleh berubah.
    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'alpa'],
    ])->assertSessionHasErrors('alasan');

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir')
        ->and(AbsensiRiwayat::count())->toBe(0);
});

test('koreksi butuh permission absensis.edit', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    // User yang boleh mencatat tapi tidak memegang `absensis.edit`. Permission
    // di Spatie bersifat aditif antara user dan role-nya, jadi revoke pada
    // `guru-piket` tidak cukup — role itu sudah memberi `absensis.edit`.
    // User dibuat tanpa role, dengan permission langsung dan sebuah baris Guru
    // supaya rombelnya tetap terjangkau; tanpa itu penolakan datang dari cek
    // jangkauan rombel, bukan dari permission yang sedang diuji.
    $setengah = User::factory()->create(['username' => 'pengguna-absensi-sebagian']);
    $guruSetengah = Guru::create([
        'user_id' => $setengah->id,
        'nama' => 'Guru Absensi Sebagian',
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);
    $rombel->update(['wali_guru_id' => $guruSetengah->id]);

    $setengah->givePermissionTo('absensis.view', 'absensis.create');

    expect($setengah->can('absensis.create'))->toBeTrue()
        ->and($setengah->can('absensis.edit'))->toBeFalse();

    $this->actingAs($setengah)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'alpa'],
        'alasan' => 'Mencoba menimpa tanpa izin',
    ])->assertForbidden();

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir')
        ->and(AbsensiRiwayat::count())->toBe(0);
});

test('bukti sakit hanya bisa dilampirkan pada status sakit atau izin', function () {
    Storage::fake('berkas');

    $rombel = rombelUjiAbsensi();
    $sakit = buatAnggotaRombel('4001', 'Anak Sakit', $rombel);
    $hadir = buatAnggotaRombel('4002', 'Anak Hadir', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$sakit->id => 'sakit', $hadir->id => 'hadir'],
    ])->assertSessionHas('success');

    $absensiSakit = Absensi::where('siswa_id', $sakit->id)->firstOrFail();
    $absensiHadir = Absensi::where('siswa_id', $hadir->id)->firstOrFail();

    $this->actingAs($admin)->post(route('admin.absensis.bukti', $absensiSakit), [
        'berkas' => UploadedFile::fake()->image('surat.jpg'),
    ])->assertSessionHas('success');

    $absensiSakit->refresh();
    expect($absensiSakit->berkas_path)->not->toBeNull();
    Storage::disk('berkas')->assertExists($absensiSakit->berkas_path);

    // Lampiran pada baris `hadir` tidak bermakna dan ditolak.
    $this->actingAs($admin)->post(route('admin.absensis.bukti', $absensiHadir), [
        'berkas' => UploadedFile::fake()->image('ngawur.jpg'),
    ])->assertStatus(422);

    expect($absensiHadir->fresh()->berkas_path)->toBeNull();
});

test('bukti ditolak bila bukan gambar atau melebihi batas', function () {
    Storage::fake('berkas');

    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Sakit', $rombel);
    $absensi = Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'izin',
        'metode' => 'manual',
    ]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.bukti', $absensi), [
        'berkas' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('berkas');

    $this->actingAs(superAdmin())->post(route('admin.absensis.bukti', $absensi), [
        'berkas' => UploadedFile::fake()->image('besar.jpg')->size(10241),
    ])->assertSessionHasErrors('berkas');

    expect($absensi->fresh()->berkas_path)->toBeNull();
});

test('mengunggah bukti baru menghapus berkas lama', function () {
    Storage::fake('berkas');

    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Sakit', $rombel);
    $absensi = Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.bukti', $absensi), [
        'berkas' => UploadedFile::fake()->image('pertama.jpg'),
    ])->assertSessionHas('success');

    $lama = $absensi->fresh()->berkas_path;
    Storage::disk('berkas')->assertExists($lama);

    $this->actingAs($admin)->post(route('admin.absensis.bukti', $absensi), [
        'berkas' => UploadedFile::fake()->image('kedua.jpg'),
    ])->assertSessionHas('success');

    $baru = $absensi->fresh()->berkas_path;

    // Satu catatan tidak boleh menumpuk lampiran.
    expect($baru)->not->toBe($lama)
        ->and(Storage::disk('berkas')->allFiles('absensi'))->toHaveCount(1);
});

test('bukti hanya bisa dibaca yang berhak: rombel, siswa, atau orang tuanya', function () {
    Storage::fake('berkas');

    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Sakit', $rombel);
    $absensi = Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.bukti', $absensi), [
        'berkas' => UploadedFile::fake()->image('surat.jpg'),
    ])->assertSessionHas('success');

    // Admin penjaga gerbang: punya jangkauan rombel.
    $this->actingAs(superAdmin())->get(route('dokumen.absensi', $absensi))->assertOk();

    // Orang tua siswa tersebut.
    $ortu = User::where('username', 'ortu-'.$siswa->nisn)->firstOrFail();
    $this->actingAs($ortu)->get(route('dokumen.absensi', $absensi))->assertOk();

    // Orang tua anak lain: harus ditolak, meski sama-sama punya akun ortu.
    $siswaLain = buatAnggotaRombel('4002', 'Anak Lain', $rombel);
    $ortuLain = User::where('username', 'ortu-'.$siswaLain->nisn)->firstOrFail();
    $this->actingAs($ortuLain)->get(route('dokumen.absensi', $absensi))->assertForbidden();

    // Guru tanpa penugasan di rombel ini, meski punya absensis.view, tidak
    // boleh membaca bukti kesehatan siswa.
    $asing = User::factory()->create(['username' => 'guru-asing-bukti', 'name' => 'Guru Asing']);
    $asing->assignRole('guru');
    $this->actingAs($asing)->get(route('dokumen.absensi', $absensi))->assertForbidden();
});

test('unduhan bukti ditolak untuk absensi tanpa lampiran', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Sakit', $rombel);
    $absensi = Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'sakit',
        'metode' => 'manual',
    ]);

    $this->actingAs(superAdmin())->get(route('dokumen.absensi', $absensi))->assertNotFound();
});

/**
 * Membangun berkas .xlsx sungguhan dari daftar baris, dengan baris pertama
 * sebagai judul kolom. Dipakai supaya uji impor melewati jalur pembacaan yang
 * sama dengan berkas yang diunggah pengguna — termasuk semua keanehan angka
 * dan tanggal yang dihasilkan Excel.
 *
 * @param  array<int, array<string, string|null>>  $baris
 */
function xlsxAbsensiUji(array $baris): UploadedFile
{
    $isi = array_map(
        fn ($b) => array_map(fn ($v) => (string) ($v ?? ''), $b),
        $baris
    );

    return UploadedFile::fake()->createWithContent('absensi.xlsx', Excel::raw(
        new class($isi) implements FromArray, WithHeadings
        {
            public function __construct(private array $baris) {}

            public function headings(): array
            {
                return ['rombel', 'tahun_ajaran', 'tanggal', 'nis', 'status'];
            }

            public function array(): array
            {
                return $this->baris;
            }
        },
        'Xlsx'
    ));
}

test('impor mencatat absensi dari berkas', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Impor Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Impor Dua', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s1->nis, 'status' => 'hadir'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s2->nis, 'status' => 'sakit'],
            ]),
        ])
        ->assertSessionHas('success');

    expect(Absensi::where('siswa_id', $s1->id)->firstOrFail()->status)->toBe('hadir')
        ->and(Absensi::where('siswa_id', $s2->id)->firstOrFail()->status)->toBe('sakit');
});

test('impor menolak rombel di luar jangkauan pemanggil', function () {
    $rombelLain = rombelUjiAbsensi('7B');
    $siswa = buatAnggotaRombel('4001', 'Anak Kelas Asing', $rombelLain);
    $wali = waliUjiAbsensi(rombelUjiAbsensi('7A'));

    // Ini yang paling penting: tanpa penjaga scoping di dalam importer, impor
    // menjadi jalan pintas melewati closure yang sama seperti grid dan scan.
    $this->actingAs($wali)
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7B', 'tahun_ajaran' => '', 'tanggal' => now()->toDateString(), 'nis' => $siswa->nis, 'status' => 'hadir'],
            ]),
        ])
        ->assertSessionHas('error');

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('impor tidak menimpa absensi yang sudah tercatat', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Impor', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    // Impor dengan status berbeda dilaporkan, bukan menimpa. Koreksi-butuh
    // alasan per perubahan, dan itu tidak bisa dinyatakan di berkas massal.
    $this->actingAs($admin)
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $siswa->nis, 'status' => 'alpa'],
            ]),
        ])
        ->assertSessionHas('error');

    expect(Absensi::where('siswa_id', $siswa->id)->firstOrFail()->status)->toBe('hadir');
});

test('impor melewati baris dengan status kosong', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Impor Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Impor Dua', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s1->nis, 'status' => 'hadir'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s2->nis, 'status' => ''],
            ]),
        ])
        ->assertSessionHas('success');

    // Status kosong berarti tidak dicatat, sama seperti grid.
    expect(Absensi::where('siswa_id', $s1->id)->exists())->toBeTrue()
        ->and(Absensi::where('siswa_id', $s2->id)->exists())->toBeFalse();
});

test('impor melaporkan NIS dan rombel yang tidak dikenal', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Impor', $rombel);
    $hari = now()->toDateString();

    $sebelum = Absensi::count();

    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '9Z', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $siswa->nis, 'status' => 'hadir'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => '999999', 'status' => 'hadir'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $siswa->nis, 'status' => 'ngawur'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $siswa->nis, 'status' => 'hadir'],
            ]),
        ])
        ->assertSessionHas('error');

    // Tiga baris pertama ditolak; baris keempat dicatat.
    expect(Absensi::count())->toBe($sebelum + 1)
        ->and(Absensi::where('siswa_id', $siswa->id)->whereDate('tanggal', $hari)->exists())->toBeTrue();
});

test('impor membaca tanggal sebagai nomor serial Excel', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Serial', $rombel);
    $hari = now()->startOfDay();

    // Excel menyimpan tanggal sebagai angka (hari sejak 1899-12-30). Kalau ini
    // tidak ditangani, seluruh berkas ditolak dengan "tanggal tidak bisa dibaca"
    // padahal yang dibuka pengguna adalah Excel biasa.
    $serial = abs((int) Carbon::create(1899, 12, 30)->diffInDays($hari, true));

    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => (string) $serial, 'nis' => $siswa->nis, 'status' => 'hadir'],
            ]),
        ])
        ->assertSessionHas('success');

    expect(Carbon::parse(Absensi::where('siswa_id', $siswa->id)->value('tanggal'))->toDateString())
        ->toBe($hari->toDateString());
});

test('impor menolak NIS bertanda angka nol di depan', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('04001', 'Anak Nol Depan', $rombel);
    $hari = now()->toDateString();

    // Kolom NIS di template diformat Teks. Kalau diformat angka, 04001 jadi
    // 4001 dan siswa yang salah tercatat — lebih buruk daripada ditolak.
    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $siswa->nis, 'status' => 'hadir'],
            ]),
        ])
        ->assertSessionHas('success');

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('impor hanya mengirim satu notifikasi alpa per rombel dan tanggal', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('4001', 'Anak Alpa Satu', $rombel);
    $s2 = buatAnggotaRombel('4002', 'Anak Alpa Dua', $rombel);
    $hari = now()->toDateString();

    $this->actingAs(superAdmin())
        ->post(route('admin.absensis.impor'), [
            'file' => xlsxAbsensiUji([
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s1->nis, 'status' => 'alpa'],
                ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => $hari, 'nis' => $s2->nis, 'status' => 'alpa'],
            ]),
        ])
        ->assertSessionHas('success');

    $rombelTanggal = $rombel->id.'|'.$hari;

    // Notifikasi sudah idempoten lewat kunci unik, jadi hitungan di sini adalah
    // banyaknya tugas antrean, bukan/baris.
    expect(DB::table('notifikasi_logs')->where('kunci_idempoten', 'like', "%{$rombelTanggal}%")->count())
        ->toBeLessThanOrEqual(1)
        ->and(Absensi::whereIn('siswa_id', [$s1->id, $s2->id])->where('status', 'alpa')->count())->toBe(2);
});

test('template impor memuat kolom yang dibutuhkan', function () {
    // Invarian yang diuji: template dan importer wajib memakai daftar kolom
    // yang sama. Kalau keduanya berbeda, berkas yang diunduh pengguna dijamin
    // ditolak — dan itu baru ketahuan setelah dipakai sungguhan.
    expect((new AbsensiTemplateExport)->headings())->toBe(AbsensiImport::KOLOM);

    $this->actingAs(superAdmin())
        ->get(route('admin.absensis.impor.template'))
        ->assertOk()
        ->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
});

test('riwayat koreksi ditampilkan di grid hari yang sama', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('4001', 'Anak Absen Satu', $rombel);
    $hari = now()->toDateString();
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    $this->actingAs($admin)->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
        'status' => [$siswa->id => 'izin'],
        'alasan' => 'Surat dokter menyusul',
    ])->assertSessionHas('success');

    $this->actingAs($admin)->get(route('admin.absensis.index', [
        'rombel_id' => $rombel->id,
        'tanggal' => $hari,
    ]))
        ->assertOk()
        ->assertSee('Riwayat Koreksi (1)')
        ->assertSee('Surat dokter menyusul')
        ->assertSee('Dari')
        ->assertSee('Ke');
});

test('batch menolak siswa luar rombel', function () {
    $rombel = rombelUjiAbsensi();
    $luar = buatAnggotaRombel('4009', 'Anak Luar');

    $kelas7B = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $rombel7B = Rombel::where('kelas_id', $kelas7B->id)->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)->firstOrFail();
    $luar->update(['kelas_id' => $kelas7B->id]);
    $luar->riwayatKelas()->delete();
    RiwayatKelas::create(['siswa_id' => $luar->id, 'kelas_id' => $kelas7B->id, 'tahun_ajaran_id' => $rombel->tahun_ajaran_id, 'status' => 'aktif']);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$luar->id => 'hadir'],
    ])->assertSessionHasErrors('status.'.$luar->id);

    expect(Absensi::count())->toBe(0);
    expect($rombel7B->id)->not->toBe($rombel->id);
});
test('simpan grid tidak mengubah baris QR yang tidak berubah', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('5001', 'Anak QR Tetap', $rombel);
    $pencatat = User::factory()->create(['name' => 'Pencatat QR']);

    Absensi::create([
        'siswa_id' => $siswa->id,
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => 'hadir',
        'jam_datang' => '06:30:00',
        'metode' => 'qr',
        'dicatat_oleh' => $pencatat->id,
    ]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => [$siswa->id => 'hadir'],
    ])->assertSessionHas('success');

    $baris = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($baris->metode)->toBe('qr')
        ->and($baris->dicatat_oleh)->toBe($pencatat->id)
        ->and(AbsensiRiwayat::count())->toBe(0);
});

test('simpan grid yang mengubah status tetap menulis manual dan histori', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('5002', 'Anak QR Koreksi', $rombel);

    Absensi::create([
        'siswa_id' => $siswa->id,
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'status' => 'hadir',
        'jam_datang' => '06:30:00',
        'metode' => 'qr',
        'dicatat_oleh' => User::factory()->create()->id,
    ]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombel->id,
        'tanggal' => now()->toDateString(),
        'alasan' => 'Koreksi status',
        'status' => [$siswa->id => 'izin'],
    ])->assertSessionHas('success');

    $baris = Absensi::where('siswa_id', $siswa->id)->firstOrFail();

    expect($baris->metode)->toBe('manual')
        ->and(AbsensiRiwayat::count())->toBe(1);
});
test('impor memakai riwayat kelas aktif, bukan kelas_id', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('5101', 'Anak Riwayat Impor', $rombel);

    $kelasLain = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $siswa->update(['kelas_id' => $kelasLain->id]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.impor'), [
        'file' => xlsxAbsensiUji([
            ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => now()->toDateString(), 'nis' => $siswa->nis, 'status' => 'hadir'],
        ]),
    ])->assertSessionHas('success');

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('impor menolak siswa yang riwayat aktifnya bukan di rombel itu', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('5102', 'Anak Riwayat Lain', $rombel);

    $kelasLain = Kelas::where('nama_kelas', '7B')->firstOrFail();
    $rombelLain = Rombel::where('kelas_id', $kelasLain->id)
        ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
        ->firstOrFail();

    $siswa->update(['kelas_id' => $rombel->kelas_id]);
    $siswa->riwayatKelas()->delete();
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelLain->kelas_id,
        'tahun_ajaran_id' => $rombelLain->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    $this->actingAs(superAdmin())->post(route('admin.absensis.impor'), [
        'file' => xlsxAbsensiUji([
            ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => now()->toDateString(), 'nis' => $siswa->nis, 'status' => 'hadir'],
        ]),
    ])->assertSessionHas('error');

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});

test('impor tidak mengisi jam datang untuk status apa pun', function () {
    $rombel = rombelUjiAbsensi();
    $s1 = buatAnggotaRombel('5103', 'Anak Impor Hadir', $rombel);
    $s2 = buatAnggotaRombel('5104', 'Anak Impor Izin', $rombel);

    $this->actingAs(superAdmin())->post(route('admin.absensis.impor'), [
        'file' => xlsxAbsensiUji([
            ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => now()->toDateString(), 'nis' => $s1->nis, 'status' => 'hadir'],
            ['rombel' => '7A', 'tahun_ajaran' => '', 'tanggal' => now()->toDateString(), 'nis' => $s2->nis, 'status' => 'izin'],
        ]),
    ])->assertSessionHas('success');

    expect(Absensi::where('siswa_id', $s1->id)->firstOrFail()->jam_datang)->toBeNull()
        ->and(Absensi::where('siswa_id', $s2->id)->firstOrFail()->jam_datang)->toBeNull();
});

test('impor menolak tahun ajaran yang tidak dikenal', function () {
    $rombel = rombelUjiAbsensi();
    $siswa = buatAnggotaRombel('5105', 'Anak Tahun Asing', $rombel);

    $this->actingAs(superAdmin())->post(route('admin.absensis.impor'), [
        'file' => xlsxAbsensiUji([
            ['rombel' => '7A', 'tahun_ajaran' => '1999/2000', 'tanggal' => now()->toDateString(), 'nis' => $siswa->nis, 'status' => 'hadir'],
        ]),
    ])->assertSessionHas('error');

    expect(Absensi::where('siswa_id', $siswa->id)->exists())->toBeFalse();
});
