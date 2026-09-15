@extends('layouts.app')

@section('title', 'Buat Ujian')
@section('breadcrumb', 'Buat Ujian')

@section('content')
<div class="page-header"><h1 class="page-title">Buat Ujian</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.exams.store') }}">
@csrf
<div class="row g-3 mb-3">
<div class="col-12 col-sm-6"><div class="form-floating"><select name="rombel_id" class="form-select" required><option value="">— Rombel —</option>@foreach($rombels as $r)<option value="{{ $r->id }}">{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach</select><label>Rombel</label></div></div>
<div class="col-12 col-sm-6"><div class="form-floating"><input type="text" name="name" class="form-control" required /><label>Nama Ujian</label></div></div>
<div class="col-12"><div class="form-floating"><textarea name="description" class="form-control" style="height:80px"></textarea><label>Deskripsi</label></div></div>
<div class="col-4"><div class="form-floating"><input type="number" name="duration_minutes" class="form-control" value="60" required /><label>Durasi (menit)</label></div></div>
<div class="col-4"><div class="form-floating"><input type="datetime-local" name="available_from" class="form-control" /><label>Mulai</label></div></div>
<div class="col-4"><div class="form-floating"><input type="datetime-local" name="available_until" class="form-control" /><label>Selesai</label></div></div>
<div class="col-12"><div class="form-floating"><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option></select><label>Status</label></div></div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection
