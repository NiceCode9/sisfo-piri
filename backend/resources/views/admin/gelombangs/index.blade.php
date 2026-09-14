@extends('layouts.app')

@section('title', 'Nexus Admin — Gelombang')
@section('breadcrumb', 'Gelombang')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Gelombang Pendaftaran</h1>
        <p class="page-subtitle mb-0">Kelola gelombang PPDB (terpisah dari jadwal fase)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('gelombangs.create')
            <a href="{{ route('admin.gelombangs.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Gelombang</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Gelombang</h5>
            <p class="card-subtitle">{{ $gelombangs->total() }} gelombang</p>
        </div>
        <form method="GET" action="{{ route('admin.gelombangs.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string) $tahunMode === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama..." style="width:200px" />
            </div>
            @if(request('search')||$tahunMode!=='aktif')<a href="{{ route('admin.gelombangs.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>#</th><th>Nama</th><th>Badge</th><th>Periode Buka</th><th>Kuota</th><th>Diskon</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($gelombangs as $g)
                        <tr>
                            <td style="font-size:13px;">{{ $g->nomor_urut }}</td>
                            <td style="font-weight:600;font-size:13px;">{{ $g->nama_gelombang }}<div style="font-size:11px;color:var(--text-muted);">{{ $g->tahunAjaran->nama_tahun_ajaran ?? '-' }}</div></td>
                            <td><span class="badge-nexus badge-info">{{ $g->badge ?? '-' }}</span></td>
                            <td style="font-size:12.5px;">{{ \Carbon\Carbon::parse($g->tanggal_buka)->format('d M Y') }} → {{ \Carbon\Carbon::parse($g->tanggal_tutup)->format('d M Y') }}</td>
                            <td style="font-size:12.5px;">{{ $g->terisi }}/{{ $g->kuota }} ({{ $g->persentase }}%)</td>
                            <td style="font-size:13px;">{{ $g->diskon_persen ? $g->diskon_persen.'%' : '-' }}</td>
                            <td>{!! $g->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('gelombangs.edit')<a href="{{ route('admin.gelombangs.edit', $g) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('gelombangs.delete')<form action="{{ route('admin.gelombangs.destroy', $g) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $g->nama_gelombang }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">Belum ada gelombang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($gelombangs->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $gelombangs->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
