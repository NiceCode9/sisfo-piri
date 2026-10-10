<?php

use App\Http\Middleware\CheckMaintenance;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->prefix('api/cbt')->group(base_path('routes/api_cbt.php'));
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
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->append(CheckMaintenance::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
