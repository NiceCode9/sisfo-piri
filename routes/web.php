<?php

use App\Http\Controllers\Admin\BiayaPendaftaranController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\GelombangController;
use App\Http\Controllers\Admin\JadwalPpdbController;
use App\Http\Controllers\Admin\JalurPendaftaranController;
use App\Http\Controllers\Admin\KuotaPendaftaranController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\RencanaAngsuranController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Siswa\DashboardController;
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
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('menus', MenuController::class)->except(['show']);
    Route::resource('calon-siswas', CalonSiswaController::class);
    Route::patch('calon-siswas/{calon_siswa}/status', [CalonSiswaController::class, 'updateStatus'])->name('calon-siswas.status');
    Route::patch('calon-siswas/{calon_siswa}/berkas', [CalonSiswaController::class, 'verifyBerkas'])->name('calon-siswas.berkas');
    Route::delete('calon-siswas/sertifikat/{sertifikat}', [CalonSiswaController::class, 'destroySertifikat'])->name('calon-siswas.sertifikat.destroy');
    Route::resource('biaya-pendaftarans', BiayaPendaftaranController::class)->except(['show']);
    Route::resource('pengumumans', PengumumanController::class)->except(['show']);
    Route::resource('gelombangs', GelombangController::class)->except(['show']);
    Route::resource('tahun-ajarans', TahunAjaranController::class)->except(['show']);
    Route::resource('jalur-pendaftarans', JalurPendaftaranController::class)->except(['show']);
    Route::resource('jadwal-ppdbs', JadwalPpdbController::class)->except(['show']);
    Route::resource('kuota-pendaftarans', KuotaPendaftaranController::class)->except(['show']);
    Route::get('pembayarans/export/excel', [PembayaranController::class, 'exportExcel'])->name('pembayarans.export.excel');
    Route::get('pembayarans/export/pdf', [PembayaranController::class, 'exportPdf'])->name('pembayarans.export.pdf');
    Route::resource('pembayarans', PembayaranController::class);
    Route::patch('pembayarans/{pembayaran}/status', [PembayaranController::class, 'updateStatus'])->name('pembayarans.status');
    Route::post('pembayarans/{pembayaran}/rencana', [RencanaAngsuranController::class, 'store'])->name('rencana.store');
    Route::patch('rencana-angsuran/{rencana}/batal', [RencanaAngsuranController::class, 'batal'])->name('rencana.batal');
    Route::patch('detail-angsuran/{detail}/denda', [RencanaAngsuranController::class, 'updateDenda'])->name('rencana.denda');
});
