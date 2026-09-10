@extends('layouts.app')

@section('title', 'Nexus Admin — Kelas')
@section('breadcrumb', 'Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Kelas</h1>
        <p class="page-subtitle mb-0">Kelas permanen lintas tahun ajaran (7A, 7B, ...)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('kelas.create')
            <a href="{{ route('admin.kelas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Kelas</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Kelas</h5>
            <p class="card-subtitle">{{ $kelas->total() }} kelas</p>
        </div>
        <form method="GET" action="{{ route('admin.kelas.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tingkat" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tingkat</option>
                @foreach (['7', '8', '9'] as $t)
                    <option value="{{ $t }}" @selected(request('tingkat')===$t)>Tingkat {{ $t }}</option>
                @endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama..." style="width:200px" />
            </div>
            @if(request('search')||request('tingkat'))<a href="{{ route('admin.kelas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>Tingkat</th><th>Siswa</th><th>Pengampu</th><th>Riwayat</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($kelas as $k)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $k->nama_kelas }}</td>
                            <td><span class="badge-nexus badge-info">{{ $k->tingkat }}</span></td>
                            <td style="font-size:13px;">{{ $k->siswas_count }}</td>
                            <td style="font-size:13px;">{{ $k->pengampus_count }}</td>
                            <td style="font-size:13px;">{{ $k->riwayat_kelas_count }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('kelas.edit')<a href="{{ route('admin.kelas.edit', $k) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('kelas.delete')<form action="{{ route('admin.kelas.destroy', $k) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $k->nama_kelas }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($kelas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $kelas->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
