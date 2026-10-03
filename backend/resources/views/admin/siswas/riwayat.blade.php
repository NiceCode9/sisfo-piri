@extends('layouts.app')

@section('title', 'Nexus Admin — Riwayat Siswa')
@section('breadcrumb', 'Siswa')

@section('content')
<div class="page-header d-flex justify-content-between gap-3">
    <div>
        <h1 class="page-title">Riwayat Siswa</h1>
        <p class="page-subtitle mb-0">{{ $siswa->user->name ?? 'Siswa' }} • NIS {{ $siswa->nis ?? '—' }} • {{ $siswa->nisn ?? '—' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.siswas.show', $siswa) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Detail Siswa</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="alert alert-info" style="font-size:13px;">
    Riwayat terpadu lintas tahun: kehadiran, e-learning, dan nilai CBT untuk setiap
    rombel yang pernah diikuti. Halaman ini <strong>bukan rapor</strong> — tidak ada
    nilai akhir, predikat, atau peringkat.
</div>

@include('components.partials.riwayat-terpadu', ['baris' => $baris, 'ringkas' => $ringkas])
@endsection