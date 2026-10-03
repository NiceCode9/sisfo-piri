<?php

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(PpdbSeeder::class);
    $this->seed(AkademikSeeder::class);
});

/*
 * AkademikSeeder: Guru A = wali 7A + pengampu MTK di 7A/7B.
 * Guru D = wali 7C + pengampu MTK di 7C/7D.
 * 7B dan 7D tidak punya wali — dipakai sebagai kasus "diampu mapel saja".
 */

function rombelKelasAkademik(string $nama): Rombel
{
    return Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $nama))->firstOrFail();
}

function guruDenganNama(string $nama): Guru
{
    return Guru::where('nama', $nama)->firstOrFail();
}

/**
 * @return array{Siswa, User}
 */
function buatSiswaDiRombelAkademik(Rombel $rombel, string $kode): array
{
    $user = User::factory()->create([
        'username' => "siswa-akd-{$kode}",
        'name' => "Murid {$kode}",
    ]);
    $user->assignRole('siswa');

    $siswa = Siswa::create([
        'user_id' => $user->id,
        'nis' => '97'.$kode,
        'nisn' => '008'.str_pad($kode, 7, '0', STR_PAD_LEFT),
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'kelas_id' => $rombel->kelas_id,
        'is_aktif' => true,
        'no_hp_orang_tua' => '0812997'.$kode,
    ]);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    return [$siswa, $user];
}

function adminAkademik(): User
{
    $user = User::factory()->create(['username' => 'admin-keputusan-akademik']);
    $user->assignRole('admin');

    return $user;
}

/*
 * Perbaikan 1 — rekap absensi punya fail-open yang sama dengan yang sudah
 * dihapus di modul tugas: guru tanpa kelas wali mendapat seluruh rombel.
 */
test('guru tanpa penugasan tidak melihat rekap absensi rombel lain', function () {
    $rombel = rombelKelasAkademik('7A');
    [$siswa] = buatSiswaDiRombelAkademik($rombel, '01');
    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'hadir',
    ]);

    $user = User::factory()->create(['username' => 'guru-tanpa-kelas-akd']);
    $user->assignRole('guru');
    Guru::create([
        'user_id' => $user->id,
        'nip' => '197001011995011001',
        'nama' => 'Guru Tanpa Kelas',
        'jenis_kelamin' => 'L',
        'is_aktif' => true,
    ]);

    $this->actingAs($user)->get(route('admin.absensis.rekap'))
        ->assertOk()
        ->assertDontSee($rombel->kelas->nama_kelas);
});

test('guru pengampu mapel tanpa wali bisa membuka rekap absensi', function () {
    $rombel = rombelKelasAkademik('7B'); // wali kosong, Guru A pengampu MTK
    $guru = guruDenganNama('Guru A');
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.absensis.rekap', ['rombel_id' => $rombel->id]))
        ->assertOk();
});

test('guru ditolak dari rekap absensi rombel milik guru lain', function () {
    $rombel = rombelKelasAkademik('7D'); // Guru D
    $guru = guruDenganNama('Guru A');
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.absensis.rekap', ['rombel_id' => $rombel->id]))
        ->assertForbidden();
});

/*
 * Perbaikan 2 — hapus rombel dulu hanya mengecek penugasan guru, padahal
 * seluruh tabel historis memakai cascadeOnDelete.
 */
test('rombel dengan kehadiran tidak dapat dihapus', function () {
    $rombel = rombelKelasAkademik('7A');

    // Penugasan dari seeder dihapus dulu supaya yang menahan hapus benar-benar
    // hanya absensi — kalau tidak, cek `pengampus` yang memblokir dan test ini
    // lolos tanpa pernah menguji absensi.
    $rombel->pengampus()->delete();

    [$siswa] = buatSiswaDiRombelAkademik($rombel, '11');
    Absensi::create([
        'rombel_id' => $rombel->id,
        'siswa_id' => $siswa->id,
        'tanggal' => now()->toDateString(),
        'status' => 'hadir',
    ]);

    $this->actingAs(adminAkademik())
        ->delete(route('admin.rombels.destroy', $rombel))
        ->assertRedirect();

    expect($rombel->fresh())->not->toBeNull();
    expect(Absensi::where('rombel_id', $rombel->id)->exists())->toBeTrue();
});

