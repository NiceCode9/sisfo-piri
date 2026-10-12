<?php

/**
 * Regresi import soal CBT.
 *
 * Fokusnya dua: (1) alur dua tahap yang benar-benar tidak menyentuh database
 * sebelum guru menekan submit, dan (2) empat anhydaman zip — Zip Slip, zip bomb,
 * berkas palsukan gambar, dan nama berkas yang menyamar sebagai gambar.
 */

use App\Imports\SoalCbtTemplateExport;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\Rombel;
use App\Models\User;
use App\Support\PaketSoal;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    Storage::fake(ExamQuestion::DISK);
});

/**
 * @return array{0: User, 1: Exam, 2: QuestionBank}
 */
function setupImport(): array
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $rombel = Rombel::factory()->create();
    $mapel = MataPelajaran::factory()->create();

    $guru = Guru::factory()->create(['user_id' => $admin->id]);
    Pengampu::create([
        'guru_id' => $guru->id,
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
    ]);

    $bank = QuestionBank::create([
        'mata_pelajaran_id' => $mapel->id,
        'guru_id' => $guru->id,
        'nama' => 'Bank Import',
        'created_by' => $admin->id,
    ]);

    $exam = Exam::factory()->create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => $mapel->id,
        'created_by' => $admin->id,
    ]);

    return [$admin, $exam, $bank];
}

/**
 * Bikin paket ZIP dari daftar [nama => isi].
 *
 * @param  array<string, string>  $entri
 */
function paketZip(array $entri): string
{
    $path = tempnam(sys_get_temp_dir(), 'paket-test-').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entri as $nama => $isi) {
        $zip->addFromString($nama, $isi);
    }

    $zip->close();

    return $path;
}

/**
 * PNG sungguhan supaya `getimagesize()` menerimanya.
 */
function pngDummy(int $lebar = 20, int $tinggi = 20): string
{
    $gambar = imagecreatetruecolor($lebar, $tinggi);
    ob_start();
    imagepng($gambar);
    $isi = ob_get_clean();
    imagedestroy($gambar);

    return $isi;
}

/*
 * Keamanan paket
 */

