<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
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
            AkademikSeeder::class,
            DemoSiswaSeeder::class,
            DemoKenaikanSeeder::class,
            RombelSeeder::class,
            DemoElearningSeeder::class,
            DemoCbtSeeder::class,
        ]);
    }
}
