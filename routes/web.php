<?php

use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SpmbController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpmbController::class, 'home'])->name('spmb.home');
Route::get('/pendaftaran', [SpmbController::class, 'create'])->name('spmb.pendaftaran');
Route::post('/pendaftaran', [SpmbController::class, 'store'])->name('spmb.store')->middleware('throttle:5,1');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('menus', MenuController::class)->except(['show']);
    Route::resource('calon-siswas', CalonSiswaController::class);
    Route::patch('calon-siswas/{calon_siswa}/status', [CalonSiswaController::class, 'updateStatus'])->name('calon-siswas.status');
    Route::patch('calon-siswas/{calon_siswa}/berkas', [CalonSiswaController::class, 'verifyBerkas'])->name('calon-siswas.berkas');
});
