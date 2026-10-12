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
@include('admin.cbt.questions._form')
<div class="mt-2"><button type="submit" class="btn btn-nexus-outline btn-sm">Tambah ke Bank</button></div>
</form>
<h6 class="mt-4" style="font-size:13px;font-weight:700;">Soal di Bank (centang untuk impor ke ujian)</h6>
@forelse($bank->questions as $q)
<div class="border p-2 mb-1 d-flex justify-content-between align-items-center" style="font-size:13px;">
<div><strong>#{{ $q->order }} [{{ $q->type }}]</strong> {{ Str::limit($q->question_text, 100) }} ({{ $q->score }} poin) @if($q->question_image)<img src="{{ route('admin.cbt.questions.image', $q) }}" alt="" style="max-height:40px;vertical-align:middle;margin-left:6px;" /><span class="badge-nexus badge-info">gambar</span>@endif</div>
<form method="POST" action="{{ route('admin.cbt.banks.questions.destroy', $q) }}" onsubmit="return confirm('Hapus soal ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm text-danger">Hapus</button></form>
</div>
@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada soal.</p>@endforelse
</div></div>
@endsection
