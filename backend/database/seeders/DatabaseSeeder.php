<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed the application's database.
 *
 * Isi default adalah DATA MASTER yang bisa dibaca orang: profil, PPDB, kelas
 * 7A-9C, 15 guru bernama, 90 siswa. Itu yang dipakai `migrate:fresh --seed`.
 *
 * Seeder demo TIDAK ikut di sini. `RiwayatMultiTahunSeeder` memanggil
 * `ProsesKenaikanAction` yang memproses SEMUA siswa aktif di tahun asal, jadi
 * kalau ia ikut jalan, siswa-siswa data master ikut naik kelas dan kelas 7A/7B
 * jadi kosong. Memisahkannya membuat data development tetap rapi, dan seeder
 * demo tetap tersedia kapan saja lewat perintahnya sendiri — lihat
 * `docs/PANDUAN_UJI_KEPUTUSAN_AKADEMIK.md`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate, bukan factory()->create(): `users.email` UNIQUE, jadi
        // pemanggilan kedua akan gagal dengan UniqueConstraintViolationException
        // sebelum seeder lain sempat jalan sama sekali. `username` juga UNIQUE dan
        // NOT NULL, sedangkan factory mengisinya dengan `fake()->unique()` yang
        // selalu berubah tiap pemanggilan, jadi keduanya harus ditulis eksplisit.
        // Password di-*hash* oleh cast 'hashed' pada model User.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'username' => 'testuser',
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            MenuSeeder::class,
            PpdbSeeder::class,
            PengaturanSeeder::class,
            ProfilSekolahSeeder::class,
            BrosurSeeder::class,
            GaleriSeeder::class,
            // Data master untuk development: kelas 7A-9C, 15 guru, 90 siswa.
            //
            // `AkademikSeeder` sengaja TIDAK dipanggil di sini. File itu tetap
            // ada karena ~40 file test memakainya sebagai fixture dengan kelas
            // 7A-7D dan "Guru A"/"Guru D" yang mereka hardcode. Mengganti isi
            // fixture itu akan merusak test yang tidak ada hubungannya dengan
            // data master. `DatabaseSeeder` sendiri tidak dipanggil oleh test
            // mana pun, jadi pemisahan ini tidak mengganggu test sama sekali.
            DataMasterRealSeeder::class,
        ]);
    }
}
