@extends('layouts.app')

@section('title', 'Nexus Admin — Peran')
@section('breadcrumb', 'Peran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Peran</h1>
        <p class="page-subtitle mb-0">Kelola peran dan permission sistem</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Peran
            </a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Peran</h5>
            <p class="card-subtitle">{{ $roles->total() }} peran terdaftar</p>
        </div>
        <form method="GET" action="{{ route('admin.roles.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama peran..." style="width: 200px" />
            </div>
            @if (request('search'))
                <a href="{{ route('admin.roles.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Peran">
                <thead>
                    <tr><th>Nama</th><th>Permission</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>
                                @forelse ($role->permissions as $perm)
                                    <span class="badge-nexus badge-info">{{ $perm->name }}</span>
                                @empty
                                    <span class="badge-nexus badge-neutral">tanpa permission</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('roles.edit')
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Ubah peran"><i class="fa-solid fa-pencil"></i></a>
                                    @endcan
                                    @can('roles.delete')
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus peran {{ $role->name }}?"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color: var(--text-muted);">Belum ada peran yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($roles->hasPages())
        <div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color: var(--border-color) !important;">
            {{ $roles->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
