<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Middleware permission per aksi.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.edit', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    /**
     * Daftar role dengan pencarian dan pagination.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')
            ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Form tambah role.
     */
    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan role baru.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role {$role->name} berhasil ditambahkan.");
    }

    /**
     * Form ubah role.
     */
    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    /**
     * Perbarui role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role {$role->name} berhasil diperbarui.");
    }

    /**
     * Hapus role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super-admin' && Role::where('name', 'super-admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya role super-admin.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role {$role->name} berhasil dihapus.");
    }
}
