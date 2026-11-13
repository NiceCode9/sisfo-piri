<?php

namespace App\Providers;

use App\Models\Brosur;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\Menu;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Observers\SiswaObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->registerRateLimiters();

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

    /**
     * Didaftarkan di provider, bukan di closure `then` pada bootstrap/app.php:
     * closure itu hanya berjalan saat route dimuat tanpa cache, sehingga semua
     * limiter bernama hilang di production yang memakai `route:cache`.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('heartbeat', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        // Pendaftaran publik. Batas lama 5/menit terlalu kecil untuk sekolah:
        // satu IP/NAT bersama membuat seluruh orang tua di balik proxy yang
        // sama saling mengunci, dan form upload 5 berkas mudah gagal validasi
        // sehingga percobaan ulang ikut kehabisan jatah.
        RateLimiter::for('pendaftaran', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(fn () => redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['jalur_pendaftaran_id' => 'Terlalu banyak permintaan pendaftaran. Silakan coba lagi beberapa saat lagi.']));
        });

        // Unggah E-Learning menerima berkas 10 MB per percobaan, jadi batas
        // throttle bawaan terlalu longgar: satu akun bisa mengulang unggahan
        // dan memaksa penyimpanan penuh tanpa ada validation yang menolak.
        RateLimiter::for('elearning-kumpul', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        // Login CBT menerima identifier + password tanpa throttle
        // sebelumnya, sehingga satu alamat IP bisa menebak kredensial
        // siswa tanpa batas. 10/menit per IP masih jauh di atas kebutuhan
        // nyata (siswa hanya login sekali per sesi ujian).
        RateLimiter::for('cbt-login', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Terlalu banyak percobaan login. Coba lagi beberapa saat lagi.',
                ], 429));
        });

        // Autosave dijadwal ulang tiap 800 ms saat siswa mengubah pilihan,
        // jadi satu siswa bisa menghasilkan banyak POST /exam/answer dalam
        // satu menit. 90/menit menutup pemakaian normal tanpa memotong
        // siswa yang sedang mengubah jawaban berulang.
        RateLimiter::for('cbt-write', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->id ?: $request->ip());
        });

        // Scan QR absensi. Kamera memindai beberapa kali per detik selama
        // kartu berada di dalam frame, jadi batas throttle bawaan terlalu
        // longgar: satu perangkat yang macet bisa membanjiri server dengan
        // permintaan yang isinya identik. 120/menit per pengguna masih jauh
        // di atas kebutuhan nyata: satu kelas 40 siswa dengan jeda scan
        // hanya sekitar 40 permintaan.
        RateLimiter::for('absensi-scan', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('komentar-berita', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
