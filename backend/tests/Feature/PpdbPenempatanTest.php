<?php

use App\Exports\CalonDiterimaExport;
use App\Models\CalonSiswa;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
});

function adminPpdb(): User
{
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    return $user;
}

/**
 * Simulasikan calon siswa yang sudah daftar lewat form publik sehingga punya akun
 * (password acak) dan sudah berstatus `diterima` — persis kondisi setelah admin
 * menekan tombol "Terima" di halaman PPDB.
 */
function calonDiterima(string $nisn, array $ortu = [], string $status = 'diterima'): CalonSiswa
{
    $tahun = TahunAjaran::create([
        'nama_tahun_ajaran' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => true,
    ]);

    $user = User::factory()->create([
        'username' => $nisn,
        'name' => 'Calon '.$nisn,
        'password' => 'rahasia-acak-'.$nisn,
    ]);
    $user->assignRole('siswa');

    $calon = CalonSiswa::create([
        'user_id' => $user->id,
        'no_pendaftaran' => 'PPDB-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'nik' => '32730'.substr($nisn, -10, 10),
        'nisn' => $nisn,
        'nama_lengkap' => 'Calon '.$nisn,
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '2013-05-10',
        'agama' => 'Islam',
        'alamat' => 'Jl. Contoh No. 1',
        'no_hp' => '081200000000',
        'email' => 'calon-'.$nisn.'@example.com',
        'tahun_ajaran_id' => $tahun->id,
        'status_pendaftaran' => $status,
    ] + $ortu);

    return $calon->fresh();
}

/**
 * Bangun file Excel (.xlsx) dari header + baris, dengan kolom NIS/NISN dipaksa
 * bertipe teks agar angka nol di depan tidak hilang saat dibaca.
 */
function excelSiswa(array $header, array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([$header], $rows));

    foreach ($sheet->getRowIterator(1) as $row) {
        foreach (['A', 'B'] as $col) {
            $cell = $sheet->getCell($col.$row->getRowIndex());
            if ($cell->getValue() !== null) {
                $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING);
            }
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'penempatan').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile(
        $path,
        'penempatan.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        UPLOAD_ERR_OK,
        true
    );
}

