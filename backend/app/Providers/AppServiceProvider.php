<?php

namespace App\Providers;

use App\Models\Brosur;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\Menu;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Observers\SiswaObserver;
use Illuminate\Support\Facades\Blade;
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
            // Nama sekolah jadi satu sumber untuk navbar, footer, dan halaman
            // tentang. Sebelumnya tiap view punya fallback sendiri yang
            // berbeda-beda ("SMP Harapan Bangsa" vs "SMK Negeri 1 Ngaglik"), jadi
            // nama yang tampil berbeda antar halaman. Fallback sengaja netral:
            // lebih baik tampil "Sekolah" daripada menyalin nama sekolah yang
            // salah ke halaman publik.
            $view->with('namaSekolah', trim((string) $profil?->nama_sekolah) ?: 'Sekolah');
            $view->with('jumlahSiswaAktif', Schema::hasTable('siswas') ? Siswa::where('is_aktif', true)->count() : 0);
            $view->with('jumlahGuruAktif', Schema::hasTable('gurus') ? Guru::where('is_aktif', true)->count() : 0);

            // Usia sekolah dihitung dari tahun berdiri di profil. Null kalau
            // profil belum diisi, supaya halaman publik tidak mengarang angka.
            $view->with('lamaBerdiri', $profil?->tahun_berdiri
                ? now()->year - (int) $profil->tahun_berdiri
                : null);
        });

        /*
         * Footer dirender di semua halaman publik, tapi section "#unduhan" dan
         * "#galeri" hanya muncul kalau isinya ada. Tanpa ini, dua dari enam
         * tautan footer mati setiap kali tabelnya kosong.
         *
         * exists() dipakai supaya tabelnya tidak ditarik utuh hanya untuk
         * dicek. Syaratnya harus sama persis dengan syarat section di
         * unduhan.blade.php dan galeri.blade.php, kalau tidak tautan dan
         * section bisa berbeda pendapat.
         */
        View::composer('spmb.partials.footer', function ($view) {
            $view->with('adaBerkasUnduhan', Schema::hasTable('brosurs')
                && Brosur::aktif()->whereNotNull('image_path')->where('image_path', '!=', '')->exists());

            $view->with('adaGaleri', Schema::hasTable('galeris') && (
                Galeri::aktif()->foto()->whereNotNull('image_path')->where('image_path', '!=', '')->exists()
                || Galeri::aktif()->prestasi()->exists()
            ));
        });

        // Font dari `fonts` di vite.config.js tidak ikut otomatis oleh
        // directive @vite, dan Laravel belum menyediakan directive untuk
        // Vite::fonts(). Tanpa ini, file .woff-nya ikut terbangun tapi tidak
        // pernah dimuat dan halaman jatuh ke font sistem.
        Blade::directive('fonts', function ($expression) {
            return "<?php echo app(Illuminate\\Foundation\\Vite::class)->fonts({$expression}); ?>";
        });
    }
}
