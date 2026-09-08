@extends('layouts.app')

@section('title', 'Nexus Admin — Jalur Pendaftaran')
@section('breadcrumb', 'Jalur Pendaftaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Jalur Pendaftaran</h1>
        <p class="page-subtitle mb-0">Kelola jalur seleksi PPDB (Zonasi, Prestasi, Afirmasi, ...)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('jalur-pendaftarans.create')
            <a href="{{ route('admin.jalur-pendaftarans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Jalur</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Jalur</h5>
            <p class="card-subtitle">{{ $jalurs->total() }} jalur</p>
        </div>
        <form method="GET" action="{{ route('admin.jalur-pendaftarans.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.jalur-pendaftarans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama Jalur</th><th>Deskripsi</th><th>Kuota</th><th>Calon</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($jalurs as $j)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $j->nama_jalur }}</td>
                            <td style="font-size:12.5px;color:var(--text-secondary);max-width:280px;">{{ \Illuminate\Support\Str::limit($j->deskripsi, 80) ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $j->kuota_pendaftaran_count }}</td>
                            <td style="font-size:13px;">{{ $j->calon_siswa_count }}</td>
                            <td>{!! $j->aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('jalur-pendaftarans.edit')<a href="{{ route('admin.jalur-pendaftarans.edit', $j) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('jalur-pendaftarans.delete')<form action="{{ route('admin.jalur-pendaftarans.destroy', $j) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $j->nama_jalur }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada jalur pendaftaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($jalurs->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $jalurs->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
