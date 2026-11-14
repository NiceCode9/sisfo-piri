<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class JurnalistikUserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 2 pengguna dengan role jurnalistik, ikuti pola firstOrCreate DatabaseSeeder.
        // email unik, username unik.
        Role::firstOrCreate(['name' => 'jurnalistik', 'guard_name' => 'web']);
        $user1 = User::firstOrCreate(
            ['email' => 'jurnalistik1@example.com'],
            [
                'username' => 'jurnalistik1',
                'name' => 'Redaktur Satu',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'jurnalistik2@example.com'],
            [
                'username' => 'jurnalistik2',
                'name' => 'Redaktur Dua',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        // Berikan role jurnalistik.
        $users = User::whereIn('email', ['jurnalistik1@example.com', 'jurnalistik2@example.com'])->get();
        foreach ($users as $user) {
            $user->assignRole('jurnalistik');
        }
    }
}