test('rombel dengan nilai tugas tidak dapat dihapus', function () {
    $rombel = rombelKelasAkademik('7A');
    $rombel->pengampus()->delete();

    [$siswa] = buatSiswaDiRombelAkademik($rombel, '12');

    $tugas = Tugas::create([
        'rombel_id' => $rombel->id,
        'mata_pelajaran_id' => MataPelajaran::aktif()->firstOrFail()->id,
        'judul' => 'Tugas Uji Hapus',
        'is_aktif' => true,
    ]);
    PengumpulanTugas::create(['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id, 'nilai' => 80]);

    $this->actingAs(adminAkademik())
        ->delete(route('admin.rombels.destroy', $rombel))
        ->assertRedirect();

    expect($rombel->fresh())->not->toBeNull();
    expect(PengumpulanTugas::where('tugas_id', $tugas->id)->exists())->toBeTrue();
});

test('rombel kosong tetap dapat dihapus', function () {
    $rombel = rombelKelasAkademik('7B');
    $rombel->pengampus()->delete();

    $this->actingAs(adminAkademik())
        ->delete(route('admin.rombels.destroy', $rombel))
        ->assertRedirect(route('admin.rombels.index'));

    expect(Rombel::find($rombel->id))->toBeNull();
});

/*
 * Perbaikan 3 — constraint unik absensi memakai (siswa, tanggal) sehingga
 * struktural menolak satu siswa punya lebih dari satu konteks rombel pada
 * tanggal sama.
 */
test('satu siswa boleh punya kehadiran di dua rombel pada tanggal sama', function () {
    $tanggal = now()->toDateString();

    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');

    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '21');
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'pindah',
    ]);

    Absensi::create([
        'rombel_id' => $rombelA->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $tanggal,
        'status' => 'hadir',
    ]);
    Absensi::create([
        'rombel_id' => $rombelB->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $tanggal,
        'status' => 'alpa',
    ]);

    expect(Absensi::where('siswa_id', $siswa->id)->where('tanggal', $tanggal)->count())->toBe(2);
});

test('simpan grid absensi tidak menimpa baris rombel lain', function () {
    $tanggal = now()->toDateString();
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');

    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '31');
    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    Absensi::create([
        'rombel_id' => $rombelA->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $tanggal,
        'status' => 'hadir',
    ]);

    $this->actingAs(adminAkademik())->post(route('admin.absensis.batch'), [
        'rombel_id' => $rombelB->id,
        'tanggal' => $tanggal,
        'status' => [$siswa->id => 'sakit'],
    ])->assertRedirect();

    // Baris kelas lama harus utuh: masih rombel A, status hadir.
    $lama = Absensi::where('siswa_id', $siswa->id)->where('rombel_id', $rombelA->id)->first();
    expect($lama)->not->toBeNull()->and($lama->status)->toBe('hadir');

    $baru = Absensi::where('siswa_id', $siswa->id)->where('rombel_id', $rombelB->id)->first();
    expect($baru)->not->toBeNull()->and($baru->status)->toBe('sakit');
});

test('rekap kehadiran menghitung satu tanggalnya sekali meski ada di dua rombel', function () {
    $bulan = now()->format('Y-m');
    $tanggal = now()->toDateString();

    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');

    [$anak] = buatSiswaDiRombelAkademik($rombelA, '41');
    RiwayatKelas::create([
        'siswa_id' => $anak->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'aktif',
    ]);

    // Dua baris untuk tanggal sama karena siswa tercatat di dua rombel.
    // Rekap harus menghitungnya sebagai SATU hari hadir, bukan dua.
    Absensi::create(['rombel_id' => $rombelA->id, 'siswa_id' => $anak->id, 'tanggal' => $tanggal, 'status' => 'hadir']);
    Absensi::create(['rombel_id' => $rombelB->id, 'siswa_id' => $anak->id, 'tanggal' => $tanggal, 'status' => 'hadir']);

    $ortu = WaliMurid::where('siswa_id', $anak->id)->firstOrFail()->user;
    $waliMurid = WaliMurid::where('siswa_id', $anak->id)->firstOrFail();

    $html = $this->actingAs($ortu)
        ->get(route('ortu.anak', $waliMurid).'?periode=bulan&acuan='.$bulan)
        ->assertOk()
        ->getContent();

    // Angka yang tampil harus "Hadir: 1", bukan "Hadir: 2".
    expect($html)->toContain('Hadir: 1');
    expect($html)->not->toContain('Hadir: 2');
});

/*
 * Perbaikan 4 — constraint unik riwayat kelas dan koreksi lewat UI.
 */
test('riwayat kelas menolak duplikat untuk pasangan siswa kelas tahun yang sama', function () {
    $rombel = rombelKelasAkademik('7A');
    [$siswa] = buatSiswaDiRombelAkademik($rombel, '51');

    // Baris pertama sudah dibuat oleh helper.
    $this->expectException(QueryException::class);

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombel->kelas_id,
        'tahun_ajaran_id' => $rombel->tahun_ajaran_id,
        'status' => 'aktif',
    ]);
});

test('riwayat kelas boleh dua baris untuk satu tahun bila kelas berbeda', function () {
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');
    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '52');

    RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'pindah',
    ]);

    expect($siswa->riwayatKelas()->count())->toBe(2);
});

test('mengubah riwayat menjadi aktif melepas baris aktif lain di tahun sama', function () {
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');
    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '53');

    $lama = RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'pindah',
    ]);

    $this->actingAs(adminAkademik())
        ->put(route('admin.riwayat-kelas.update', $lama), [
            'kelas_id' => $rombelB->kelas_id,
            'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
            'status' => 'aktif',
        ])->assertRedirect();

    expect($lama->fresh()->status)->toBe('aktif');
    expect($siswa->riwayatKelas()->where('status', 'aktif')->count())->toBe(1);
});

