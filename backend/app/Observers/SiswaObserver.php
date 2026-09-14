<?php

namespace App\Observers;

use App\Models\Siswa;
use App\Models\User;
use App\Models\WaliMurid;

class SiswaObserver
{
    /**
     * Handle the Siswa "created" event.
     */
    public function created(Siswa $siswa): void
    {
        static::daftarkanOrangTua($siswa);
    }

    /**
     * Buat (atau pakai ulang) akun orang-tua untuk satu siswa.
     * Idempoten: kakak-beradik dengan no WA sama memakai satu akun.
     * Dilewati bila NISN kosong (username tidak bisa dibentuk).
     */
    public static function daftarkanOrangTua(Siswa $siswa): ?User
    {
        if (! $siswa->nisn) {
            return null;
        }

        $user = null;

        if ($siswa->no_hp_orang_tua) {
            $user = WaliMurid::where('no_whatsapp', $siswa->no_hp_orang_tua)->first()?->user;
        }

        $user ??= User::firstOrCreate(
            ['username' => 'ortu-'.$siswa->nisn],
            [
                'name' => $siswa->nama_ayah ?? $siswa->nama_ibu ?? 'Wali '.$siswa->user?->name,
                'email' => 'ortu-'.$siswa->nisn.'@example.com',
                'password' => $siswa->nisn,
            ],
        );
        $user->assignRole('orang-tua');

        WaliMurid::firstOrCreate(
            ['user_id' => $user->id, 'siswa_id' => $siswa->id],
            ['no_whatsapp' => $siswa->no_hp_orang_tua, 'hubungan' => 'orang-tua'],
        );

        return $user;
    }
}
