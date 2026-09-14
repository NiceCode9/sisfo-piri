@extends('layouts.app')

@section('title', 'Nexus Admin — Mata Pelajaran')
@section('breadcrumb', 'Mata Pelajaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Mata Pelajaran</h1>
        <p class="page-subtitle mb-0">Kelola mapel beserta KKM</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('mata-pelajarans.create')
            <a href="{{ route('admin.mata-pelajarans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Mapel</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Mata Pelajaran</h5>
            <p class="card-subtitle">{{ $mapels->total() }} mapel</p>
        </div>
        <form method="GET" action="{{ route('admin.mata-pelajarans.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari kode/nama..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.mata-pelajarans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kelompok</th><th>KKM</th><th>Pengampu</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($mapels as $m)
                        <tr>
                            <td><span class="badge-nexus badge-info">{{ $m->kode }}</span></td>
                            <td style="font-weight:600;font-size:13px;">{{ $m->nama }}</td>
                            <td style="font-size:13px;">{{ $m->kelompok }}</td>
                            <td style="font-size:13px;">{{ $m->kkm }}</td>
                            <td style="font-size:13px;">{{ $m->pengampus_count }}</td>
                            <td>{!! $m->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('mata-pelajarans.edit')<a href="{{ route('admin.mata-pelajarans.edit', $m) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('mata-pelajarans.delete')<form action="{{ route('admin.mata-pelajarans.destroy', $m) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $m->nama }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">Belum ada mata pelajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($mapels->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $mapels->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
