@extends('layouts.app')

@section('title', 'Jurnalistik — Dashboard')
@section('breadcrumb', 'Jurnalistik')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard Jurnalistik</h1>
</div>
<div class="row g-3">
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Total Berita</div><div class="h4 mb-0">{{ $totalBerita }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Draft</div><div class="h4 mb-0">{{ $draft }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Published</div><div class="h4 mb-0">{{ $published }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Scheduled</div><div class="h4 mb-0">{{ $scheduled }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Kategori</div><div class="h4 mb-0">{{ $totalKategori }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Tag</div><div class="h4 mb-0">{{ $totalTag }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Komentar Pending</div><div class="h4 mb-0">{{ $komentarPending }}</div></div></div>
</div>
@endsection