@extends('layouts.app')

@section('title', 'Rekap Tugas Anak')
@section('breadcrumb', 'Rekap')

@section('content')
<div class="page-header"><h1 class="page-title">Rekap Tugas Anak</h1><p class="page-subtitle mb-0">Nilai per anak</p></div>
@include('layouts.partials.alert')
<div class="row g-3">
    @forelse($rekap as $row)
        <div class="col-12 col-md-6">
            <div class="card-nexus">
                <div class="card-header-nexus"><div><h5 class="card-title">{{ $row['siswa']->user->name ?? '-' }}</h5><p class="card-subtitle">{{ $row['siswa']->nis ?? '-' }} • Rata: {{ $row['rata'] ?? '—' }}</p></div></div>
                <div class="card-body-nexus"><p style="font-size:13px;">Jumlah tugas dikumpulkan: {{ $row['jumlah'] }}</p></div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card-nexus"><div class="card-body-nexus text-center py-4" style="color:var(--text-muted);">Belum ada data.</div></div></div>
    @endforelse
</div>
@endsection
