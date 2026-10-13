<?php

namespace App\Support;

use App\Models\User;

/**
 * Satu-satunya tempat yang memutuskan "user ini mendarat di dashboard mana".
 *
 * Tiga tempat butuh jawaban itu — brand sidebar, dispatcher `/admin`, dan
 * menu — dan ketiganya dulu punya logikanya sendiri. Begitu role guru dapat
 * dashboard sendiri, menyalin keputusan ke tiga tempat berarti ketiganya bisa
 * berbeda diam-diam. Semua pemanggil wajib lewat sini.
 *
 * Pola yang sama sudah dipakai StatusPendaftaran untuk gate PPDB.
 */
final class RuteBeranda
{
    /**
     * Role yang punya dashboard sekolah (laporan lintas rombel), bukan
     * dashboard kelasnya sendiri.
     *
     * @var array<int, string>
     */
    private const ROLE_ADMIN = ['super-admin', 'admin'];

    public static function untuk(?User $user): string
    {
        if ($user === null) {
            return 'admin.dashboard';
        }

        if ($user->hasRole('siswa')) {
            return 'siswa.dashboard';
        }

        if ($user->hasRole('orang-tua')) {
            return 'ortu.dashboard';
        }

        // Guru dan guru-piket diarahkan ke dashboard mereka. super-admin dan
        // admin tetap di laporan sekolah meski juga memegang role guru.
        if (! $user->hasRole(self::ROLE_ADMIN)
            && ($user->hasRole('guru') || $user->hasRole('guru-piket'))) {
            return 'guru.dashboard';
        }

        return 'admin.dashboard';
    }
}
