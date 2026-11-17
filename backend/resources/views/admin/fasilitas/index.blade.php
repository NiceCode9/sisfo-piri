@extends('layouts.app')

@section('title', 'Nexus Admin — Fasilitas')
@section('breadcrumb', 'Fasilitas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Fasilitas</h1>
        <p class="page-subtitle mb-0">Kelola fasilitas penunjang pembelajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('fasilitas.create')
            <a href="{{ route('admin.fasilitas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Fasilitas</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Fasilitas</h5>
            <p class="card-subtitle">{{ $fasilitas->total() }} item</p>
        </div>
        <form method="GET" action="{{ route('admin.fasilitas.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="jenis" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach ($jenisList as $jenis)
                    <option value="{{ $jenis }}" @selected(request('jenis') === $jenis)>{{ $jenis }}</option>
                @endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama/jenis..." style="width:200px" />
            </div>
            @if(request('search') || request('jenis'))<a href="{{ route('admin.fasilitas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Preview</th><th>Jenis</th><th>Nama</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($fasilitas as $f)
                        <tr>
                            <td>
                                @if (! empty($f->fotos))
                                    <img src="{{ Storage::disk('public')->url($f->fotos[0]) }}" alt="{{ $f->nama }}" style="height:48px;border-radius:6px;" />
                                @else
                                    <span class="badge-nexus badge-neutral">tanpa foto</span>
                                @endif
                            </td>
                            <td style="font-size:13px;">{{ $f->jenis }}</td>
                            <td style="font-weight:600;font-size:13px;">{{ $f->nama }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ \Illuminate\Support\Str::limit($f->deskripsi, 60) }}</div></td>
                            <td style="font-size:13px;">{{ $f->urutan }}</td>
                            <td>{!! $f->aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('fasilitas.edit')<a href="{{ route('admin.fasilitas.edit', $f) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('fasilitas.delete')<form action="{{ route('admin.fasilitas.destroy', $f) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $f->nama }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada fasilitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($fasilitas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $fasilitas->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection