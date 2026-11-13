<?php

use Illuminate\Support\Facades\RateLimiter;

test('limiter bernama tetap terdaftar saat route di-cache', function () {
    // Produksi memakai route:cache. Saat cache route ada, closure `then` pada
    // bootstrap/app.php dilewati, jadi limiter tidak boleh didaftarkan di sana.
    $this->artisan('route:cache')->assertSuccessful();

    try {
        $this->refreshApplication();

        foreach (['heartbeat', 'pendaftaran', 'elearning-kumpul', 'cbt-login', 'cbt-write', 'absensi-scan'] as $limiter) {
            expect(RateLimiter::limiter($limiter))->toBeInstanceOf(Closure::class);
        }
    } finally {
        $this->artisan('route:clear');
    }
});
