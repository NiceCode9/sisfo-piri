@extends('layouts.app')

@section('title', 'Nexus Admin — Guru')
@section('breadcrumb', 'Guru')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Guru</h1>
        <p class="page-subtitle mb-0">Kelola data guru beserta akun login</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('gurus.create')
            <a href="{{ route('admin.gurus.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Guru</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Guru</h5>
            <p class="card-subtitle">{{ $gurus->total() }} guru</p>
        </div>
        <form method="GET" action="{{ route('admin.gurus.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama/NIP..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.gurus.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>NIP</th><th>Username</th><th>L/P</th><th>Telp</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($gurus as $g)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $g->nama }}</td>
                            <td style="font-size:13px;">{{ $g->nip ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $g->user->username ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $g->jenis_kelamin }}</td>
                            <td style="font-size:13px;">{{ $g->telp ?? '-' }}</td>
                            <td>{!! $g->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('gurus.edit')<a href="{{ route('admin.gurus.edit', $g) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('gurus.delete')<form action="{{ route('admin.gurus.destroy', $g) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $g->nama }} beserta akunnya?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">Belum ada guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($gurus->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $gurus->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