test('koreksi riwayat ditolak bila bentrok dengan baris lain', function () {
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');
    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '54');

    $lain = RiwayatKelas::create([
        'siswa_id' => $siswa->id,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
        'status' => 'pindah',
    ]);

    $asal = $siswa->riwayatKelas()->where('kelas_id', $rombelA->kelas_id)->firstOrFail();

    $this->actingAs(adminAkademik())
        ->put(route('admin.riwayat-kelas.update', $asal), [
            'kelas_id' => $rombelB->kelas_id,
            'tahun_ajaran_id' => $rombelB->tahun_ajaran_id,
            'status' => 'pindah',
        ])->assertSessionHasErrors('kelas_id');

    expect($lain->fresh())->not->toBeNull();
});

test('hapus riwayat tanpa konfirmasi ditolak', function () {
    $rombel = rombelKelasAkademik('7A');
    [$siswa] = buatSiswaDiRombelAkademik($rombel, '55');
    $baris = $siswa->riwayatKelas()->firstOrFail();

    $this->actingAs(adminAkademik())
        ->delete(route('admin.riwayat-kelas.destroy', $baris))
        ->assertRedirect();

    expect(RiwayatKelas::find($baris->id))->not->toBeNull();
});

test('hapus riwayat berjalan bila konfirmasi dicentang', function () {
    $rombel = rombelKelasAkademik('7A');
    [$siswa] = buatSiswaDiRombelAkademik($rombel, '56');
    $baris = $siswa->riwayatKelas()->firstOrFail();

    $this->actingAs(adminAkademik())
        ->delete(route('admin.riwayat-kelas.destroy', $baris), ['sadar' => '1'])
        ->assertRedirect();

    expect(RiwayatKelas::find($baris->id))->toBeNull();
});

test('koreksi riwayat tidak tersedia bagi guru', function () {
    $rombel = rombelKelasAkademik('7A');
    [$siswa] = buatSiswaDiRombelAkademik($rombel, '57');
    $baris = $siswa->riwayatKelas()->firstOrFail();

    $guru = guruDenganNama('Guru A');
    $guru->user->assignRole('guru');

    $this->actingAs($guru->user)->get(route('admin.riwayat-kelas.index'))->assertForbidden();
    $this->actingAs($guru->user)->put(route('admin.riwayat-kelas.update', $baris), [
        'kelas_id' => $baris->kelas_id,
        'tahun_ajaran_id' => $baris->tahun_ajaran_id,
        'status' => 'aktif',
    ])->assertForbidden();
});

/*
 * Perbaikan 5 — ganti kelas lewat form siswa menutup riwayat lama sebagai
 * `pindah`. Sebelumnya baris lama dibiarkan `aktif`, dan karena
 * Rombel::anggotaIds() mengabaikan status, siswa jadi anggota dua rombel.
 */
test('ganti kelas menutup riwayat lama dan membuka yang baru', function () {
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');
    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '61');

    $tahun = $rombelA->tahun_ajaran_id;

    $this->actingAs(adminAkademik())->put(route('admin.siswas.update', $siswa), [
        'nisn' => $siswa->nisn,
        'nama' => $siswa->user->name,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $tahun,
        'is_aktif' => 1,
    ])->assertRedirect();

    $riwayats = $siswa->riwayatKelas()->where('tahun_ajaran_id', $tahun)->get();

    expect($riwayats)->toHaveCount(2);
    expect($riwayats->where('kelas_id', $rombelA->kelas_id)->first()->status)->toBe('pindah');
    expect($riwayats->where('kelas_id', $rombelB->kelas_id)->first()->status)->toBe('aktif');
    expect($riwayats->where('status', 'aktif')->count())->toBe(1);
});

test('pindah kelas memindahkan keanggotaan aktif tapi menjaga histori', function () {
    $rombelA = rombelKelasAkademik('7A');
    $rombelB = rombelKelasAkademik('7B');
    [$siswa] = buatSiswaDiRombelAkademik($rombelA, '62');

    $this->actingAs(adminAkademik())->put(route('admin.siswas.update', $siswa), [
        'nisn' => $siswa->nisn,
        'nama' => $siswa->user->name,
        'kelas_id' => $rombelB->kelas_id,
        'tahun_ajaran_id' => $rombelA->tahun_ajaran_id,
        'is_aktif' => 1,
    ])->assertRedirect();

    // Operasional: hanya kelas baru yang boleh mencatat absensi/berkas.
    expect($rombelB->anggotaIdsAktif())->toContain($siswa->id);
    expect($rombelA->anggotaIdsAktif())->not->toContain($siswa->id);

    // Historis: kelas lama tetap menyimpan jejaknya supaya rekap tahun itu
    // tidak kehilangan catatan yang sudah tercatat.
    expect($rombelA->anggotaIds())->toContain($siswa->id);
});
