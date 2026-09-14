@extends('layouts.app')

@section('title', 'Nexus Admin — Tugas')
@section('breadcrumb', 'Tugas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tugas</h1>
        <p class="page-subtitle mb-0">{{ $tugas->total() }} tugas</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.tugas.rekap') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-chart-column"></i> Rekap Nilai</a>
        @can('tugas.create')<a href="{{ route('admin.tugas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Tugas</a>@endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Filter</h5></div>
        <form method="GET" action="{{ route('admin.tugas.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode==='aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode==='semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string)$tahunMode===(string)$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="rombel" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Rombel</option>
                @foreach($rombels as $r)<option value="{{ $r->id }}" @selected((string)request('rombel')===(string)$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul..." class="form-control form-control-sm" style="width:160px" />
            @if(request('tahun')||request('rombel')||request('search'))<a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100">
            <thead><tr><th>Judul</th><th>Rombel</th><th>Mapel</th><th>Deadline</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($tugas as $t)
                    <tr>
                        <td style="font-weight:600;font-size:13px;">{{ $t->judul }}</td>
                        <td style="font-size:12.5px;">{{ $t->rombel->kelas->nama_kelas ?? '-' }} — {{ $t->rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                        <td><span class="badge-nexus badge-info">{{ $t->mataPelajaran->kode ?? '-' }}</span></td>
                        <td style="font-size:12.5px;">{{ $t->deadline ? $t->deadline->format('d M Y H:i') : '—' }}</td>
                        <td><div class="d-flex gap-1">
                            <a href="{{ route('admin.tugas.show', $t) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i></a>
                            @can('tugas.nilai')<a href="{{ route('admin.tugas.nilai', $t) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-star"></i></a>@endcan
                            @can('tugas.edit')<a href="{{ route('admin.tugas.edit', $t) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                            @can('tugas.delete')<form action="{{ route('admin.tugas.destroy', $t) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus tugas ini?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada tugas.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    @if($tugas->hasPages())<div class="px-3 py-2">{{ $tugas->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
