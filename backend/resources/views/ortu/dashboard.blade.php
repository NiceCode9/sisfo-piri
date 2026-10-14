@extends('layouts.app')

@section('title', 'Dashboard Orang Tua')
@section('breadcrumb', 'Dashboard')

@section('content')
@include('ortu._nav', ['tabAktif' => 'dashboard'])
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pantauan Anak</h1>
        <p class="page-subtitle mb-0">Halo, {{ auth()->user()->name }} — kehadiran hari ini ({{ $hari }})</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ortu.profil') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-key"></i> Ganti Password</a>
        <a href="{{ route('login') }}" class="btn btn-nexus-outline btn-sm" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="row g-3">
    @forelse($anak as $a)
        @php
            $menunggu = $tugasMenunggu[$a->siswa_id] ?? collect();
            $rata = $rataNilaiTugas[$a->siswa_id] ?? null;
            $cbt = $a->siswa ? ($nilaiCbtTerakhir[$a->siswa->user_id] ?? null) : null;
        @endphp
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">{{ $a->siswa->user->name ?? '-' }}</h5>
                        <p class="card-subtitle mb-0">{{ $a->siswa->nis ?? $a->siswa->nisn ?? '-' }} • {{ $a->siswa->kelas->nama_kelas ?? '-' }} • Wali: {{ $waliPerSiswa[$a->siswa_id] ?? '—' }}</p>
                    </div>
                    @php $st = $statusHariIni->get($a->siswa_id)?->status; @endphp
                    @if($st)<span class="badge-nexus badge-info">{{ ucfirst($st) }}</span>
                    @else<span class="badge-nexus">Belum absen</span>@endif
                </div>
                <div class="card-body-nexus">
                    @php $persen = $persenPerSiswa[$a->siswa_id] ?? null; @endphp
                    <p class="mb-2" style="font-size:13px;">Kehadiran bulan ini: <strong>{{ $persen === null ? '—' : $persen.'%' }}</strong></p>

                    <div class="d-flex gap-2 mb-3">
                        <div class="flex-fill text-center p-2 rounded" style="border:1px solid var(--border-color)">
                            <div class="fw-bold">{{ count($menunggu) }}</div>
                            <div class="small text-muted">Tugas menunggu</div>
                        </div>
                        <div class="flex-fill text-center p-2 rounded" style="border:1px solid var(--border-color)">
                            <div class="fw-bold">{{ $rata === null ? '—' : number_format($rata, 1) }}</div>
                            <div class="small text-muted">Rata-rata tugas</div>
                        </div>
                    </div>

                    {{-- "Nilai terakhir", bukan "nilai". Rapor memakai skor terbaik
                         dalam satu rantai remedial, sedangkan angka di sini cuma
                         sesi CBT terakhir. Labelnya harus beda supaya orang tua
                         tidak menyangka dua angka itu sama. --}}
                    <p class="mb-3" style="font-size:13px;">
                        Nilai CBT terakhir:
                        <strong>{{ $cbt === null ? '—' : $cbt['skor'] }}</strong>
                        @if($cbt)<span class="text-muted">— {{ $cbt['nama'] }}</span>@endif
                    </p>

                    @if($menunggu->isNotEmpty())
                        <details class="mb-3">
                            <summary style="font-size:13px;cursor:pointer;">
                                {{ $menunggu->count() }} tugas belum dikerjakan
                            </summary>
                            <ul class="mt-2 mb-0 ps-3" style="font-size:12.5px;">
                                @foreach($menunggu as $tugas)
                                    <li class="py-1 border-bottom">
                                        {{ $tugas->judul }}
                                        @if($tugas->deadline)
                                            <div class="text-muted">tenggat {{ $tugas->deadline->translatedFormat('j M H:i') }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    <a href="{{ route('ortu.anak', $a) }}" class="btn btn-nexus-outline btn-sm w-100">Lihat Rekap</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-nexus"><div class="card-body-nexus text-center py-5" style="color:var(--text-muted);">Belum ada anak tertaut ke akun ini.</div></div>
        </div>
    @endforelse
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-7">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Tenggat Terdekat</h5>
                    <p class="card-subtitle">Tugas anak yang belum lewat batas</p>
                </div>
                <a href="{{ route('ortu.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Semua Tugas</a>
            </div>
            <div class="card-body-nexus">
                @forelse($deadlineTerdekat as $tugas)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $tugas->judul }}</div>
                            <div class="small text-muted">
                                {{ $tugas->rombel?->kelas?->nama_kelas ?? '-' }}
                                &middot; {{ $tugas->deadline->translatedFormat('j M H:i') }}
                            </div>
                        </div>
                        @if($tugas->deadline->isBefore(now()->addDay()))
                            <span class="badge-nexus badge-danger">Segera</span>
                        @else
                            <span class="badge-nexus badge-neutral">{{ $tugas->deadline->diffForHumans() }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Tidak ada tenggat yang mendekati.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Pengumuman</h5>
                    <p class="card-subtitle">Informasi terbaru sekolah</p>
                </div>
            </div>
            <div class="card-body-nexus">
                @forelse($pengumuman as $item)
                    <div class="py-2 border-bottom">
                        <div class="fw-semibold">{{ $item->judul }}</div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($item->isi), 140) }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada pengumuman aktif.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