function headerPenempatan(): array
{
    return ['nis', 'nisn', 'nama', 'kelas', 'tahun_ajaran', 'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 'no_hp_orang_tua'];
}

test('terima calon siswa menyalin data orang-tua ke siswa', function () {
    $calon = calonDiterima('0070030001', [
        'nama_ayah' => 'Bapak Ahmad',
        'pekerjaan_ayah' => 'Wiraswasta',
        'nama_ibu' => 'Ibu Siti',
        'pekerjaan_ibu' => 'IRT',
        'no_hp_orang_tua' => '081234567890',
    ], 'menunggu');

    $this->actingAs(adminPpdb())
        ->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima'])
        ->assertSessionHasNoErrors();

    $siswa = Siswa::where('calon_siswa_id', $calon->id)->first();

    expect($siswa)->not->toBeNull()
        ->and($siswa->nama_ayah)->toBe('Bapak Ahmad')
        ->and($siswa->pekerjaan_ibu)->toBe('IRT')
        ->and($siswa->no_hp_orang_tua)->toBe('081234567890');
});

test('import penempatan mengisi kelas pada siswa yang sudah diterima tanpa menggandakan akun', function () {
    $nisn = '0070030010';
    $calon = calonDiterima($nisn, ['nama_ayah' => 'Bapak Budi'], 'menunggu');

    // PPDB: akun dibuat saat daftar, siswa dibuat saat diterima (tanpa kelas).
    $this->actingAs(adminPpdb())->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima']);

    $siswa = Siswa::where('calon_siswa_id', $calon->id)->first();
    expect($siswa)->not->toBeNull()->and($siswa->kelas_id)->toBeNull();

    $userSiswa = $calon->user;
    $passwordAsli = $userSiswa->password;

    $kelas = Kelas::create(['nama_kelas' => '7A', 'tingkat' => '7']);

    // Admin导出 diterima, isi kelas, lalu import ulang.
    $file = excelSiswa(headerPenempatan(), [
        ['2001', $nisn, 'Calon '.$nisn, '7A', '2026/2027', '', '', '', '', '081298765432'],
    ]);

    $this->actingAs(adminPpdb())
        ->post(route('admin.siswas.import'), ['file' => $file])
        ->assertSessionHas('success');

    $siswa->refresh();

    expect($siswa->kelas_id)->toBe($kelas->id)
        ->and($siswa->nis)->toBe('2001')
        ->and($siswa->no_hp_orang_tua)->toBe('081298765432')
        // Tidak ada siswa ganda untuk NISN yang sama.
        ->and(Siswa::where('nisn', $nisn)->count())->toBe(1)
        // Akun siswa tetap satu, password pendaftar tidak diubah.
        ->and(User::where('username', $nisn)->count())->toBe(1)
        ->and(Hash::check('rahasia-acak-'.$nisn, $passwordAsli))->toBeTrue()
        ->and(Hash::check('rahasia-acak-'.$nisn, $userSiswa->fresh()->password))->toBeTrue()
        // Riwayat kelas terbentuk → anggota rombel.
        ->and(RiwayatKelas::where('siswa_id', $siswa->id)->where('kelas_id', $kelas->id)->exists())->toBeTrue();
});

test('import penempatan memperbarui nomor whatsapp wali murid yang sebelumnya kosong', function () {
    $nisn = '0070030011';
    $calon = calonDiterima($nisn, [], 'menunggu');

    // Terima tanpa data ortu → akun wali dibuat dengan no_whatsapp kosong.
    $this->actingAs(adminPpdb())->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima']);

    $siswa = Siswa::where('calon_siswa_id', $calon->id)->first();
    $wali = $siswa->waliMurids()->first();
    expect($wali)->not->toBeNull()->and($wali->no_whatsapp)->toBeNull();

    Kelas::create(['nama_kelas' => '7B', 'tingkat' => '7']);

    $file = excelSiswa(headerPenempatan(), [
        ['', $nisn, 'Calon '.$nisn, '7B', '2026/2027', 'Bapakudi', '', '', '', '081200011122'],
    ]);

    $this->actingAs(adminPpdb())->post(route('admin.siswas.import'), ['file' => $file])->assertSessionHas('success');

    expect($wali->fresh()->no_whatsapp)->toBe('081200011122');
});

test('import penempatan menggagalkan kelas yang tidak dikenal', function () {
    $nisn = '0070030012';
    $calon = calonDiterima($nisn, [], 'menunggu');

    $this->actingAs(adminPpdb())->patch(route('admin.calon-siswas.status', $calon), ['status' => 'diterima']);

    $file = excelSiswa(headerPenempatan(), [
        ['2003', $nisn, 'Calon '.$nisn, '9Z', '2026/2027', '', '', '', '', ''],
    ]);

    $this->actingAs(adminPpdb())
        ->post(route('admin.siswas.import'), ['file' => $file])
        ->assertSessionHas('error');

    expect(Siswa::where('nisn', $nisn)->first()->kelas_id)->toBeNull();
});

test('import dengan nisn baru tetap membuat siswa dan akun', function () {
    $nisn = '0070030013';
    Kelas::create(['nama_kelas' => '7C', 'tingkat' => '7']);
    TahunAjaran::create([
        'nama_tahun_ajaran' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'status_aktif' => true,
    ]);

    $file = excelSiswa(headerPenempatan(), [
        ['2004', $nisn, 'Siswa Baru', '7C', '2026/2027', 'Ayah', 'Petani', 'Ibu', 'IRT', '081333333333'],
    ]);

    $this->actingAs(adminPpdb())->post(route('admin.siswas.import'), ['file' => $file])->assertSessionHas('success');

    $siswa = Siswa::where('nisn', $nisn)->first();

    expect($siswa)->not->toBeNull()
        ->and($siswa->user->hasRole('siswa'))->toBeTrue()
        // Siswa manual/import baru memakai password = NISN.
        ->and(Hash::check($nisn, $siswa->user->password))->toBeTrue()
        ->and(RiwayatKelas::where('siswa_id', $siswa->id)->exists())->toBeTrue();
});

test('export calon diterima hanya memuat yang berstatus diterima', function () {
    $nisn = '0070030020';
    $diterima = calonDiterima('0070030020', ['nama_ayah' => 'Bapak Terima'], 'menunggu');
    $menunggu = calonDiterima('0070030021', [], 'menunggu');

    $this->actingAs(adminPpdb())->patch(route('admin.calon-siswas.status', $diterima), ['status' => 'diterima']);

    $response = $this->actingAs(adminPpdb())->get(route('admin.calon-siswas.export-diterima'));

    $response->assertOk();

    $csv = (new CalonDiterimaExport)->array();
    $nisnList = array_column($csv, 1);

    expect($nisnList)->toContain('0070030020')
        ->and($nisnList)->not->toContain('0070030021');
});
