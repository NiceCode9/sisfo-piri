@extends('layouts.app')

@section('title', 'Nexus Admin — Pengguna')
@section('breadcrumb', 'Pengguna')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pengguna</h1>
        <p class="page-subtitle mb-0">Kelola akun dan role pengguna sistem</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('users.create')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Pengguna
            </a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Pengguna</h5>
            <p class="card-subtitle">{{ $users->total() }} akun terdaftar</p>
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari username / nama..." style="width: 200px" />
            </div>
            <select name="role" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>
            @if (request('search') || request('role'))
                <a href="{{ route('admin.users.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Pengguna">
                <thead>
                    <tr><th>Pengguna</th><th>Nama</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar avatar-sm">{{ strtoupper(mb_substr($user->username, 0, 1)) }}</div>
                                    <div><div class="u-name">{{ $user->username }}</div><div class="u-email">{{ $user->email ?? '—' }}</div></div>
                                </div>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary);">{{ $user->name }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge-nexus {{ $role->name === 'super-admin' ? 'badge-purple' : 'badge-info' }}">{{ $role->name }}</span>
                                @empty
                                    <span class="badge-nexus badge-neutral">tanpa role</span>
                                @endforelse
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-muted);">{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('users.edit')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Ubah pengguna"><i class="fa-solid fa-pencil"></i></a>
                                    @endcan
                                    @can('users.delete')
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" aria-label="Hapus pengguna" data-confirm="Hapus pengguna {{ $user->username }}?"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color: var(--text-muted);">Belum ada pengguna yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($users->hasPages())
        <div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color: var(--border-color) !important;">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
