@extends('layouts.app')

@section('title', 'Buat Bank Soal')
@section('breadcrumb', 'Buat Bank')

@section('content')
<div class="page-header"><h1 class="page-title">Buat Bank Soal</h1><p class="page-subtitle">Hanya mapel yang Anda ampu yang tersedia (admin lihat semua)</p></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.banks.store') }}">
@csrf
<div class="row g-3 mb-3">
<div class="col-12 col-sm-6"><div class="form-floating"><select name="mata_pelajaran_id" class="form-select" required><option value="">— Mata Pelajaran —</option>@foreach($mapels as $m)<option value="{{ $m->id }}">{{ $m->nama }} ({{ $m->kode }})</option>@endforeach</select><label>Mapel</label></div></div>
<div class="col-12 col-sm-6"><div class="form-floating"><input type="text" name="nama" class="form-control" required /><label>Nama Bank</label></div></div>
<div class="col-12"><div class="form-floating"><textarea name="deskripsi" class="form-control" style="height:80px"></textarea><label>Deskripsi</label></div></div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection
