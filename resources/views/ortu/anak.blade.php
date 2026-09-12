@extends('layouts.app')

@section('title', 'Rekap Anak')
@section('breadcrumb', 'Rekap Anak')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $tautan->siswa->user->name ?? '-' }}</h1>
        <p class="page-subtitle mb-0">{{ $tautan->siswa->nis ?? $tautan->siswa->nisn ?? '-' }} • {{ $tautan->siswa->kelas->nama_kelas ?? '-' }} • {{ $tautan->siswa->tahunAjaran->nama_tahun_ajaran ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ortu.dashboard') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mb-3">
    <div class="card-header-nexus"><h5 class="card-title">Rekap Bulan {{ $bulan }}</h5></div>
    <div class="card-body-nexus">
        @php $total = array_sum($rekap); @endphp
        @if(!$total)
            <p class="mb-0" style="font-size:13px;color:var(--text-muted);">Belum ada data bulan ini.</p>
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
            <p class="card-subtitle">Filter per bulan</p>
        </div>
        <form method="GET" action="{{ route('ortu.anak', $tautan) }}" class="d-flex gap-2">
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
