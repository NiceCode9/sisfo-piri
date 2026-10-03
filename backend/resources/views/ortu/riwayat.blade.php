@extends('layouts.app')

@section('title', 'Nexus Admin — Riwayat Anak')
@section('breadcrumb', 'Anak')

@section('content')
<div class="page-header d-flex justify-content-between gap-3">
    <div>
        <h1 class="page-title">Riwayat {{ $siswa->user->name ?? 'Anak' }}</h1>
        <p class="page-subtitle mb-0">Kehadiran, tugas, dan nilai ujian lintas tahun ajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ortu.anak', $waliMurid) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Ringkasan</a>
    </div>
</div>

@include('ortu._nav', ['tabAktif' => ''])
@include('layouts.partials.alert')

@include('components.partials.riwayat-terpadu', ['baris' => $baris, 'ringkas' => $ringkas])
@endsection