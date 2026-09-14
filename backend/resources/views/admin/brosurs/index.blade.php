@extends('layouts.app')

@section('title', 'Nexus Admin — Brosur')
@section('breadcrumb', 'Brosur')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Brosur</h1>
        <p class="page-subtitle mb-0">Kelola dokumen brosur landing page</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('brosurs.create')
            <a href="{{ route('admin.brosurs.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Brosur</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Brosur</h5>
            <p class="card-subtitle">{{ $brosurs->total() }} dokumen</p>
        </div>
        <form method="GET" action="{{ route('admin.brosurs.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari judul..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.brosurs.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Preview</th><th>Judul</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($brosurs as $b)
                        <tr>
                            <td>
                                @if ($b->image_path)
                                    <img src="{{ Storage::disk('public')->url($b->image_path) }}" alt="{{ $b->title }}" style="height:48px;border-radius:6px;" />
                                @else
                                    <span class="badge-nexus badge-neutral">tanpa gambar</span>
                                @endif
                            </td>
                            <td style="font-weight:600;font-size:13px;">{{ $b->title }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ \Illuminate\Support\Str::limit($b->desc, 60) }}</div></td>
                            <td style="font-size:13px;">{{ $b->order }}</td>
                            <td>{!! $b->is_active ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('brosurs.edit')<a href="{{ route('admin.brosurs.edit', $b) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('brosurs.delete')<form action="{{ route('admin.brosurs.destroy', $b) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $b->title }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada brosur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($brosurs->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $brosurs->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
