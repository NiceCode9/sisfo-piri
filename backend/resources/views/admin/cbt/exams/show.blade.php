@extends('layouts.app')

@section('title', 'Detail Ujian')
@section('breadcrumb', 'Detail Ujian')

@section('content')
<div class="page-header d-flex gap-2"><div><h1 class="page-title">{{ $exam->name }}</h1><p class="page-subtitle">{{ $exam->rombel->kelas->nama_kelas ?? '-' }} • {{ $exam->mataPelajaran->nama ?? '-' }} • {{ $exam->duration_minutes }}m</p></div><a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-primary btn-sm">Monitoring</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<p style="font-size:13px;">{{ $exam->description ?? '—' }}</p>
<h6 style="font-size:13px;font-weight:700;">Token</h6>
@forelse($exam->tokens as $t)<div style="font-size:13px;">{{ $t->token }} • {{ $t->active_from }}–{{ $t->active_until }} • {{ $t->used_count }}/{{ $t->max_usage ?? '∞' }}</div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada token.</p>@endforelse
<form method="POST" action="{{ route('admin.cbt.exams.tokens.store', $exam) }}" class="mt-2 d-flex gap-2">@csrf<div class="form-floating"><input type="datetime-local" name="active_from" class="form-control form-control-sm" required /><label>From</label></div><div class="form-floating"><input type="datetime-local" name="active_until" class="form-control form-control-sm" required /><label>Until</label></div><button type="submit" class="btn btn-nexus-outline btn-sm">Buat Token</button></form>
<h6 class="mt-3" style="font-size:13px;font-weight:700;">Soal ({{ $exam->questions->count() }}) — snapshot dari bank</h6>
@forelse($exam->questions as $q)<div style="font-size:13px;" class="border p-2 mb-1 d-flex justify-content-between"><span>{{ $q->question_text }} ({{ $q->type }}, {{ $q->score }} poin) @if($q->question_bank_id) <span class="badge-nexus badge-info">bank #{{ $q->question_bank_id }}</span>@endif</span><form method="POST" action="{{ route('admin.cbt.questions.destroy', $q) }}" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm text-danger">Hapus</button></form></div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada soal. Impor dari bank di bawah.</p>@endforelse
<form method="POST" action="{{ route('admin.cbt.exams.questions.store', $exam) }}" class="mt-2">@csrf<div class="row g-2"><div class="col-8"><input type="text" name="question_text" class="form-control form-control-sm" placeholder="Teks soal manual" required /></div><div class="col-2"><select name="type" class="form-select form-select-sm"><option value="single_choice">Single</option><option value="multiple_choice">Multiple</option><option value="essay">Essay</option></select></div><div class="col-2"><button type="submit" class="btn btn-nexus-outline btn-sm w-100">Tambah Manual</button></div></div></form>

@if($banks->isNotEmpty())
<h6 class="mt-4" style="font-size:13px;font-weight:700;">Impor dari Bank Soal ({{ $exam->mataPelajaran->nama ?? 'mapel' }})</h6>
@foreach($banks as $bank)
<div class="border rounded p-2 mb-2">
<div class="d-flex justify-content-between align-items-center"><strong style="font-size:13px;">{{ $bank->nama }}</strong><span style="font-size:12px;color:var(--text-muted);">{{ $bank->questions_count }} soal</span></div>
@php $bankQuestions = \App\Models\ExamQuestion::where('question_bank_id', $bank->id)->orderBy('order')->get(); @endphp
@if($bankQuestions->isEmpty())<p style="font-size:12px;color:var(--text-muted);">Bank kosong.</p>@else
<form method="POST" action="{{ route('admin.cbt.exams.banks.import', [$exam, $bank]) }}">
@csrf
<div class="mt-2" style="max-height:220px;overflow-y:auto;">
@foreach($bankQuestions as $bq)
<label class="d-flex gap-2 align-items-start border-bottom py-1" style="font-size:13px;"><input type="checkbox" name="question_ids[]" value="{{ $bq->id }}" checked /> <span>{{ Str::limit($bq->question_text, 80) }} ({{ $bq->type }}, {{ $bq->score }}p)</span></label>
@endforeach
</div>
<button type="submit" class="btn btn-primary btn-sm mt-2">Salin Soal Terpilih</button>
</form>
@endif
</div>
@endforeach
@endif
</div></div>
@endsection
