@extends('layouts.app')

@section('title', 'Nexus Admin — Menu')
@section('breadcrumb', 'Menu')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Menu</h1>
        <p class="page-subtitle mb-0">Kelola menu sidebar dan hak aksesnya</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('menus.create')
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Menu
            </a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Menu</h5>
            <p class="card-subtitle">{{ $menus->total() }} menu terdaftar</p>
        </div>
        <form method="GET" action="{{ route('admin.menus.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama menu..." style="width: 200px" />
            </div>
            @if (request('search') || request('is_header') !== null || request('is_active') !== null)
                <a href="{{ route('admin.menus.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Menu">
                <thead>
                    <tr><th>Nama</th><th>Icon</th><th>Route / URL</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 13px;">{{ $menu->name }}</div>
                                @if ($menu->is_header)
                                    <span class="badge-nexus badge-neutral" style="font-size: 11px;">header</span>
                                @endif
                                @if ($menu->parent)
                                    <div style="font-size: 11px; color: var(--text-muted);">parent: {{ $menu->parent->name ?? '-' }}</div>
                                @endif
                            </td>
                            <td style="font-size: 13px;">
                                @if ($menu->icon)
                                    <i class="{{ $menu->icon }}"></i> <span style="color: var(--text-muted); font-size: 12px;">{{ $menu->icon }}</span>
                                @else
                                    <span style="color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-secondary);">
                                {{ $menu->route ?? $menu->url ?? '—' }}
                                @if ($menu->permission)
                                    <div style="font-size: 11px; color: var(--text-muted);">perm: {{ $menu->permission }}</div>
                                @endif
                            </td>
                            <td style="font-size: 13px;">{{ $menu->order }}</td>
                            <td>
                                @if ($menu->is_active)
                                    <span class="badge-nexus badge-info">aktif</span>
                                @else
                                    <span class="badge-nexus badge-neutral">nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('menus.edit')
                                        <a href="{{ route('admin.menus.edit', $menu) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Ubah menu"><i class="fa-solid fa-pencil"></i></a>
                                    @endcan
                                    @can('menus.delete')
                                        <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus menu {{ $menu->name }}?"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color: var(--text-muted);">Belum ada menu yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($menus->hasPages())
        <div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color: var(--border-color) !important;">
            {{ $menus->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
