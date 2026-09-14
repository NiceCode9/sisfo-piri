@extends('layouts.app')

@section('title', 'Nexus Admin — Jadwal PPDB')
@section('breadcrumb', 'Jadwal PPDB')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Jadwal PPDB</h1>
        <p class="page-subtitle mb-0">Kelola fase timeline PPDB (Pendaftaran → Verifikasi → Tes → Pengumuman → Daftar Ulang)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('jadwal-ppdbs.create')
            <a href="{{ route('admin.jadwal-ppdbs.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Jadwal</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Jadwal</h5>
            <p class="card-subtitle">{{ $jadwals->total() }} fase jadwal</p>
        </div>
        <form method="GET" action="{{ route('admin.jadwal-ppdbs.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string) $tahunMode === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama..." style="width:200px" />
            </div>
            @if(request('search')||$tahunMode!=='aktif')<a href="{{ route('admin.jadwal-ppdbs.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama Fase</th><th>Tahun Ajaran</th><th>Periode</th><th>Keterangan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($jadwals as $j)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $j->nama_jadwal }}</td>
                            <td><span class="badge-nexus badge-info">{{ $j->tahunAjaran->nama_tahun_ajaran ?? '-' }}</span></td>
                            <td style="font-size:12.5px;">{{ \Carbon\Carbon::parse($j->tanggal_mulai)->format('d M Y') }} → {{ \Carbon\Carbon::parse($j->tanggal_selesai)->format('d M Y') }}</td>
                            <td style="font-size:12.5px;color:var(--text-secondary);max-width:240px;">{{ \Illuminate\Support\Str::limit($j->keterangan, 60) ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('jadwal-ppdbs.edit')<a href="{{ route('admin.jadwal-ppdbs.edit', $j) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('jadwal-ppdbs.delete')<form action="{{ route('admin.jadwal-ppdbs.destroy', $j) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $j->nama_jadwal }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada jadwal PPDB.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($jadwals->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $jadwals->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
