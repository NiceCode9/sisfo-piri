<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'icon' => 'fa-solid fa-th-large', 'route' => 'admin.dashboard', 'order' => 1],

            ['name' => 'PPDB', 'is_header' => true, 'order' => 10],
            ['name' => 'Calon Siswa', 'icon' => 'fa-solid fa-user-graduate', 'order' => 11],
            ['name' => 'Jalur Pendaftaran', 'icon' => 'fa-solid fa-signs-post', 'order' => 12],
            ['name' => 'Jadwal PPDB', 'icon' => 'fa-solid fa-calendar-days', 'order' => 13],
            ['name' => 'Kuota', 'icon' => 'fa-solid fa-chart-pie', 'order' => 14],
            ['name' => 'Pembayaran', 'icon' => 'fa-solid fa-dollar-sign', 'order' => 15],

            ['name' => 'Sistem', 'is_header' => true, 'order' => 20],
            ['name' => 'Role', 'icon' => 'fa-solid fa-user-tie', 'route' => 'admin.roles.index', 'permission' => 'roles.view', 'order' => 21],
            ['name' => 'Menu', 'icon' => 'fa-solid fa-bars', 'route' => 'admin.menus.index', 'permission' => 'menus.view', 'order' => 22],
            ['name' => 'Pengguna & Role', 'icon' => 'fa-solid fa-users-gear', 'route' => 'admin.users.index', 'permission' => 'users.view', 'order' => 23],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['name' => $menu['name']],
                array_merge(['is_active' => true, 'is_header' => false], $menu),
            );
        }
    }
}
