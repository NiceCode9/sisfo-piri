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
     * Handle the Siswa "updated" event.
     *
     * Data orang-tua bisa baru terisi belakangan (mis. saat import penempatan kelas
     * PPDB), sementara akun WaliMurid sudah dibuat saat siswa diterima dengan
     * no_whatsapp kosong dan usernamecadangan. Perbarui di sini agar akun ortu
     * ikut memakai nomor telepon dan tidak tertinggal kosong.
     */
    public function updated(Siswa $siswa): void
    {
        if (! $siswa->wasChanged('no_hp_orang_tua') || ! $siswa->no_hp_orang_tua) {
            return;
        }

        $wali = WaliMurid::where('siswa_id', $siswa->id)->first();

        if (! $wali) {
            return;
        }

        if (! $wali->no_whatsapp) {
            $wali->update(['no_whatsapp' => $siswa->no_hp_orang_tua]);
        }

        static::selaraskanUsername($wali->user, $siswa);
    }

    /**
     * Username akun orang-tua = nomor telepon wali (hanya digit).
     * Bila nomor belum tersedia, jatuh ke bentuk cadangan `ortu-{nisn}`.
     * Password tetap memakai NISN anak.
     */
    public static function usernameUntukWali(Siswa $siswa): string
    {
        $nomor = static::normalisasiNomor($siswa->no_hp_orang_tua);

        return $nomor ?? static::usernameCadangan($siswa);
    }

    /**
     * Buang semua karakter selain digit (spasi, tanda hubung, tanda plus).
     */
    public static function normalisasiNomor(?string $nomor): ?string
    {
        if ($nomor === null) {
            return null;
        }

        $digit = preg_replace('/\D+/', '', $nomor);

        return ($digit === null || $digit === '') ? null : $digit;
    }

    private static function usernameCadangan(Siswa $siswa): string
    {
        return 'ortu-'.$siswa->nisn;
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

        $user ??= static::buatUserWali($siswa);

        if (! $user) {
            return null;
        }

        $user->assignRole('orang-tua');

        WaliMurid::firstOrCreate(
            ['user_id' => $user->id, 'siswa_id' => $siswa->id],
            ['no_whatsapp' => $siswa->no_hp_orang_tua, 'hubungan' => 'orang-tua'],
        );

        return $user;
    }

    /**
     * Username preferred adalah nomor telepon. Bila username itu sudah dipakai
     * akun non-wali (mis. akun siswa), jatuh ke username cadangan agar tidak
     * mengambil alih akun tersebut.
     */
    private static function buatUserWali(Siswa $siswa): ?User
    {
        $username = static::usernameUntukWali($siswa);

        $bentrok = User::where('username', $username)->first();

        if ($bentrok && ! WaliMurid::where('user_id', $bentrok->id)->exists()) {
            $username = static::usernameCadangan($siswa);
        }

        return User::firstOrCreate(
            ['username' => $username],
            [
                'name' => $siswa->nama_ayah ?? $siswa->nama_ibu ?? 'Wali '.$siswa->user?->name,
                'email' => $username.'@ortu.example.com',
                'password' => $siswa->nisn,
            ],
        );
    }

    /**
     * Rename username akun wali menjadi nomor telepon terbaru bila belum dipakai.
     */
    private static function selaraskanUsername(?User $user, Siswa $siswa): void
    {
        if (! $user) {
            return;
        }

        $username = static::usernameUntukWali($siswa);

        if ($username === $user->username) {
            return;
        }

        if (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
            return; // nomor sudah dipakai akun lain — biarkan username lama
        }

        $user->update(['username' => $username]);
    }
}
