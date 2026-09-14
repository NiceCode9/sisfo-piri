@extends('layouts.app')

@section('title', 'Nexus Admin — Galeri')
@section('breadcrumb', 'Galeri')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Galeri & Prestasi</h1>
        <p class="page-subtitle mb-0">Kelola foto galeri dan prestasi landing page</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('galeris.create')
            <a href="{{ route('admin.galeris.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Galeri</h5>
            <p class="card-subtitle">{{ $galeris->total() }} item</p>
        </div>
        <form method="GET" action="{{ route('admin.galeris.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tipe" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="galeri" @selected(request('tipe') === 'galeri')>Galeri</option>
                <option value="prestasi" @selected(request('tipe') === 'prestasi')>Prestasi</option>
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari judul..." style="width:200px" />
            </div>
            @if(request('search') || request('tipe'))<a href="{{ route('admin.galeris.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Preview</th><th>Judul</th><th>Tipe</th><th>Tanggal</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($galeris as $g)
                        <tr>
                            <td>
                                @if ($g->image_path)
                                    <img src="{{ Storage::disk('public')->url($g->image_path) }}" alt="{{ $g->title }}" style="height:48px;border-radius:6px;" />
                                @else
                                    <span class="badge-nexus badge-neutral">tanpa gambar</span>
                                @endif
                            </td>
                            <td style="font-weight:600;font-size:13px;">{{ $g->title }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ \Illuminate\Support\Str::limit($g->desc, 60) }}</div></td>
                            <td><span class="badge-nexus {{ $g->tipe === 'prestasi' ? 'badge-purple' : 'badge-info' }}">{{ $g->tipe }}</span></td>
                            <td style="font-size:12.5px;">{{ $g->tanggal ? \Carbon\Carbon::parse($g->tanggal)->format('d M Y') : '-' }}</td>
                            <td style="font-size:13px;">{{ $g->order }}</td>
                            <td>{!! $g->is_active ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('galeris.edit')<a href="{{ route('admin.galeris.edit', $g) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('galeris.delete')<form action="{{ route('admin.galeris.destroy', $g) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $g->title }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">Belum ada galeri.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($galeris->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $galeris->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
