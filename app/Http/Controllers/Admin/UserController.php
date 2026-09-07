<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Middleware permission per aksi.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.view', only: ['index']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.edit', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
        ];
    }

    /**
     * Daftar pengguna dengan pencarian, filter role, dan pagination.
     */
    public function index(): View
    {
        $users = User::with('roles')
            ->when(request('search'), fn ($query, $search) => $query->where(
                fn ($query) => $query
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(request('role'), fn ($query, $role) => $query->role($role))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    /**
     * Form tambah pengguna.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Simpan pengguna baru.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create($validated);
        $user->syncRoles($this->filterRoles($validated['roles'] ?? []));

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna {$user->username} berhasil ditambahkan.");
    }

    /**
     * Form ubah pengguna.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Perbarui pengguna.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        // Password kosong = tidak diubah.
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles($this->filterRoles($validated['roles'] ?? []));

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna {$user->username} berhasil diperbarui.");
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya super-admin.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna {$user->username} berhasil dihapus.");
    }

    /**
     * Role yang boleh di-assign oleh user saat ini.
     * Non super-admin tidak boleh memberikan role super-admin.
     *
     * @return Collection<int, Role>
     */
    protected function assignableRoles()
    {
        return Role::orderBy('name')
            ->when(! auth()->user()->hasRole('super-admin'), fn ($query) => $query->where('name', '!=', 'super-admin'))
            ->get();
    }

    /**
     * Saring role yang diminta agar tidak melebihi hak pemberi.
     *
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    protected function filterRoles(array $roles): array
    {
        if (auth()->user()->hasRole('super-admin')) {
            return $roles;
        }

        return array_values(array_diff($roles, ['super-admin']));
    }
}
