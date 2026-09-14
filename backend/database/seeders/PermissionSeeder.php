<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'menus.view',
            'menus.create',
            'menus.edit',
            'menus.delete',
            'calon-siswas.view',
            'calon-siswas.create',
            'calon-siswas.edit',
            'calon-siswas.delete',
            'berkas-calon-siswas.view',
            'berkas-calon-siswas.edit',
            'biaya-pendaftarans.view',
            'biaya-pendaftarans.create',
            'biaya-pendaftarans.edit',
            'biaya-pendaftarans.delete',
            'pengumumans.view',
            'pengumumans.create',
            'pengumumans.edit',
            'pengumumans.delete',
            'gelombangs.view',
            'gelombangs.create',
            'gelombangs.edit',
            'gelombangs.delete',
            'siswas.view',
            'siswas.create',
            'siswas.edit',
            'siswas.delete',
            'tahun-ajarans.view',
            'tahun-ajarans.create',
            'tahun-ajarans.edit',
            'tahun-ajarans.delete',
            'jalur-pendaftarans.view',
            'jalur-pendaftarans.create',
            'jalur-pendaftarans.edit',
            'jalur-pendaftarans.delete',
            'jadwal-ppdbs.view',
            'jadwal-ppdbs.create',
            'jadwal-ppdbs.edit',
            'jadwal-ppdbs.delete',
            'kuota-pendaftarans.view',
            'kuota-pendaftarans.create',
            'kuota-pendaftarans.edit',
            'kuota-pendaftarans.delete',
            'pembayarans.view',
            'pembayarans.create',
            'pembayarans.edit',
            'pembayarans.delete',
            'rencana-angsurans.view',
            'rencana-angsurans.create',
            'rencana-angsurans.edit',
            'rencana-angsurans.delete',
            'pembayaran-lainnyas.view',
            'pembayaran-lainnyas.create',
            'pembayaran-lainnyas.edit',
            'pembayaran-lainnyas.delete',
            'profil-sekolahs.view',
            'profil-sekolahs.edit',
            'brosurs.view',
            'brosurs.create',
            'brosurs.edit',
            'brosurs.delete',
            'galeris.view',
            'galeris.create',
            'galeris.edit',
            'galeris.delete',
            'gurus.view',
            'gurus.create',
            'gurus.edit',
            'gurus.delete',
            'mata-pelajarans.view',
            'mata-pelajarans.create',
            'mata-pelajarans.edit',
            'mata-pelajarans.delete',
            'kelas.view',
            'kelas.create',
            'kelas.edit',
            'kelas.delete',
            'pengampus.view',
            'pengampus.create',
            'pengampus.edit',
            'pengampus.delete',
            'wali-kelas.view',
            'rombels.view',
            'rombels.create',
            'rombels.edit',
            'rombels.delete',
            'kenaikan-kelas.view',
            'kenaikan-kelas.execute',
            'absensis.view',
            'absensis.create',
            'absensis.edit',
            'absensis.delete',
            'pengaturans.view',
            'pengaturans.edit',
            'whatsapp.view',
            'whatsapp.manage',
            'materis.view',
            'materis.create',
            'materis.edit',
            'materis.delete',
            'tugas.view',
            'tugas.create',
            'tugas.edit',
            'tugas.delete',
            'tugas.nilai',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // super-admin: semua permission; admin: kelola tanpa hapus.
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::all());

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->givePermissionTo([
                'users.view', 'users.create', 'users.edit',
                'roles.view', 'roles.create', 'roles.edit',
                'menus.view', 'menus.create', 'menus.edit',
                'calon-siswas.view', 'calon-siswas.create', 'calon-siswas.edit',
                'berkas-calon-siswas.view', 'berkas-calon-siswas.edit',
                'biaya-pendaftarans.view', 'biaya-pendaftarans.create', 'biaya-pendaftarans.edit',
                'pengumumans.view', 'pengumumans.create', 'pengumumans.edit',
                'gelombangs.view', 'gelombangs.create', 'gelombangs.edit',
                'siswas.view', 'siswas.create', 'siswas.edit',
                'tahun-ajarans.view', 'tahun-ajarans.create', 'tahun-ajarans.edit',
                'jalur-pendaftarans.view', 'jalur-pendaftarans.create', 'jalur-pendaftarans.edit',
                'jadwal-ppdbs.view', 'jadwal-ppdbs.create', 'jadwal-ppdbs.edit',
                'kuota-pendaftarans.view', 'kuota-pendaftarans.create', 'kuota-pendaftarans.edit',
                'pembayarans.view', 'pembayarans.create', 'pembayarans.edit',
                'rencana-angsurans.view', 'rencana-angsurans.create', 'rencana-angsurans.edit',
                'pembayaran-lainnyas.view', 'pembayaran-lainnyas.create', 'pembayaran-lainnyas.edit',
                'profil-sekolahs.view', 'profil-sekolahs.edit',
                'brosurs.view', 'brosurs.create', 'brosurs.edit',
                'galeris.view', 'galeris.create', 'galeris.edit',
                'gurus.view', 'gurus.create', 'gurus.edit',
                'mata-pelajarans.view', 'mata-pelajarans.create', 'mata-pelajarans.edit',
                'kelas.view', 'kelas.create', 'kelas.edit',
                'pengampus.view', 'pengampus.create', 'pengampus.edit',
                'wali-kelas.view',
                'rombels.view', 'rombels.create', 'rombels.edit',
                'materis.view', 'materis.create', 'materis.edit', 'materis.delete',
                'tugas.view', 'tugas.create', 'tugas.edit', 'tugas.delete', 'tugas.nilai',
                'kenaikan-kelas.view', 'kenaikan-kelas.execute',
                'absensis.view', 'absensis.create', 'absensis.edit',
                'pengaturans.view', 'pengaturans.edit',
                'whatsapp.view', 'whatsapp.manage',
                'materis.view', 'materis.create', 'materis.edit', 'materis.delete',
            ]);

        // Guru piket: input dan pantau absensi harian.
        Role::firstOrCreate(['name' => 'guru-piket', 'guard_name' => 'web'])
            ->syncPermissions(['absensis.view', 'absensis.create', 'absensis.edit']);

        // Guru: lihat rekap (wali dikunci ke rombel ampuan di controller).
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web'])
            ->syncPermissions(['absensis.view', 'materis.view', 'materis.create', 'materis.edit', 'materis.delete', 'tugas.view', 'tugas.create', 'tugas.edit', 'tugas.delete', 'tugas.nilai']);

        // Siswa dan orang-tua tidak membuka halaman admin.
        // Akses mereka dilayani area khusus (siswa.* dan — menyusul A3 — ortu.*).
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web'])
            ->syncPermissions([]);

        Role::firstOrCreate(['name' => 'orang-tua', 'guard_name' => 'web'])
            ->syncPermissions([]);

        // ensure siswa still can view own payments even after permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
