@extends('layouts.app')

@section('title', 'Nexus Admin — Kuota')
@section('breadcrumb', 'Kuota')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Kuota Pendaftaran</h1>
        <p class="page-subtitle mb-0">Kelola kuota per jalur per tahun ajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('kuota-pendaftarans.create')
            <a href="{{ route('admin.kuota-pendaftarans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Kuota</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Kuota</h5>
            <p class="card-subtitle">{{ $kuotas->total() }} baris kuota</p>
        </div>
        <form method="GET" action="{{ route('admin.kuota-pendaftarans.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(request('tahun')==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            @if(request('tahun'))<a href="{{ route('admin.kuota-pendaftarans.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Tahun Ajaran</th><th>Jalur</th><th>Terisi / Kuota</th><th>Progress</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($kuotas as $k)
                        @php $pct = $k->kuota > 0 ? round($k->terisi / $k->kuota * 100) : 0; @endphp
                        <tr>
                            <td><span class="badge-nexus badge-info">{{ $k->tahunAjaran->nama_tahun_ajaran ?? '-' }}</span></td>
                            <td style="font-weight:600;font-size:13px;">{{ $k->jalurPendaftaran->nama_jalur ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $k->terisi }}/{{ $k->kuota }} ({{ $pct }}%)</td>
                            <td style="min-width:140px;"><div class="progress-nexus"><div class="progress-fill" style="width:{{ min($pct, 100) }}%"></div></div></td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('kuota-pendaftarans.edit')<a href="{{ route('admin.kuota-pendaftarans.edit', $k) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('kuota-pendaftarans.delete')<form action="{{ route('admin.kuota-pendaftarans.destroy', $k) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus kuota ini?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada kuota pendaftaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($kuotas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $kuotas->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
