@extends('layouts.app')

@section('title', 'Nexus Admin — Rombel')
@section('breadcrumb', 'Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Rombongan Belajar</h1>
        <p class="page-subtitle mb-0">Satu baris per kelas per tahun ajaran + wali</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('rombels.create')
            <a href="{{ route('admin.rombels.salin') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-copy"></i> Salin Tahun</a>
            <a href="{{ route('admin.rombels.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Bentuk Rombel</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Rombel</h5>
            <p class="card-subtitle">{{ $rombels->total() }} rombel</p>
        </div>
        <form method="GET" action="{{ route('admin.rombels.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(request('tahun')==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari kelas..." style="width:180px" />
            </div>
            @if(request('search')||request('tahun'))<a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Kelas</th><th>Tahun Ajaran</th><th>Wali</th><th>Penugasan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($rombels as $r)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $r->kelas->nama_kelas ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ $r->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $r->waliGuru->nama ?? '—' }}</td>
                            <td style="font-size:13px;">{{ $r->pengampus_count }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('rombels.view')<a href="{{ route('admin.rombels.show', $r) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i></a>@endcan
                                    @can('rombels.edit')<a href="{{ route('admin.rombels.edit', $r) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('rombels.delete')<form action="{{ route('admin.rombels.destroy', $r) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus rombel {{ $r->kelas->nama_kelas ?? '' }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada rombel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rombels->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $rombels->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
