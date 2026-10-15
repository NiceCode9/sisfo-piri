@extends('layouts.app')

@section('title', 'Detail Ujian')
@section('breadcrumb', 'Detail Ujian')

@section('content')
<div class="page-header d-flex justify-content-between gap-2 flex-wrap">
<div><h1 class="page-title">{{ $exam->name }}</h1><p class="page-subtitle">{{ $exam->rombel->kelas->nama_kelas ?? '-' }} • {{ $exam->mataPelajaran->nama ?? '-' }} • {{ $exam->duration_minutes }}m</p></div>
<div class="d-flex gap-2">
@can('update', $exam)<a href="{{ route('admin.cbt.exams.edit', $exam) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil" aria-hidden="true"></i> Edit Ujian</a>@endcan
<a href="{{ route('admin.cbt.results.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Hasil</a>
<a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Monitoring</a>
</div>
</div>
@include('layouts.partials.alert', ['errorDitampilkan' => ['active_from', 'active_until', 'max_usage', 'paket']])

{{--
  Ringkasan ini menjawab satu pertanyaan yang selalu muncul di halaman ini:
  apakah ujiannya sudah siap dijalankan. Status, durasi, dan jendela waktu
  menentukan itu, jadi ditampilkan di atas soal dan token, bukan dikubur di
  form edit.
--}}
<div class="card-nexus mb-3"><div class="card-body-nexus">
<div class="d-flex flex-wrap gap-4">
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Status</p>
<span class="badge-nexus {{ $exam->statusBadge() }}">{{ $exam->statusLabel() }}</span>
</div>
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Durasi</p>
<p style="font-size:13px;margin:0;">{{ $exam->duration_minutes }} menit</p>
</div>
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Batas Pelanggaran</p>
<p style="font-size:13px;margin:0;">{{ $exam->max_violation_count }} kali</p>
</div>
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Jendela Waktu</p>
<p style="font-size:13px;margin:0;">
@if($exam->available_from && $exam->available_until)
{{ $exam->available_from->format('d M Y H:i') }} - {{ $exam->available_until->format('d M Y H:i') }}
@elseif($exam->available_from)
Mulai {{ $exam->available_from->format('d M Y H:i') }}, tanpa batas akhir
@elseif($exam->available_until)
Sampai {{ $exam->available_until->format('d M Y H:i') }}
@else
Tanpa batas waktu
@endif
</p>
</div>
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Pengacakan</p>
<p style="font-size:13px;margin:0;">Soal {{ $exam->shuffle_questions ? 'acak' : 'berurutan' }}, opsi {{ $exam->shuffle_options ? 'acak' : 'berurutan' }}</p>
</div>
@if($exam->is_remedial)
<div>
<p style="font-size:12px;color:var(--text-muted);margin:0 0 3px;">Remedial dari</p>
<p style="font-size:13px;margin:0;">{{ $exam->remedialOf->name ?? 'ujian asal tidak ditemukan' }}</p>
</div>
@endif
</div>
</div></div>

<div class="card-nexus mb-3"><div class="card-body-nexus">
<h6 style="font-size:13px;font-weight:700;">Import soal massal (Excel + gambar)</h6>
<p style="font-size:12.5px;color:var(--text-secondary);margin:4px 0 8px;">
  Unggah paket <code>.zip</code> berisi <code>soal.xlsx</code> dan folder <code>gambar/</code>. Soal akan ditampilkan di layar
  review dulu — kamu bisa mengoreksinya sebelum disimpan.
</p>
<div class="d-flex gap-2 flex-wrap">
  <a href="{{ route('admin.cbt.import.template') }}" class="btn btn-nexus-outline btn-sm">Unduh template Excel</a>
</div>
<form method="POST" action="{{ route('admin.cbt.import.unggah', $exam) }}" enctype="multipart/form-data" class="mt-2 d-flex gap-2 flex-wrap align-items-center">
  @csrf
  <input type="file" name="paket" accept=".zip,application/zip" class="form-control form-control-sm @error('paket') is-invalid @enderror" style="max-width:340px;" required />
  @error('paket')<div class="invalid-feedback">{{ $message }}</div>@enderror
  <button type="submit" class="btn btn-primary btn-sm">Unggah &amp; review</button>
</form>
</div></div>
<div class="card-nexus"><div class="card-body-nexus">
<p style="font-size:13px;">{{ $exam->description ?? '—' }}</p>
<h6 style="font-size:13px;font-weight:700;">Token</h6>
@forelse($exam->tokens as $t)<div style="font-size:13px;">{{ $t->token }} • {{ $t->active_from }}–{{ $t->active_until }} • {{ $t->used_count }}/{{ $t->max_usage ?? '∞' }}</div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada token.</p>@endforelse
<form method="POST" action="{{ route('admin.cbt.exams.tokens.store', $exam) }}" class="mt-2 d-flex gap-2 flex-wrap">@csrf
<div class="form-floating"><input type="datetime-local" name="active_from" value="{{ old('active_from') }}" class="form-control form-control-sm @error('active_from') is-invalid @enderror" required /><label>From</label>@error('active_from')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-floating"><input type="datetime-local" name="active_until" value="{{ old('active_until') }}" class="form-control form-control-sm @error('active_until') is-invalid @enderror" required /><label>Until</label>@error('active_until')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
{{-- `max_usage` divalidasi controller tapi tidak pernah punya kolom. Tanpa ini
     kuota token selalu kosong. --}}
<div class="form-floating"><input type="number" name="max_usage" value="{{ old('max_usage') }}" min="1" class="form-control form-control-sm @error('max_usage') is-invalid @enderror" placeholder="Kuota" /><label>Kuota pakai</label>@error('max_usage')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<button type="submit" class="btn btn-nexus-outline btn-sm align-self-end">Buat Token</button></form>
<h6 class="mt-3" style="font-size:13px;font-weight:700;">Soal ({{ $exam->questions->count() }}) — snapshot dari bank</h6>
@forelse($exam->questions as $q)<div style="font-size:13px;" class="border p-2 mb-1 d-flex justify-content-between"><span>@if($q->question_image)<img src="{{ route('admin.cbt.questions.image', $q) }}" alt="" style="max-height:48px;vertical-align:middle;margin-right:6px;" />@endif{{ $q->question_text }} ({{ $q->type }}, {{ $q->score }} poin) @if($q->question_bank_id) <span class="badge-nexus badge-info">bank #{{ $q->question_bank_id }}</span>@endif</span><form method="POST" action="{{ route('admin.cbt.questions.destroy', $q) }}" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm text-danger">Hapus</button></form></div>@empty<p style="font-size:13px;color:var(--text-muted);">Belum ada soal. Impor dari bank di bawah.</p>@endforelse

<form method="POST" action="{{ route('admin.cbt.exams.questions.store', $exam) }}" enctype="multipart/form-data" class="mt-2">
@csrf
@include('admin.cbt.questions._form')
<div class="mt-2"><button type="submit" class="btn btn-primary btn-sm">Tambah Manual</button></div>
</form>

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
