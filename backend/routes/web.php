<?php

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Admin\BiayaPendaftaranController;
use App\Http\Controllers\Admin\BrosurController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\GaleriController;
use App\Http\Controllers\Admin\GelombangController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\JadwalPpdbController;
use App\Http\Controllers\Admin\JalurPendaftaranController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\KenaikanKelasController;
use App\Http\Controllers\Admin\KuotaPendaftaranController;
use App\Http\Controllers\Admin\MataPelajaranController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PembayaranLainnyaController;
use App\Http\Controllers\Admin\PengampuController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\ProfilSekolahController;
use App\Http\Controllers\Admin\RencanaAngsuranController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RombelController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Ortu\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Ortu\ProfilController as OrtuProfilController;
use App\Http\Controllers\Siswa\DashboardController;
use App\Http\Controllers\Siswa\ProfilController;
use App\Http\Controllers\Siswa\RiwayatController;
use App\Http\Controllers\SpmbController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpmbController::class, 'home'])->name('spmb.home');
Route::get('/pendaftaran', [SpmbController::class, 'create'])->name('spmb.pendaftaran');
Route::get('/tentang', [SpmbController::class, 'about'])->name('spmb.about');
Route::post('/pendaftaran', [SpmbController::class, 'store'])->name('spmb.store')->middleware('throttle:5,1');
Route::get('/pengumuman', [SpmbController::class, 'pengumumanIndex'])->name('spmb.pengumuman.index');
Route::get('/pengumuman/{pengumuman}', [SpmbController::class, 'pengumumanShow'])->name('spmb.pengumuman.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('siswa')->name('siswa.')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/kelas', [RiwayatController::class, 'kelas'])->name('kelas');
    Route::get('/absensi', [RiwayatController::class, 'absensi'])->name('absensi');
});

