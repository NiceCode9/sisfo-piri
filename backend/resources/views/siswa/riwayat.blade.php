@extends('layouts.app')

@section('title', 'Nexus Admin — Riwayat Saya')
@section('breadcrumb', 'Riwayat')

@section('content')
<div class="page-header">
    <h1 class="page-title">Riwayat Saya</h1>
    <p class="page-subtitle mb-0">Kehadiran, e-learning, dan nilai ujian dari setiap tahun ajaran</p>
</div>

@include('layouts.partials.alert')

@include('components.partials.riwayat-terpadu', ['baris' => $baris, 'ringkas' => $ringkas])
@endsection