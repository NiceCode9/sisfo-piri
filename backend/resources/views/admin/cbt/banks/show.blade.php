@extends('layouts.app')

@section('title', 'Bank Soal — Detail')
@section('breadcrumb', 'Detail Bank')

@section('content')
<div class="page-header d-flex gap-2 justify-content-between"><div><h1 class="page-title">{{ $bank->nama }}</h1><p class="page-subtitle">{{ $bank->mataPelajaran->nama ?? '-' }} • {{ $bank->guru->nama ?? '-' }} • {{ $bank->questions->count() }} soal</p></div><a href="{{ route('admin.cbt.banks.edit', $bank) }}" class="btn btn-nexus-outline btn-sm">Edit Bank</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<h6 style="font-size:13px;font-weight:700;">Tambah Soal ke Bank</h6>
<form method="POST" action="{{ route('admin.cbt.banks.questions.store', $bank) }}" enctype="multipart/form-data" class="mt-2">
@csrf
<div class="row g-2">
<div class="col-12"><textarea name="question_text" class="form-control form-control-sm" rows="2" placeholder="Teks soal" required></textarea></div>
<div class="col-3"><select name="type" class="form-select form-select-sm"><option value="single_choice">Single</option><option value="multiple_choice">Multiple</option><option value="essay">Essay</option></select></div>
<div class="col-2"><input type="number" name="score" value="10" class="form-control form-control-sm" placeholder="Skor" required /></div>
<div class="col-3"><input type="file" name="question_image" class="form-control form-control-sm" /></div>
<div class="col-2"><input type="number" name="order" value="0" class="form-control form-control-sm" placeholder="Urutan" /></div>
<div class="col-2"><button type="submit" class="btn btn-nexus-outline btn-sm w-100">Tambah</button></div>
</div>
</form>
<h6 class="mt-4" style="font-size:13px;font-weight:700;">Soal di Bank (centang untuk impor ke ujian)</h6>
@forelse($bank->questions as $q)
<div class="border p-2 mb-1 d-flex justify-content-between align-items-center" style="font-size:13px;">
<div><strong>#{{ $q->order }} [{{ $q->type }}]</strong> {{ Str::limit($q->question_text, 100) }} ({{ $q->score }} poin) @if($q->question_image) <span class="badge-nexus badge-info">gambar</span>@endif</div>
<form method="POST" action="{{ route('admin.cbt.banks.questions.destroy', $q) }}" onsubmit="return confirm('Hapus soal ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm text-danger">Hapus</button></form>
</div>
@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada soal.</p>@endforelse
</div></div>
@endsection
