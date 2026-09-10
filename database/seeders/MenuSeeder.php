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
            ['name' => 'Calon Siswa', 'icon' => 'fa-solid fa-user-graduate', 'route' => 'admin.calon-siswas.index', 'permission' => 'calon-siswas.view', 'order' => 11],
            ['name' => 'Tahun Ajaran', 'icon' => 'fa-solid fa-calendar-check', 'route' => 'admin.tahun-ajarans.index', 'permission' => 'tahun-ajarans.view', 'order' => 11],
            ['name' => 'Jalur Pendaftaran', 'icon' => 'fa-solid fa-signs-post', 'route' => 'admin.jalur-pendaftarans.index', 'permission' => 'jalur-pendaftarans.view', 'order' => 12],
            ['name' => 'Jadwal PPDB', 'icon' => 'fa-solid fa-calendar-days', 'route' => 'admin.jadwal-ppdbs.index', 'permission' => 'jadwal-ppdbs.view', 'order' => 13],
            ['name' => 'Kuota', 'icon' => 'fa-solid fa-chart-pie', 'route' => 'admin.kuota-pendaftarans.index', 'permission' => 'kuota-pendaftarans.view', 'order' => 14],
            ['name' => 'Gelombang', 'icon' => 'fa-solid fa-layer-group', 'route' => 'admin.gelombangs.index', 'permission' => 'gelombangs.view', 'order' => 15],
            ['name' => 'Pembayaran', 'icon' => 'fa-solid fa-dollar-sign', 'route' => 'admin.pembayarans.index', 'permission' => 'pembayarans.view', 'order' => 16],
            ['name' => 'Biaya Pendaftaran', 'icon' => 'fa-solid fa-money-bill-wave', 'route' => 'admin.biaya-pendaftarans.index', 'permission' => 'biaya-pendaftarans.view', 'order' => 17],
            ['name' => 'Pengumuman', 'icon' => 'fa-solid fa-bullhorn', 'route' => 'admin.pengumumans.index', 'permission' => 'pengumumans.view', 'order' => 18],
            ['name' => 'Brosur', 'icon' => 'fa-solid fa-file-image', 'route' => 'admin.brosurs.index', 'permission' => 'brosurs.view', 'order' => 19],
            ['name' => 'Galeri', 'icon' => 'fa-solid fa-images', 'route' => 'admin.galeris.index', 'permission' => 'galeris.view', 'order' => 20],

            ['name' => 'Sistem', 'is_header' => true, 'order' => 30],
            ['name' => 'Role', 'icon' => 'fa-solid fa-user-tie', 'route' => 'admin.roles.index', 'permission' => 'roles.view', 'order' => 31],
            ['name' => 'Menu', 'icon' => 'fa-solid fa-bars', 'route' => 'admin.menus.index', 'permission' => 'menus.view', 'order' => 32],
            ['name' => 'Pengguna & Role', 'icon' => 'fa-solid fa-users-gear', 'route' => 'admin.users.index', 'permission' => 'users.view', 'order' => 33],
            ['name' => 'Profil Sekolah', 'icon' => 'fa-solid fa-school', 'route' => 'admin.profil-sekolah.edit', 'permission' => 'profil-sekolahs.view', 'order' => 34],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['name' => $menu['name']],
                array_merge(['is_active' => true, 'is_header' => false], $menu),
            );
        }
    }
}
