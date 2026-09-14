@extends('layouts.app')

@section('title', 'Nexus Admin — Rekap Tugas')
@section('breadcrumb', 'Rekap Tugas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Rekap Nilai Tugas</h1>
        <p class="page-subtitle mb-0">Matriks siswa × tugas per rombel</p>
    </div>
    @if(isset($rekap))
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.tugas.rekap.excel', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-excel"></i> Excel</a>
            <a href="{{ route('admin.tugas.rekap.pdf', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </div>
    @endif
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Filter</h5></div>
        <form method="GET" action="{{ route('admin.tugas.rekap') }}" class="d-flex gap-2 flex-wrap">
            <select name="rombel_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach($rombels as $r)<option value="{{ $r->id }}" @selected($rombel && $rombel->id==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="mapel_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Mapel</option>
                @foreach($mapels as $m)<option value="{{ $m->id }}" @selected((string)$mapelId===(string)$m->id)>{{ $m->kode }}</option>@endforeach
            </select>
        </form>
    </div>
</div>

@if(isset($rekap))
<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">{{ $rekap['rombel']->kelas->nama_kelas }} — {{ $rekap['tugasList']->count() }} tugas • {{ $rekap['siswas']->count() }} siswa</h5>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead>
                    <tr>
                        <th>Nama</th>
                        @foreach($rekap['tugasList'] as $t)<th title="{{ $t->judul }}">{{ \Illuminate\Support\Str::limit($t->judul, 12) }}</th>@endforeach
                        <th>Rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap['siswas'] as $s)
                        <tr>
                            <td style="font-weight:600;font-size:13px;white-space:nowrap;">{{ $s->user->name ?? '-' }}</td>
                            @foreach($rekap['tugasList'] as $t)
                                @php $p = $rekap['matriks'][$s->id][$t->id] ?? null; @endphp
                                <td class="text-center" style="font-size:12.5px;">
                                    @if($p?->nilai !== null)
                                        {{ $p->nilai }}@if($p->is_terlambat)<span class="badge-nexus badge-warning ms-1">T</span>@endif
                                    @else — @endif
                                </td>
                            @endforeach
                            <td style="font-weight:700;">{{ $rekap['rataPerSiswa'][$s->id] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 2 + $rekap['tugasList']->count() }}" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
