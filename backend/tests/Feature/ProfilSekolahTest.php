<?php

use App\Models\ProfilSekolah;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PpdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    Storage::fake('public');
});

if (! function_exists('superAdmin')) {
    function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}

test('tamu tidak dapat membuka form profil', function () {
    $this->get(route('admin.profil-sekolah.edit'))->assertRedirect(route('login'));
});

test('user tanpa permission ditolak membuka form profil', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.profil-sekolah.edit'))->assertForbidden();
});

test('super-admin dapat membuka form profil', function () {
    $response = $this->actingAs(superAdmin())->get(route('admin.profil-sekolah.edit'));
    $response->assertOk()->assertSee('Profil Sekolah');
});

test('super-admin dapat memperbarui identitas dan misi', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $response = $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik Baru',
        'telp' => '(0274) 123456',
        'email' => 'info@smkn.sch.id',
        'tahun_berdiri' => 2005,
        'akreditasi' => 'A',
        'visi' => 'Visi baru',
        'misi' => ['Misi satu', 'Misi dua'],
        'nama_kepala' => 'Budi Kepala',
    ]);

    $response->assertRedirect(route('admin.profil-sekolah.edit'));
    $profil = ProfilSekolah::first();
    expect($profil->nama_sekolah)->toBe('SMKN Ngaglik Baru')
        ->and($profil->misi)->toBe(['Misi satu', 'Misi dua'])
        ->and(ProfilSekolah::count())->toBe(1);
});

test('ganti logo menghapus file lama', function () {
    $profil = ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);
    $lama = UploadedFile::fake()->image('lama.png')->store('profil', 'public');
    $profil->update(['logo_path' => $lama]);
    Storage::disk('public')->assertExists($lama);

    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'logo' => UploadedFile::fake()->image('baru.png'),
    ])->assertRedirect(route('admin.profil-sekolah.edit'));

    Storage::disk('public')->assertMissing($lama);
    Storage::disk('public')->assertExists($profil->fresh()->logo_path);
});

test('maps url selain google embed ditolak', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $response = $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'maps_embed_url' => 'https://evil.example.com/x',
    ]);

    $response->assertSessionHasErrors('maps_embed_url');
});

test('baris misi yang kosong tidak menggagalkan penyimpanan', function () {
    // Form selalu merender minimal satu baris `misi[]` yang kosong secara
    // bawaan. Aturan `misi.*` pernah memakai `required_with:misi`, jadi begitu
    // administrator menyentuh baris misi lalu mengosongkannya lagi, SETIAP
    // penyimpanan gagal.
    //
    // Yang membuatnya tidak bisa dilaporkan: kuncinya jadi `misi.0`, bukan
    // `misi`. View hanya menulis `@error('misi')` yang tidak menangkap
    // `misi.0`, dan `layouts.partials.alert` menyingkirkan `misi.0` dari
    // ringkasan karena cocok prefiks `misi.`. Pesannya hilang dari kedua
    // tempat: tidak ada di bawah kolom, tidak ada di atas form.
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $response = $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'misi' => ['', 'Misi pertama', '', 'Misi kedua'],
    ]);

    $response->assertRedirect(route('admin.profil-sekolah.edit'))
        ->assertSessionHasNoErrors();

    // Baris kosong harus dibuang, bukan tersimpan sebagai string kosong.
    expect(ProfilSekolah::first()->misi)->toBe(['Misi pertama', 'Misi kedua']);
});

test('semua baris misi kosong berarti misi dikosongkan, bukan gagal', function () {
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik', 'misi' => ['Misi lama']]);

    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'misi' => ['', ''],
    ])->assertRedirect(route('admin.profil-sekolah.edit'))
        ->assertSessionHasNoErrors();

    expect(ProfilSekolah::first()->misi)->toBe([]);
});

test('error misi yang muncul selalu terlihat di layar', function () {
    // Pengaman kedua untuk kelas bug yang sama: kalau validasi gagal, pesannya
    // harus muncul di bawah kolom ATAU di ringkasan atas. Tidak boleh hilang
    // dari keduanya.
    //
    // Yang dipancing di sini `misi` dengan lebih dari 20 butir, supaya kuncinya
    // `misi` (bukan `misi.0`) dan pesannya harus tampil di bawah kolom.
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    // `actingAs` dipanggil SEKALI. `superAdmin()` membuat user baru setiap
    // kali dipanggil, jadi memanggilnya lagi di antara PUT dan GET
    // menghasilkan user berbeda dengan session yang tidak lagi sama.
    $this->actingAs(superAdmin());

    /*
     * Tiga jebakan di sekeliling tes ini, semuanya wegen flash data.
     *
     * Error validasi disimpan sebagai flash data yang dihapus begitu dibaca.
     * Jadi SESIAPAT yang menyentuh error antara PUT dan GET akan membuatnya
     * hilang sebelum halaman sempat merendernya, dan tes gagal bukan karena
     * pesannya tidak tampil tapi karena pesannya tidak pernah ada:
     *
     * 1. `session()->get('errors')` untuk "membuktikan" error itu ada.
     * 2. `$response->assertSessionHasErrors('misi')` — assists ini ternyata
     *    juga mengonsumsi flash-nya. Sudah diuji: dengan panggilan ini pesan
     *    tidak muncul, tanpanya pesan muncul.
     * 3. `followingRedirects()`, yang mengosongkan session sepenuhnya.
     *
     * Karena itu assertion session-nya tidak dipakai di sini sama sekali.
     * "Validasi ditolak" sudah dibuktikan tes sebelumnya; yang diuji di sini
     * hanya satu hal: pesan itu sampai ke layar.
     */
    $this->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'misi' => array_fill(0, 21, 'Butir misi'),
    ]);

    $html = $this->get(route('admin.profil-sekolah.edit'))->getContent();

    // Yang diperiksa adalah isi pesannya, bukan tempat penampilannya. View ini
    // mendaftarkan `'misi.*'` ke `layouts.partials.alert`, jadi pesannya
    // muncul di bawah kolom dan TIDAK diulang di ringkasan atas. Kalau hanya
    // mengecek kata "Periksa kembali isian berikut", tes ini akan gagal
    // meski pesannya jelas terlihat.
    expect($html)->toContain('Misi tidak boleh memiliki lebih dari 20 item.');
});

