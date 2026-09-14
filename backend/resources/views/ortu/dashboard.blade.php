@extends('layouts.app')

@section('title', 'Dashboard Orang Tua')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pantauan Anak</h1>
        <p class="page-subtitle mb-0">Halo, {{ auth()->user()->name }} — kehadiran hari ini ({{ $hari }})</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('login') }}" class="btn btn-nexus-outline btn-sm" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="row g-3">
    @forelse($anak as $a)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">{{ $a->siswa->user->name ?? '-' }}</h5>
                        <p class="card-subtitle mb-0">{{ $a->siswa->nis ?? $a->siswa->nisn ?? '-' }} • {{ $a->siswa->kelas->nama_kelas ?? '-' }}</p>
                    </div>
                    @php $st = $statusHariIni->get($a->siswa_id)?->status; @endphp
                    @if($st)<span class="badge-nexus badge-info">{{ ucfirst($st) }}</span>
                    @else<span class="badge-nexus">Belum absen</span>@endif
                </div>
                <div class="card-body-nexus">
                    <a href="{{ route('ortu.anak', $a) }}" class="btn btn-nexus-outline btn-sm w-100">Lihat Rekap Bulanan</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-nexus"><div class="card-body-nexus text-center py-5" style="color:var(--text-muted);">Belum ada anak tertaut ke akun ini.</div></div>
        </div>
    @endforelse
</div>
@endsection
