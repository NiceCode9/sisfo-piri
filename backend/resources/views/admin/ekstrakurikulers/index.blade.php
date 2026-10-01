@extends('layouts.app')

@section('title', 'Nexus Admin — Ekstrakurikuler')
@section('breadcrumb', 'Ekstrakurikuler')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ekstrakurikuler</h1>
        <p class="page-subtitle mb-0">Kelola daftar ekstrakurikuler sekolah</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('ekstrakurikulers.create')
            <a href="{{ route('admin.ekstrakurikulers.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Ekstrakurikuler</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Ekstrakurikuler</h5>
            <p class="card-subtitle">{{ $ekstrakurikulers->total() }} ekstrakurikuler</p>
        </div>
        <form method="GET" action="{{ route('admin.ekstrakurikulers.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari kode/nama/pembina..." style="width:220px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.ekstrakurikulers.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Kode</th><th>Nama</th><th>Pembina</th><th>Jadwal</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($ekstrakurikulers as $e)
                        <tr>
                            <td><span class="badge-nexus badge-info">{{ $e->kode }}</span></td>
                            <td style="font-weight:600;font-size:13px;">{{ $e->nama }}</td>
                            <td style="font-size:13px;">{{ $e->pembina ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $e->jadwal ?? '—' }}</td>
                            <td>{!! $e->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('ekstrakurikulers.edit')<a href="{{ route('admin.ekstrakurikulers.edit', $e) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('ekstrakurikulers.delete')<form action="{{ route('admin.ekstrakurikulers.destroy', $e) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $e->nama }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada ekstrakurikuler.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ekstrakurikulers->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $ekstrakurikulers->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
