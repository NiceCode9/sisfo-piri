@extends('layouts.app')

@section('title', 'Absensi Saya')
@section('breadcrumb', 'Absensi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Absensi Saya</h1>
        <p class="page-subtitle mb-0">Riwayat kehadiran harian dan rekap</p>
    </div>
</div>

@include('layouts.partials.alert')

@include('siswa._nav', ['tabAktif' => 'absensi'])

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Rekap Bulan Ini ({{ $bulan }})</h5></div>
            <div class="card-body-nexus">
                @php $totalBulan = array_sum($rekapBulan); @endphp
                @if(!$totalBulan)
                    <p class="mb-0" style="font-size:13px;color:var(--text-muted);">Belum ada data bulan ini.</p>
                @else
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['hadir' => 'badge-info', 'terlambat' => 'badge-warning', 'sakit' => 'badge-neutral', 'izin' => 'badge-neutral', 'alpa' => 'badge-danger'] as $st => $badge)
                            <span class="badge-nexus {{ $badge }}">{{ ucfirst($st) }}: {{ $rekapBulan[$st] ?? 0 }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Rekap Tahun Berjalan</h5></div>
            <div class="card-body-nexus">
                @php $totalTahun = array_sum($rekapTahun); @endphp
                @if(!$totalTahun)
                    <p class="mb-0" style="font-size:13px;color:var(--text-muted);">Belum ada data tahun ini.</p>
                @else
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['hadir' => 'badge-info', 'terlambat' => 'badge-warning', 'sakit' => 'badge-neutral', 'izin' => 'badge-neutral', 'alpa' => 'badge-danger'] as $st => $badge)
                            <span class="badge-nexus {{ $badge }}">{{ ucfirst($st) }}: {{ $rekapTahun[$st] ?? 0 }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Riwayat Harian</h5>
            <p class="card-subtitle">Filter per bulan</p>
        </div>
        <form method="GET" action="{{ route('siswa.absensi') }}" class="d-flex gap-2">
            <input type="month" name="bulan" value="{{ $bulan }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()" />
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
                        <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada absensi bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