Route::prefix('ortu')->name('ortu.')->middleware(['auth', 'role:orang-tua'])->group(function () {
    Route::get('/dashboard', [OrtuDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [OrtuProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [OrtuProfilController::class, 'update'])->name('profil.update');
    Route::get('/anak/{waliMurid}', [OrtuDashboardController::class, 'show'])->name('anak');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }

        if ($user->hasRole('orang-tua')) {
            return redirect()->route('ortu.dashboard');
        }

        return view('admin.dashboard');
    })->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('menus', MenuController::class)->except(['show']);
    Route::resource('calon-siswas', CalonSiswaController::class);
    Route::patch('calon-siswas/{calon_siswa}/status', [CalonSiswaController::class, 'updateStatus'])->name('calon-siswas.status');
    Route::patch('calon-siswas/{calon_siswa}/berkas', [CalonSiswaController::class, 'verifyBerkas'])->name('calon-siswas.berkas');
    Route::delete('calon-siswas/sertifikat/{sertifikat}', [CalonSiswaController::class, 'destroySertifikat'])->name('calon-siswas.sertifikat.destroy');
    Route::resource('biaya-pendaftarans', BiayaPendaftaranController::class)->except(['show']);
    Route::resource('pengumumans', PengumumanController::class)->except(['show']);
    Route::resource('brosurs', BrosurController::class)->except(['show']);
    Route::resource('galeris', GaleriController::class)->except(['show']);
    Route::resource('gelombangs', GelombangController::class)->except(['show']);
    Route::resource('tahun-ajarans', TahunAjaranController::class)->except(['show']);
    Route::resource('jalur-pendaftarans', JalurPendaftaranController::class)->except(['show']);
    Route::resource('jadwal-ppdbs', JadwalPpdbController::class)->except(['show']);
    Route::resource('kuota-pendaftarans', KuotaPendaftaranController::class)->except(['show']);
    Route::resource('gurus', GuruController::class)->except(['show']);
    Route::resource('mata-pelajarans', MataPelajaranController::class)->except(['show']);
    Route::resource('kelas', KelasController::class)->except(['show'])->parameters(['kelas' => 'kelas']);
    Route::resource('pengampus', PengampuController::class)->except(['show']);
    Route::post('rombels/{rombel}/pengampus/batch', [RombelController::class, 'storePengampuBatch'])->name('rombels.pengampus.batch');
    Route::get('rombels/salin', [RombelController::class, 'salin'])->name('rombels.salin');
    Route::post('rombels/salin', [RombelController::class, 'prosesSalin'])->name('rombels.salin.proses');
    Route::resource('rombels', RombelController::class);
    Route::get('pembayarans/export/excel', [PembayaranController::class, 'exportExcel'])->name('pembayarans.export.excel');
    Route::get('pembayarans/export/pdf', [PembayaranController::class, 'exportPdf'])->name('pembayarans.export.pdf');
    Route::get('pembayarans/{pembayaran}/kwitansi', [PembayaranController::class, 'kwitansi'])->name('pembayarans.kwitansi');
    Route::resource('pembayarans', PembayaranController::class);
    Route::patch('pembayarans/{pembayaran}/status', [PembayaranController::class, 'updateStatus'])->name('pembayarans.status');
    Route::get('pembayaran-lainnyas/export/excel', [PembayaranLainnyaController::class, 'exportExcel'])->name('pembayaran-lainnyas.export.excel');
    Route::get('pembayaran-lainnyas/export/pdf', [PembayaranLainnyaController::class, 'exportPdf'])->name('pembayaran-lainnyas.export.pdf');
    Route::resource('pembayaran-lainnyas', PembayaranLainnyaController::class);
    Route::patch('pembayaran-lainnyas/{pembayaran_lainnya}/status', [PembayaranLainnyaController::class, 'updateStatus'])->name('pembayaran-lainnyas.status');
    Route::post('pembayarans/{pembayaran}/rencana', [RencanaAngsuranController::class, 'store'])->name('rencana.store');
    Route::patch('rencana-angsuran/{rencana}/batal', [RencanaAngsuranController::class, 'batal'])->name('rencana.batal');
    Route::patch('detail-angsuran/{detail}/denda', [RencanaAngsuranController::class, 'updateDenda'])->name('rencana.denda');
    Route::get('profil-sekolah', [ProfilSekolahController::class, 'edit'])->name('profil-sekolah.edit');
    Route::put('profil-sekolah', [ProfilSekolahController::class, 'update'])->name('profil-sekolah.update');
    Route::get('kenaikan-kelas', [KenaikanKelasController::class, 'index'])->name('kenaikan.index');
    Route::post('kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan.proses');
    Route::get('siswas/template', [SiswaController::class, 'template'])->name('siswas.template');
    Route::post('siswas/import', [SiswaController::class, 'import'])->name('siswas.import');
    Route::get('siswas/{siswa}/kartu', [SiswaController::class, 'kartu'])->name('siswas.kartu');
    Route::post('siswas/{siswa}/qr', [SiswaController::class, 'regenerateQr'])->name('siswas.qr');
    Route::resource('siswas', SiswaController::class);
    Route::get('absensis', [AbsensiController::class, 'index'])->name('absensis.index');
    Route::post('absensis/batch', [AbsensiController::class, 'storeBatch'])->name('absensis.batch');
    Route::get('absensis/scan', [AbsensiController::class, 'scan'])->name('absensis.scan');
    Route::post('absensis/scan', [AbsensiController::class, 'storeScan'])->name('absensis.scan.store');
    Route::get('absensis/rekap', [AbsensiController::class, 'rekap'])->name('absensis.rekap');
    Route::get('absensis/rekap/excel', [AbsensiController::class, 'exportExcel'])->name('absensis.rekap.excel');
    Route::get('absensis/rekap/pdf', [AbsensiController::class, 'exportPdf'])->name('absensis.rekap.pdf');
    Route::get('pengaturans', [PengaturanController::class, 'index'])->name('pengaturans.index');
    Route::put('pengaturans', [PengaturanController::class, 'update'])->name('pengaturans.update');
    Route::post('pengaturans/reset', [PengaturanController::class, 'reset'])->name('pengaturans.reset');
});
