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
            ['name' => 'Ekstrakurikuler', 'icon' => 'fa-solid fa-futbol', 'route' => 'admin.ekstrakurikulers.index', 'permission' => 'ekstrakurikulers.view', 'order' => 24],
            ['name' => 'Kelas', 'icon' => 'fa-solid fa-school-flag', 'route' => 'admin.kelas.index', 'permission' => 'kelas.view', 'order' => 25],
            ['name' => 'Pengampu', 'icon' => 'fa-solid fa-clipboard-user', 'route' => 'admin.pengampus.index', 'permission' => 'pengampus.view', 'order' => 26],
            ['name' => 'Rombel', 'icon' => 'fa-solid fa-users', 'route' => 'admin.rombels.index', 'permission' => 'rombels.view', 'order' => 27],
            // "Kenaikan Kelas" lama sudah dilebur ke wizard ini — override per
            // siswa pindah ke halaman yang sama supaya tidak ada lagi jalur
            // memindahkan siswa tanpa menyalin rombel lebih dulu.
            ['name' => 'Tahun Ajaran Baru', 'icon' => 'fa-solid fa-calendar-plus', 'route' => 'admin.tahun-ajaran-baru.index', 'permission' => 'tahun-ajaran-baru.view', 'order' => 28],
            ['name' => 'Riwayat Kelas', 'icon' => 'fa-solid fa-timeline', 'route' => 'admin.riwayat-kelas.index', 'permission' => 'riwayat-kelas.manage', 'order' => 29],
            ['name' => 'Siswa', 'icon' => 'fa-solid fa-graduation-cap', 'route' => 'admin.siswas.index', 'permission' => 'siswas.view', 'order' => 30],
            ['name' => 'Absensi', 'icon' => 'fa-solid fa-clipboard-check', 'route' => 'admin.absensis.index', 'permission' => 'absensis.view', 'order' => 31],
            ['name' => 'Rekap Absensi', 'icon' => 'fa-solid fa-chart-column', 'route' => 'admin.absensis.rekap', 'permission' => 'absensis.view', 'order' => 32],
            ['name' => 'E-Learning', 'is_header' => true, 'order' => 33],
            ['name' => 'Materi', 'icon' => 'fa-solid fa-book-open-reader', 'route' => 'admin.materis.index', 'permission' => 'materis.view', 'order' => 34],
            ['name' => 'Tugas', 'icon' => 'fa-solid fa-clipboard-question', 'route' => 'admin.tugas.index', 'permission' => 'tugas.view', 'order' => 35],
            ['name' => 'CBT', 'is_header' => true, 'order' => 36],
            ['name' => 'Bank Soal', 'icon' => 'fa-solid fa-database', 'route' => 'admin.cbt.banks.index', 'permission' => 'cbt.view', 'order' => 37],
            ['name' => 'Ujian', 'icon' => 'fa-solid fa-laptop', 'route' => 'admin.cbt.exams.index', 'permission' => 'cbt.view', 'order' => 38],

            ['name' => 'Sistem', 'is_header' => true, 'order' => 39],
            ['name' => 'Role', 'icon' => 'fa-solid fa-user-tie', 'route' => 'admin.roles.index', 'permission' => 'roles.view', 'order' => 40],
            ['name' => 'Menu', 'icon' => 'fa-solid fa-bars', 'route' => 'admin.menus.index', 'permission' => 'menus.view', 'order' => 41],
            ['name' => 'Pengguna & Role', 'icon' => 'fa-solid fa-users-gear', 'route' => 'admin.users.index', 'permission' => 'users.view', 'order' => 42],
            ['name' => 'Profil Sekolah', 'icon' => 'fa-solid fa-school', 'route' => 'admin.profil-sekolah.edit', 'permission' => 'profil-sekolahs.view', 'order' => 43],
            ['name' => 'Pengaturan', 'icon' => 'fa-solid fa-gear', 'route' => 'admin.pengaturans.index', 'permission' => 'pengaturans.view', 'order' => 44],
            ['name' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'route' => 'admin.whatsapp.index', 'permission' => 'whatsapp.view', 'order' => 45],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['name' => $menu['name']],
                array_merge(['is_active' => true, 'is_header' => false], $menu),
            );
        }

        // Menu yang sudah dilebur harus hilang dari database, bukan hanya
        // berhenti di daftar di atas — `updateOrCreate` tidak pernah menghapus.
        //
        // Efek samping yang disengaja: baris `menus` ini ikut terhapus saat
        // `migrate --force --seed` jalan di produksi, sesuai keputusan
        // menggabungkan "Kenaikan Kelas" ke dalam wizard "Tahun Ajaran Baru".
        // `menu_permission.menu_id` sudah cascade, jadi pivot ikut bersih dan
        // tidak ada grant permission yang menggantung.
        Menu::where('name', 'Kenaikan Kelas')->delete();

        // "Jadwal PPDB" dihapus bersama fiturnya. Baris ini hanya kalender
        // internal yang isinya salinan `gelombang_tahapan` milik Gelombang 1,
        // dan tidak dibaca halaman mana pun sejak status pendaftaran dipusatkan
        // ke Gelombang. Sisa barisnya harus ikut hilang, karena
        // `updateOrCreate` tidak pernah menghapus.
        Menu::where('name', 'Jadwal PPDB')->delete();
    }
}
