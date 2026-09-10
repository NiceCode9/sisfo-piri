@extends('layouts.app')

@section('title', 'Nexus Admin — Wali Kelas')
@section('breadcrumb', 'Wali Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Wali Kelas</h1>
        <p class="page-subtitle mb-0">Satu wali per kelas per tahun ajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('wali-kelas.create')
            <a href="{{ route('admin.wali-kelas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tetapkan Wali</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Wali Kelas</h5>
            <p class="card-subtitle">{{ $walis->total() }} penetapan</p>
        </div>
        <form method="GET" action="{{ route('admin.wali-kelas.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(request('tahun')==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            @if(request('tahun'))<a href="{{ route('admin.wali-kelas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Kelas</th><th>Wali</th><th>Tahun Ajaran</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($walis as $w)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $w->kelas->nama_kelas ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $w->guru->nama ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ $w->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('wali-kelas.edit')<a href="{{ route('admin.wali-kelas.edit', $w) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('wali-kelas.delete')<form action="{{ route('admin.wali-kelas.destroy', $w) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus penetapan wali ini?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada wali kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($walis->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $walis->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
