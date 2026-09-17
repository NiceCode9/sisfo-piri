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

            ['name' => 'Akademik', 'is_header' => true, 'order' => 21],
            ['name' => 'Guru', 'icon' => 'fa-solid fa-chalkboard-user', 'route' => 'admin.gurus.index', 'permission' => 'gurus.view', 'order' => 22],
            ['name' => 'Mata Pelajaran', 'icon' => 'fa-solid fa-book-open', 'route' => 'admin.mata-pelajarans.index', 'permission' => 'mata-pelajarans.view', 'order' => 23],
            ['name' => 'Kelas', 'icon' => 'fa-solid fa-school-flag', 'route' => 'admin.kelas.index', 'permission' => 'kelas.view', 'order' => 24],
            ['name' => 'Pengampu', 'icon' => 'fa-solid fa-clipboard-user', 'route' => 'admin.pengampus.index', 'permission' => 'pengampus.view', 'order' => 25],
            ['name' => 'Rombel', 'icon' => 'fa-solid fa-users', 'route' => 'admin.rombels.index', 'permission' => 'rombels.view', 'order' => 26],
            ['name' => 'Kenaikan Kelas', 'icon' => 'fa-solid fa-arrow-up-right-dots', 'route' => 'admin.kenaikan.index', 'permission' => 'kenaikan-kelas.view', 'order' => 27],
            ['name' => 'Siswa', 'icon' => 'fa-solid fa-graduation-cap', 'route' => 'admin.siswas.index', 'permission' => 'siswas.view', 'order' => 28],
            ['name' => 'Absensi', 'icon' => 'fa-solid fa-clipboard-check', 'route' => 'admin.absensis.index', 'permission' => 'absensis.view', 'order' => 29],
            ['name' => 'Rekap Absensi', 'icon' => 'fa-solid fa-chart-column', 'route' => 'admin.absensis.rekap', 'permission' => 'absensis.view', 'order' => 30],
            ['name' => 'E-Learning', 'is_header' => true, 'order' => 31],
            ['name' => 'Materi', 'icon' => 'fa-solid fa-book-open-reader', 'route' => 'admin.materis.index', 'permission' => 'materis.view', 'order' => 32],
            ['name' => 'Tugas', 'icon' => 'fa-solid fa-clipboard-question', 'route' => 'admin.tugas.index', 'permission' => 'tugas.view', 'order' => 33],
            ['name' => 'CBT', 'is_header' => true, 'order' => 34],
            ['name' => 'Bank Soal', 'icon' => 'fa-solid fa-database', 'route' => 'admin.cbt.banks.index', 'permission' => 'cbt.view', 'order' => 35],
            ['name' => 'Ujian', 'icon' => 'fa-solid fa-laptop', 'route' => 'admin.cbt.exams.index', 'permission' => 'cbt.view', 'order' => 36],

            ['name' => 'Sistem', 'is_header' => true, 'order' => 35],
            ['name' => 'Role', 'icon' => 'fa-solid fa-user-tie', 'route' => 'admin.roles.index', 'permission' => 'roles.view', 'order' => 36],
            ['name' => 'Menu', 'icon' => 'fa-solid fa-bars', 'route' => 'admin.menus.index', 'permission' => 'menus.view', 'order' => 37],
            ['name' => 'Pengguna & Role', 'icon' => 'fa-solid fa-users-gear', 'route' => 'admin.users.index', 'permission' => 'users.view', 'order' => 38],
            ['name' => 'Profil Sekolah', 'icon' => 'fa-solid fa-school', 'route' => 'admin.profil-sekolah.edit', 'permission' => 'profil-sekolahs.view', 'order' => 39],
            ['name' => 'Pengaturan', 'icon' => 'fa-solid fa-gear', 'route' => 'admin.pengaturans.index', 'permission' => 'pengaturans.view', 'order' => 40],
            ['name' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'route' => 'admin.whatsapp.index', 'permission' => 'whatsapp.view', 'order' => 41],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['name' => $menu['name']],
                array_merge(['is_active' => true, 'is_header' => false], $menu),
            );
        }
    }
}
