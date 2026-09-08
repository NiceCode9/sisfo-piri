<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            ]);
    }
}
