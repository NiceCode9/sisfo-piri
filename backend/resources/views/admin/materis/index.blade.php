@extends('layouts.app')

@section('title', 'Nexus Admin — Materi')
@section('breadcrumb', 'Materi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Materi E-Learning</h1>
        <p class="page-subtitle mb-0">{{ $materis->total() }} materi</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('materis.create')<a href="{{ route('admin.materis.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Materi</a>@endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Filter</h5>
            <p class="card-subtitle">Rombel tahun aktif dan tipe</p>
        </div>
        <form method="GET" action="{{ route('admin.materis.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode==='aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode==='semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string)$tahunMode===(string)$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="rombel" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Rombel</option>
                @foreach($rombels as $r)<option value="{{ $r->id }}" @selected((string)request('rombel')===(string)$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="tipe" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                @foreach(['dokumen','video','link'] as $t)<option value="{{ $t }}" @selected(request('tipe')===$t)>{{ ucfirst($t) }}</option>@endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul..." class="form-control form-control-sm" style="width:160px" />
            @if(request('tahun')||request('rombel')||request('tipe')||request('search'))<a href="{{ route('admin.materis.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Judul</th><th>Rombel</th><th>Mapel</th><th>Tipe</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($materis as $m)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $m->judul }}</td>
                            <td style="font-size:12.5px;">{{ $m->rombel->kelas->nama_kelas ?? '-' }} — {{ $m->rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td><span class="badge-nexus badge-info">{{ $m->mataPelajaran->kode ?? '-' }}</span></td>
                            <td><span class="badge-nexus badge-neutral">{{ ucfirst($m->tipe) }}</span></td>
                            <td>@if($m->is_aktif)<span class="badge-nexus badge-info">Aktif</span>@else<span class="badge-nexus">Nonaktif</span>@endif</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.materis.show', $m) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i></a>
                                    @can('materis.edit')<a href="{{ route('admin.materis.edit', $m) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('materis.delete')<form action="{{ route('admin.materis.destroy', $m) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus materi ini?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada materi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($materis->hasPages())<div class="px-3 py-2">{{ $materis->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
