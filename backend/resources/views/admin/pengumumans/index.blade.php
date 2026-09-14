@extends('layouts.app')

@section('title', 'Nexus Admin — Pengumuman')
@section('breadcrumb', 'Pengumuman')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pengumuman</h1>
        <p class="page-subtitle mb-0">Kelola pengumuman PPDB untuk publik</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('pengumumans.create')
            <a href="{{ route('admin.pengumumans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Pengumuman</h5>
            <p class="card-subtitle">{{ $pengumumans->total() }} data</p>
        </div>
        <form method="GET" action="{{ route('admin.pengumumans.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string) $tahunMode === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari judul..." style="width:200px" />
            </div>
            <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua</option>
                <option value="1" @selected(request('status')==='1')>Aktif</option>
                <option value="0" @selected(request('status')==='0')>Nonaktif</option>
            </select>
            @if(request('search') || request('status')!=='' || $tahunMode!=='aktif')<a href="{{ route('admin.pengumumans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Judul</th><th>Tahun</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($pengumumans as $p)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $p->judul }}<div style="font-size:11px;color:var(--text-muted);">{{ \Illuminate\Support\Str::limit($p->isi, 60) }}</div></td>
                            <td style="font-size:12.5px;">{{ $p->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ \Carbon\Carbon::parse($p->tanggal_pengumuman)->format('d M Y') }}</td>
                            <td>{!! $p->status_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('pengumumans.edit')<a href="{{ route('admin.pengumumans.edit', $p) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('pengumumans.delete')<form action="{{ route('admin.pengumumans.destroy', $p) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $p->judul }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada pengumuman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pengumumans->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $pengumumans->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
