@extends('layouts.app')

@section('title', 'Nexus Admin — Pengampu')
@section('breadcrumb', 'Pengampu')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pengampu Mapel</h1>
        <p class="page-subtitle mb-0">Siapa mengajar apa, di kelas mana, tahun kapan</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('pengampus.create')
            <a href="{{ route('admin.pengampus.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Penugasan</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Penugasan</h5>
            <p class="card-subtitle">{{ $pengampus->total() }} penugasan</p>
        </div>
        <form method="GET" action="{{ route('admin.pengampus.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(request('tahun')==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="kelas" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected(request('kelas')==$k->id)>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari guru..." style="width:180px" />
            </div>
            @if(request('search')||request('tahun')||request('kelas'))<a href="{{ route('admin.pengampus.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Guru</th><th>Mapel</th><th>Kelas</th><th>Tahun Ajaran</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($pengampus as $p)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $p->guru->nama ?? '-' }}</td>
                            <td><span class="badge-nexus badge-info">{{ $p->mataPelajaran->kode ?? '-' }}</span> <span style="font-size:12.5px;">{{ $p->mataPelajaran->nama ?? '' }}</span></td>
                            <td style="font-size:13px;">{{ $p->kelas->nama_kelas ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ $p->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('pengampus.edit')<a href="{{ route('admin.pengampus.edit', $p) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('pengampus.delete')<form action="{{ route('admin.pengampus.destroy', $p) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus penugasan ini?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada penugasan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pengampus->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $pengampus->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
