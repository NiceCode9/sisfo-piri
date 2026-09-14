@extends('layouts.app')

@section('title', 'Nexus Admin — Rekap Absensi')
@section('breadcrumb', 'Rekap Absensi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Rekap Absensi</h1>
        <p class="page-subtitle mb-0">Matriks kehadiran per rombel dan periode</p>
    </div>
    @if(isset($rekap))
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.absensis.rekap.excel', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-excel"></i> Excel</a>
            <a href="{{ route('admin.absensis.rekap.pdf', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </div>
    @endif
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Filter</h5>
            <p class="card-subtitle">{{ $terkunci ? 'Rombel ampuan Anda' : 'Rombel tahun aktif dan periode rekap' }}</p>
        </div>
        <form method="GET" action="{{ route('admin.absensis.rekap') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label" style="font-size:12px;" for="rombel_id">Rombel</label>
                <select name="rombel_id" id="rombel_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach($rombels as $r)<option value="{{ $r->id }}" @selected($rombel && $rombel->id==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px;" for="periode">Periode</label>
                <select name="periode" id="periode" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach(['minggu' => 'Mingguan', 'bulan' => 'Bulanan', 'ganjil' => 'Semester Ganjil', 'genap' => 'Semester Genap', 'tahun' => 'Tahun Penuh'] as $val => $label)
                        <option value="{{ $val }}" @selected($periode===$val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(in_array($periode, ['minggu', 'bulan'], true))
                <div>
                    <label class="form-label" style="font-size:12px;" for="acuan">Acuan</label>
                    <input type="{{ $periode === 'bulan' ? 'month' : 'date' }}" name="acuan" id="acuan" value="{{ $periode === 'bulan' ? substr($acuan, 0, 7) : $acuan }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()" />
                </div>
            @else
                <div>
                    <label class="form-label" style="font-size:12px;" for="tahun_ajaran_id">Tahun</label>
                    <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string) $tahunAjaranId === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
                    </select>
                </div>
            @endif
        </form>
    </div>
</div>

@if(isset($rekap))
<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">{{ $rekap['rombel']->kelas->nama_kelas }} — {{ $rekap['mulai'] }} s.d. {{ $rekap['selesai'] }}</h5>
            <p class="card-subtitle">{{ $rekap['siswas']->count() }} siswa • H {{ $rekap['total']['hadir'] ?? 0 }} • S {{ $rekap['total']['sakit'] ?? 0 }} • I {{ $rekap['total']['izin'] ?? 0 }} • A {{ $rekap['total']['alpa'] ?? 0 }} • T {{ $rekap['total']['terlambat'] ?? 0 }}</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead>
                    <tr>
                        <th>Nama</th>
                        @if($rekap['rinci'])
                            @foreach($rekap['tanggals'] as $tgl)<th title="{{ $tgl }}">{{ substr($tgl, 8, 2) }}</th>@endforeach
                        @endif
                        <th>H</th><th>S</th><th>I</th><th>A</th><th>T</th><th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap['siswas'] as $s)
                        @php $baris = $rekap['matriks'][$s->id]; @endphp
                        <tr>
                            <td style="font-weight:600;font-size:13px;white-space:nowrap;">{{ $s->user->name ?? '-' }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ $s->nis ?? $s->nisn ?? '-' }}</div></td>
                            @if($rekap['rinci'])
                                @foreach($rekap['tanggals'] as $tgl)
                                    @php $st = $baris['perTanggal']->get($tgl)?->status; @endphp
                                    <td class="text-center" style="font-size:12px;font-weight:700;">{{ $st ? strtoupper(substr($st, 0, 1)) : '–' }}</td>
                                @endforeach
                            @endif
                            <td>{{ $baris['hitung']['hadir'] }}</td>
                            <td>{{ $baris['hitung']['sakit'] }}</td>
                            <td>{{ $baris['hitung']['izin'] }}</td>
                            <td>{{ $baris['hitung']['alpa'] }}</td>
                            <td>{{ $baris['hitung']['terlambat'] }}</td>
                            <td>{{ $baris['persen'] === null ? '–' : $baris['persen'].'%' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa pada rombel ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
