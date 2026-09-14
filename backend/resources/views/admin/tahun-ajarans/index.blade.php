@extends('layouts.app')

@section('title', 'Nexus Admin — Tahun Ajaran')
@section('breadcrumb', 'Tahun Ajaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tahun Ajaran</h1>
        <p class="page-subtitle mb-0">Kelola tahun ajaran (satu tahun aktif)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('tahun-ajarans.create')
            <a href="{{ route('admin.tahun-ajarans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Tahun</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Tahun Ajaran</h5>
            <p class="card-subtitle">{{ $tahunAjarans->total() }} tahun ajaran</p>
        </div>
        <form method="GET" action="{{ route('admin.tahun-ajarans.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.tahun-ajarans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>Periode</th><th>Jadwal</th><th>Kuota</th><th>Calon</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($tahunAjarans as $t)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $t->nama_tahun_ajaran }}</td>
                            <td style="font-size:12.5px;">{{ \Carbon\Carbon::parse($t->tanggal_mulai)->format('d M Y') }} → {{ \Carbon\Carbon::parse($t->tanggal_selesai)->format('d M Y') }}</td>
                            <td style="font-size:13px;">{{ $t->jadwal_ppdb_count }}</td>
                            <td style="font-size:13px;">{{ $t->kuota_pendaftaran_count }}</td>
                            <td style="font-size:13px;">{{ $t->calon_siswa_count }}</td>
                            <td>{!! $t->status_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('tahun-ajarans.edit')<a href="{{ route('admin.tahun-ajarans.edit', $t) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('tahun-ajarans.delete')<form action="{{ route('admin.tahun-ajarans.destroy', $t) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $t->nama_tahun_ajaran }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">Belum ada tahun ajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tahunAjarans->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $tahunAjarans->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
