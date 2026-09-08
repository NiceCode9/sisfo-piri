<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuRequest;
use App\Http\Requests\Admin\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class MenuController extends Controller implements HasMiddleware
{
    /**
     * Middleware permission per aksi.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:menus.view', only: ['index']),
            new Middleware('permission:menus.create', only: ['create', 'store']),
            new Middleware('permission:menus.edit', only: ['edit', 'update']),
            new Middleware('permission:menus.delete', only: ['destroy']),
        ];
    }

    /**
     * Daftar menu dengan pencarian, filter header/aktif, dan pagination.
     */
    public function index(): View
    {
        $menus = Menu::with('permissions')
            ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(request('is_header') !== null, fn ($query, $value) => $query->where('is_header', $value === '1'))
            ->when(request('is_active') !== null, fn ($query, $value) => $query->where('is_active', $value === '1'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.menus.index', [
            'menus' => $menus,
        ]);
    }

    /**
     * Form tambah menu.
     */
    public function create(): View
    {
        return view('admin.menus.create', [
            'parentMenus' => Menu::parents()->orderBy('order')->get(),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan menu baru.
     */
    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $menu = Menu::create(collect($validated)->only([
            'name', 'icon', 'route', 'url', 'permission', 'parent_id', 'order', 'is_active', 'is_header', 'group',
        ])->toArray());

        $permissionIds = $validated['permission_ids'] ?? [];

        if (! empty($validated['permission'])) {
            $perm = Permission::where('name', $validated['permission'])->first();
            if ($perm && ! in_array($perm->id, $permissionIds)) {
                $permissionIds[] = $perm->id;
            }
        }

        $menu->permissions()->sync($permissionIds);

        return redirect()->route('admin.menus.index')
            ->with('success', "Menu {$menu->name} berhasil ditambahkan.");
    }

    /**
     * Form ubah menu.
     */
    public function edit(Menu $menu): View
    {
        return view('admin.menus.edit', [
            'menu' => $menu->load('permissions'),
            'parentMenus' => Menu::parents()->where('id', '!=', $menu->id)->orderBy('order')->get(),
            'permissions' => Permission::orderBy('name')->get(),
            'permissionIds' => $menu->permissions->pluck('id')->toArray(),
        ]);
    }

    /**
     * Perbarui menu.
     */
    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validated();

        $menu->update(collect($validated)->only([
            'name', 'icon', 'route', 'url', 'permission', 'parent_id', 'order', 'is_active', 'is_header', 'group',
        ])->toArray());

        $permissionIds = $validated['permission_ids'] ?? [];

        if (! empty($validated['permission'])) {
            $perm = Permission::where('name', $validated['permission'])->first();
            if ($perm && ! in_array($perm->id, $permissionIds)) {
                $permissionIds[] = $perm->id;
            }
        }

        $menu->permissions()->sync($permissionIds);

        return redirect()->route('admin.menus.index')
            ->with('success', "Menu {$menu->name} berhasil diperbarui.");
    }

    /**
     * Hapus menu.
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')
            ->with('success', "Menu {$menu->name} berhasil dihapus.");
    }
}
