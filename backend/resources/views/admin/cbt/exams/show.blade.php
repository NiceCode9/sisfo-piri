@extends('layouts.app')

@section('title', 'Detail Ujian')
@section('breadcrumb', 'Detail Ujian')

@section('content')
<div class="page-header d-flex gap-2"><div><h1 class="page-title">{{ $exam->name }}</h1><p class="page-subtitle">{{ $exam->rombel->kelas->nama_kelas ?? '-' }} • {{ $exam->duration_minutes }}m</p></div><a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-primary btn-sm">Monitoring</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<p style="font-size:13px;">{{ $exam->description ?? '—' }}</p>
<h6 style="font-size:13px;font-weight:700;">Token</h6>
@forelse($exam->tokens as $t)<div style="font-size:13px;">{{ $t->token }} • {{ $t->active_from }}–{{ $t->active_until }} • {{ $t->used_count }}/{{ $t->max_usage ?? '∞' }}</div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada token.</p>@endforelse
<form method="POST" action="{{ route('admin.cbt.exams.tokens.store', $exam) }}" class="mt-2 d-flex gap-2">@csrf<div class="form-floating"><input type="datetime-local" name="active_from" class="form-control form-control-sm" required /><label>From</label></div><div class="form-floating"><input type="datetime-local" name="active_until" class="form-control form-control-sm" required /><label>Until</label></div><button type="submit" class="btn btn-nexus-outline btn-sm">Buat Token</button></form>
<h6 class="mt-3" style="font-size:13px;font-weight:700;">Soal ({{ $exam->questions->count() }})</h6>
@forelse($exam->questions as $q)<div style="font-size:13px;" class="border p-2 mb-1">{{ $q->question_text }} ({{ $q->type }}, {{ $q->score }} poin)</div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada soal.</p>@endforelse
<form method="POST" action="{{ route('admin.cbt.exams.questions.store', $exam) }}" class="mt-2">@csrf<div class="row g-2"><div class="col-8"><input type="text" name="question_text" class="form-control form-control-sm" placeholder="Teks soal" required /></div><div class="col-2"><select name="type" class="form-select form-select-sm"><option value="single_choice">Single</option><option value="multiple_choice">Multiple</option><option value="essay">Essay</option></select></div><div class="col-2"><button type="submit" class="btn btn-nexus-outline btn-sm w-100">Tambah</button></div></div></form>
</div></div>
@endsection
