@extends('layouts.app')

@section('title', 'Edit Ujian')
@section('breadcrumb', 'Edit Ujian')

@section('content')
<div class="page-header"><h1 class="page-title">Edit Ujian</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.exams.update', $exam) }}">
@csrf @method('PUT')
<div class="row g-3 mb-3">
<div class="col-12 col-sm-4"><div class="form-floating"><select name="rombel_id" class="form-select" required><option value="">— Rombel —</option>@foreach($rombels as $r)<option value="{{ $r->id }}" @selected($exam->rombel_id==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach</select><label>Rombel</label></div></div>
<div class="col-12 col-sm-4"><div class="form-floating"><select name="mata_pelajaran_id" class="form-select" required><option value="">— Mapel —</option>@foreach($mapels as $m)<option value="{{ $m->id }}" @selected($exam->mata_pelajaran_id==$m->id)>{{ $m->nama }}</option>@endforeach</select><label>Mapel</label></div></div>
<div class="col-12 col-sm-4"><div class="form-floating"><input type="text" name="name" value="{{ $exam->name }}" class="form-control" required /><label>Nama</label></div></div>
<div class="col-12"><div class="form-floating"><select name="status" class="form-select"><option value="draft" @selected($exam->status==='draft')>Draft</option><option value="published" @selected($exam->status==='published')>Published</option><option value="archived" @selected($exam->status==='archived')>Archived</option></select><label>Status</label></div></div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection
