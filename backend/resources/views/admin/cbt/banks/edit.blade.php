@extends('layouts.app')

@section('title', 'Edit Bank Soal')
@section('breadcrumb', 'Edit Bank')

@section('content')
<div class="page-header"><h1 class="page-title">Edit Bank: {{ $bank->nama }}</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.banks.update', $bank) }}">
@csrf @method('PUT')
<div class="row g-3 mb-3">
<div class="col-12 col-sm-6"><div class="form-floating"><input type="text" name="nama" value="{{ $bank->nama }}" class="form-control" required /><label>Nama</label></div></div>
<div class="col-12"><div class="form-floating"><textarea name="deskripsi" class="form-control" style="height:80px">{{ $bank->deskripsi }}</textarea><label>Deskripsi</label></div></div>
<div class="col-12"><label class="d-flex gap-2 align-items-center" style="font-size:13px;"><input type="checkbox" name="is_shared" value="1" @checked($bank->is_shared) /> Bagikan ke guru lain</label></div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection
