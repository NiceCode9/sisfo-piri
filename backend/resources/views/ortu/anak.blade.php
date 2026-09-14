@extends('layouts.app')

@section('title', 'Rekap Anak')
@section('breadcrumb', 'Rekap Anak')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $tautan->siswa->user->name ?? '-' }}</h1>
        <p class="page-subtitle mb-0">{{ $tautan->siswa->nis ?? $tautan->siswa->nisn ?? '-' }} • {{ $tautan->siswa->kelas->nama_kelas ?? '-' }} • {{ $tautan->siswa->tahunAjaran->nama_tahun_ajaran ?? '-' }} • Wali: {{ $wali ?? '—' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ortu.dashboard') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mb-3">
    <div class="card-header-nexus"><h5 class="card-title">Rekap {{ $rentang['mulai'] }} s.d. {{ $rentang['selesai'] }}</h5><span class="badge-nexus badge-info">{{ $persen === null ? '—' : $persen.'%' }} hadir</span></div>
    <div class="card-body-nexus">
        @php $total = array_sum($rekap); @endphp
        @if(!$total)
            <p class="mb-0" style="font-size:13px;color:var(--text-muted);">Belum ada data periode ini.</p>
        @else
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['hadir' => 'badge-info', 'terlambat' => 'badge-warning', 'sakit' => 'badge-neutral', 'izin' => 'badge-neutral', 'alpa' => 'badge-danger'] as $st => $badge)
                    <span class="badge-nexus {{ $badge }}">{{ ucfirst($st) }}: {{ $rekap[$st] ?? 0 }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Riwayat Harian</h5>
            <p class="card-subtitle">Periode: {{ $periode }}</p>
        </div>
        <form method="GET" action="{{ route('ortu.anak', $tautan) }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label" style="font-size:12px;" for="periode">Periode</label>
                <select name="periode" id="periode" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach(['bulan' => 'Bulanan', 'ganjil' => 'Ganjil', 'genap' => 'Genap', 'tahun' => 'Tahun'] as $val => $label)
                        <option value="{{ $val }}" @selected($periode===$val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($periode === 'bulan')
                <div>
                    <label class="form-label" style="font-size:12px;" for="acuan">Bulan</label>
                    <input type="month" name="acuan" id="acuan" value="{{ $acuan }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()" />
                </div>
            @elseif($periode === 'minggu')
                <div>
                    <label class="form-label" style="font-size:12px;" for="acuan">Tanggal</label>
                    <input type="date" name="acuan" id="acuan" value="{{ $acuan }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()" />
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
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" style="font-size:13px;">
                <thead><tr><th>Tanggal</th><th>Rombel</th><th>Status</th><th>Jam</th></tr></thead>
                <tbody>
                    @forelse($riwayat as $a)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($a->tanggal)->format('d M Y') }}</td>
                            <td>{{ $a->rombel->kelas->nama_kelas ?? '-' }}</td>
                            <td>
                                @php $badge = $a->status === 'hadir' ? 'badge-info' : ($a->status === 'alpa' ? 'badge-danger' : ($a->status === 'terlambat' ? 'badge-warning' : 'badge-neutral')); @endphp
                                <span class="badge-nexus {{ $badge }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td>{{ $a->jam_datang ? substr($a->jam_datang, 0, 5) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada absensi periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