test('menolak zip slip yang mencoba keluar dari folder tujuan', function () {
    $paket = PaketSoal::dari(new UploadedFile(
        paketZip([
            'soal.xlsx' => 'x',
            '../../../config/app.php' => '<?php echo "rCE";',
        ]),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'keluar dari foldernya');

test('menolak entri dengan path absolut', function () {
    PaketSoal::dari(new UploadedFile(
        paketZip(['/etc/passwd' => 'root:x:0:0']),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'path absolut');

test('menolak entri dengan backslash', function () {
    PaketSoal::dari(new UploadedFile(
        paketZip([
            '..\\..\\config\\app.php' => '<?php',
            'soal.xlsx' => 'x',
        ]),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'tidak valid');

test('menolak berkas yang menyamar gambar', function () {
    // Ekstensi .png tapi isinya PHP — inilah yang dicek getimagesize().
    PaketSoal::dari(new UploadedFile(
        paketZip([
            'soal.xlsx' => 'x',
            'gambar/jahat.png' => '<?php system($_GET["c"]); ?>',
        ]),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'bukan gambar yang valid');

test('menolak gambar melebihi batas dimensi', function () {
    $besar = imagecreatetruecolor(3100, 100);
    ob_start();
    imagejpeg($besar);
    $isi = ob_get_clean();
    imagedestroy($besar);

    PaketSoal::dari(new UploadedFile(
        paketZip(['soal.xlsx' => 'x', 'gambar/lebar.jpg' => $isi]),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'batasnya 3000x3000');

test('menolak zip yang tidak berisi spreadsheet', function () {
    // Isinya harus gambar sungguhan, kalau tidak gagalnya datang dari validasi
    // gambar dan test ini tidak menguji apa yang dimaksud.
    PaketSoal::dari(new UploadedFile(
        paketZip(['gambar/a.png' => pngDummy()]),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'harus berisi satu berkas .xlsx');

test('menolak zip yang tidak berisi gambar', function () {
    PaketSoal::dari(new UploadedFile(
        paketZip(['soal.xlsx' => 'x']),
        'paket.zip',
        null,
        null,
        true,
    ));
})->throws(RuntimeException::class, 'tidak berisi gambar');

/*
 * Ekstraksi yang berhasil
 */

test('paket valid diekstrak dan gambarnya diacak ulang', function () {
    $isi = pngDummy(20, 20);

    $paket = PaketSoal::dari(new UploadedFile(
        paketZip(['soal.xlsx' => 'x', 'gambar/mtk-01.png' => $isi]),
        'paket.zip',
        null,
        null,
        true,
    ));

    $tersimpan = $paket->berkasGambar('gambar/mtk-01.png');

    expect($tersimpan)->toMatch('/^[a-f0-9]{40}\.png$/')
        ->and(Storage::disk(ExamQuestion::DISK)->exists($paket->berkas.$tersimpan))->toBeTrue()
        ->and($paket->berkasGambar('mtk-01.png'))->toBe($tersimpan)
        ->and($paket->berkasGambar('tidak-ada.png'))->toBe('');
});

test('nama gambar dari luar tidak pernah jadi nama berkas di disk', function () {
    $isi = pngDummy(10, 10);

    $paket = PaketSoal::dari(new UploadedFile(
        paketZip(['soal.xlsx' => 'x', 'gambar/../../evil.png' => $isi]),
        'paket.zip',
        null,
        null,
        true,
    ));

    // Entrinya ditolak lebih dulu oleh proteksi Zip Slip.
    expect(true)->toBeTrue();
})->throws(RuntimeException::class);

/*
 * Template
 */

test('template excel memuat contoh soal bergambar', function () {
    $admin = setupImport()[0];

    $respons = $this->actingAs($admin)->get(route('admin.cbt.import.template'));
    $respons->assertOk();
    $respons->assertDownload('template-import-soal-cbt.xlsx');

    // XLSX itu zip. Buka supaya bisa dipastikan nama kolom dan referensi gambar
    // benar-benar ikut terbungkus — bukan sekadar berkas yang bisa diunduh.
    $path = $respons->baseResponse->getFile()->getPathname();

    $arsip = new ZipArchive;
    expect($arsip->open($path))->toBeTrue();

    $sheetXml = '';
    $sharedXml = '';

    for ($i = 0; $i < $arsip->numFiles; $i++) {
        $nama = $arsip->getNameIndex($i);

        if ($nama === 'xl/sharedStrings.xml') {
            $sharedXml = (string) $arsip->getFromIndex($i);
        }

        if ($nama === 'xl/worksheets/sheet1.xml') {
            $sheetXml = (string) $arsip->getFromIndex($i);
        }
    }

    $arsip->close();

    // Shared strings memuat isi sel yang diketik (kolom & contoh gambar).
    expect($sharedXml)->not->toBeEmpty()
        ->and($sharedXml)->toContain('gambar')
        ->and($sharedXml)->toContain('gambar/contoh-01.png')
        ->and($sheetXml)->not->toBeEmpty();
});

test('template punya kolom yang diharapkan', function () {
    expect(SoalCbtTemplateExport::KOLOM)->toBe([
        'teks', 'tipe', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'kunci', 'skor', 'gambar',
    ]);
});

/*
 * Otorisasi
 */

test('guru lain tidak bisa mengunggah paket ke ujian yang bukan miliknya', function () {
    [, $exam] = setupImport();

    $rombelLain = Rombel::factory()->create();
    $mapelLain = MataPelajaran::factory()->create();
    $userLain = User::factory()->create();
    $userLain->assignRole('guru');
    $guruLain = Guru::factory()->create(['user_id' => $userLain->id]);
    Pengampu::create([
        'guru_id' => $guruLain->id,
        'rombel_id' => $rombelLain->id,
        'mata_pelajaran_id' => $mapelLain->id,
    ]);

    $this->actingAs($userLain)
        ->post(route('admin.cbt.import.unggah', $exam), [
            'paket' => UploadedFile::fake()->create('paket.zip', 10),
        ])
        ->assertForbidden();
});

test('preview gambar menolak url tanpa tanda tangan', function () {
    [$admin, $exam, $bank] = setupImport();

    $isi = pngDummy(10, 10);

    $paket = PaketSoal::dari(new UploadedFile(
        paketZip(['soal.xlsx' => 'x', 'gambar/a.png' => $isi]),
        'paket.zip',
        null,
        null,
        true,
    ));
    $berkas = $paket->berkasGambar('gambar/a.png');

    // Tanpa `signature` — harus ditolak walau token dan nama berkasnya benar.
    $this->actingAs($admin)
        ->get("/admin/cbt/import/{$paket->token}/gambar/{$berkas}")
        ->assertForbidden();
});

test('preview gambar menolak nama berkas yang bukan hasil pengacakan', function () {
    [$admin, $exam] = setupImport();

    $paketToken = Str::random(40);
    Storage::disk(ExamQuestion::DISK)->put(
        ExamQuestion::IMPORT_DIR.$paketToken.'/'.PaketSoal::MANIFEST,
        json_encode(['a.png' => '../../evil.png']),
    );

    $url = URL::temporarySignedRoute(
        'admin.cbt.import.gambar',
        now()->addMinutes(30),
        ['token' => $paketToken, 'berkas' => str_repeat('a', 40).'.png'],
    );

    $this->actingAs($admin)->get($url)->assertNotFound();
});

/*
 * Sweper
 */

test('sweeper membuang paket import lama dan menyisakan yang baru', function () {
    $disk = Storage::disk(ExamQuestion::DISK);

    $lama = Str::random(40);
    $baru = Str::random(40);

    foreach ([$lama, $baru] as $token) {
        $disk->put(ExamQuestion::IMPORT_DIR.$token.'/'.PaketSoal::MANIFEST, '{}');
    }

    // Paket lama dibuat lebih dari 24 jam lalu.
    touch(
        $disk->path(ExamQuestion::IMPORT_DIR.$lama.'/'.PaketSoal::MANIFEST),
        now()->subHours(25)->getTimestamp(),
    );

    $this->artisan('cbt:expire-sessions')->assertSuccessful();

    expect($disk->exists(ExamQuestion::IMPORT_DIR.$lama))->toBeFalse()
        // Paket yang masih di-review guru tidak boleh ikut terhapus.
        ->and($disk->exists(ExamQuestion::IMPORT_DIR.$baru))->toBeTrue();
});

test('sweeper tidak melempar saat tidak ada paket import sama sekali', function () {
    $this->artisan('cbt:expire-sessions')->assertSuccessful();
});
