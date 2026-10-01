<?php

namespace App\Providers;

use App\Models\Guru;
use App\Models\Menu;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Observers\SiswaObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Siswa::observe(SiswaObserver::class);

        View::composer('layouts.partials.sidebar', function ($view) {
            $user = auth()->user();

            $menus = Menu::parents()
                ->userCanAccess($user)
                ->with(['children' => fn ($query) => $query->userCanAccess($user), 'permissions'])
                ->get();

            $view->with('adminMenus', $menus);
        });

        // Profil sekolah + statistik untuk seluruh halaman publik (spmb.*).
        View::composer('spmb.*', function ($view) {
            $adaProfil = Schema::hasTable('profil_sekolahs');
            $profil = $adaProfil ? ProfilSekolah::aktif() : null;

            $view->with('profileSekolah', $profil);
            $view->with('jumlahSiswaAktif', Schema::hasTable('siswas') ? Siswa::where('is_aktif', true)->count() : 0);
            $view->with('jumlahGuruAktif', Schema::hasTable('gurus') ? Guru::where('is_aktif', true)->count() : 0);

            // Usia sekolah dihitung dari tahun berdiri di profil. Null kalau
            // profil belum diisi, supaya halaman publik tidak mengarang angka.
            $view->with('lamaBerdiri', $profil?->tahun_berdiri
                ? now()->year - (int) $profil->tahun_berdiri
                : null);
        });
    }
}
