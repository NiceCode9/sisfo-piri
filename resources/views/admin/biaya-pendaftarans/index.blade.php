@extends('layouts.app')

@section('title', 'Nexus Admin — Biaya Pendaftaran')
@section('breadcrumb', 'Biaya Pendaftaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Biaya Pendaftaran</h1>
        <p class="page-subtitle mb-0">Kelola biaya PPDB per tahun ajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('biaya-pendaftarans.create')
            <a href="{{ route('admin.biaya-pendaftarans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Biaya</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Biaya</h5>
            <p class="card-subtitle">{{ $biayas->total() }} data</p>
        </div>
        <form method="GET" action="{{ route('admin.biaya-pendaftarans.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari jenis biaya..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.biaya-pendaftarans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Biaya">
                <thead><tr><th>Jenis</th><th>Tahun</th><th>Jumlah</th><th>Wajib</th><th>Angsur</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($biayas as $b)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $b->jenis_biaya }}</td>
                            <td style="font-size:12.5px;">{{ $b->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td style="font-size:13px;">Rp {{ number_format($b->jumlah,0,',','.') }}</td>
                            <td>{!! $b->wajib_bayar ? '<span class="badge-nexus badge-info">ya</span>' : '<span class="badge-nexus badge-neutral">tidak</span>' !!}</td>
                            <td>{!! $b->dapat_diangsur ? '<span class="badge-nexus badge-purple">ya</span>' : '<span class="badge-nexus badge-neutral">tidak</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('biaya-pendaftarans.edit')<a href="{{ route('admin.biaya-pendaftarans.edit', $b) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('biaya-pendaftarans.delete')<form action="{{ route('admin.biaya-pendaftarans.destroy', $b) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $b->jenis_biaya }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada biaya.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($biayas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $biayas->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