test('pesan validasi yang muncul bisa ditindaklanjuti', function () {
    // "Maps embed url harus diawali salah satu dari: ..." adalah satu-satunya
    // petunjuk yang muncul di layar, dan tidak menjelaskan apa yang harus
    // administrator tempel.
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'maps_embed_url' => 'https://www.google.com/maps?q=Ngaglik',
    ])->assertSessionHasErrors([
        'maps_embed_url' => 'URL harus diambil dari tombol Share Google Maps lalu pilih "Sematkan peta" (Embed a map), lalu salin atribut src. URL peta biasa tidak bisa dipakai.',
    ]);
});

test('website tanpa protokol diterima', function () {
    // Aturan `url` bawaan menolak `sekolah.sch.id`, padahal itu cara paling
    // natural mengetik alamat situs. Lebih penting lagi, halaman publik sudah
    // menambahkan `https://` sendiri, jadi aturan dan tampilan bertentangan.
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'website' => 'sekolah.sch.id',
    ])->assertRedirect(route('admin.profil-sekolah.edit'))
        ->assertSessionHasNoErrors();

    expect(ProfilSekolah::first()->website)->toBe('sekolah.sch.id');

    // Dan bentuk yang jelas bukan domain tetap ditolak, dengan pesan yang
    // menjelaskan format yang diterima.
    $this->actingAs(superAdmin())->put(route('admin.profil-sekolah.update'), [
        'nama_sekolah' => 'SMKN Ngaglik',
        'website' => 'bukan domain',
    ])->assertSessionHasErrors('website');
});

test('halaman tentang menampilkan peta lokasi', function () {
    // Peta dulu hanya ada di section Kontak (Beranda). Halaman Tentang tidak
    // pernah menampilkannya sama sekali, padahal petanya data yang sama dan
    // di halaman profil itulah orang tua mencari tahu di mana sekolahnya.
    ProfilSekolah::create([
        'nama_sekolah' => 'SMKN Ngaglik',
        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=MOCK',
    ]);

    $tentang = $this->get(route('spmb.about'))->assertOk()->getContent();
    $beranda = $this->get(route('spmb.home'))->assertOk()->getContent();

    expect($tentang)
        ->toContain('maps/embed?pb=MOCK')
        // `title` tanpa itu, pembaca layar membacakan URL peta yang panjang
        // sebagai ganti nama tempatnya.
        ->toContain('Peta lokasi SMKN Ngaglik');

    /*
     * Dua halaman harus menampilkan peta yang sama. Ini yang jadi alasan
     * petanya diekstrak jadi partial, bukan ditulis dua kali.
     *
     * `preg_match` biasa, bukan helper `regex()` Laravel: penulisan helper
     * dengan kurung siku bisa berubah jadi konstanta PHP yang tidak ada.
     */
    $pola = '#src="(https://www\.google\.com/maps[^"]*)"#';

    preg_match($pola, $tentang, $diTentang);
    preg_match($pola, $beranda, $diBeranda);

    expect($diTentang[1] ?? null, 'peta tidak ada di halaman Tentang')->toBe('https://www.google.com/maps/embed?pb=MOCK');
    expect($diTentang[1] ?? null)->toBe($diBeranda[1] ?? null, 'dua halaman menampilkan peta yang berbeda');
});

test('peta cadangan dipakai dan jujur saat url admin kosong', function () {
    // Kalau `maps_embed_url` belum diisi, peta tetap muncul memakai patokan
    // Ngaglik, dan halaman harus jujur mengatakannya supaya tidak terlihat
    // `PETA_CADANGAN` di-escape dulu: di dalam HTML, `&` menjadi `&amp;`.
    ProfilSekolah::create(['nama_sekolah' => 'SMKN Ngaglik']);

    $html = $this->get(route('spmb.about'))->assertOk()->getContent();

    expect($html)
        // `ProfilSekolah::PETA_CADANGAN`, bukan string yang diketik ulang di
        // sini. Kalau konstantanya berubah, tes ini harus ikut gagal.
        // `PETA_CADANGAN` di-escape dulu: di dalam HTML, `&` menjadi `&amp;`.
        ->toContain(e(ProfilSekolah::PETA_CADANGAN))
        ->toContain('URL Embed Google Maps')
        ->toContain('belum diisi');
});

test('halaman about menampilkan nama dari database', function () {
    ProfilSekolah::create([
        'nama_sekolah' => 'SMKN Ngaglik Asli',
        'tahun_berdiri' => 2005,
        'akreditasi' => 'A',
        'misi' => ['Misi DB Satu', 'Misi DB Dua'],
    ]);

    $response = $this->get(route('spmb.about'));

    $response->assertOk()
        ->assertSee('SMKN Ngaglik Asli')
        ->assertSee('Misi DB Satu');
});
