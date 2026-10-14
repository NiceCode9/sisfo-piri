@extends('layouts.app')

@section('title', 'Hasil Ujian')
@section('breadcrumb', 'Hasil')

@section('content')
<div class="page-header d-flex justify-content-between"><div><h1 class="page-title">Hasil: {{ $exam->name }}</h1><p class="page-subtitle">{{ $exam->mataPelajaran->nama ?? '-' }} • {{ $exam->rombel->kelas->nama_kelas ?? '-' }} • {{ $exam->questions->count() }} soal</p></div><a href="{{ route('admin.cbt.results.export', $exam) }}" class="btn btn-primary btn-sm">Export CSV</a></div>
@include('layouts.partials.alert')

{{-- Semua cell uraian memakai nama field `score_obtained` yang sama, jadi tanpa
     penanda ini pesan error akan muncul di SEMUA cell sekaligus. `answer_id`
     yang gagal dikembalikan ke halaman ini lewat old() supaya error hanya
     menempel pada kolom yang memang ditolak. --}}
@php $cellGagal = old('answer_id'); @endphp

<div class="card-nexus"><div class="card-body-nexus p-0"><div class="table-responsive"><table class="table-nexus w-100" style="font-size:12px;">
<thead><tr><th>Siswa</th><th>Status</th><th>Skor</th>@foreach($exam->questions as $q)<th title="{{ Str::limit($q->question_text, 60) }}">Q{{ $loop->iteration }}</th>@endforeach<th>Aksi</th></tr></thead>
<tbody>
@forelse($sessions as $s)
<tr>
<td style="font-weight:600;">{{ $s->user->name ?? '-' }}</td>
<td><span class="badge-nexus badge-info">{{ $s->status }}</span></td>
<td style="font-weight:700;">{{ $s->score ?? 0 }}</td>
@foreach($exam->questions as $q)
@php
  $ans = $s->answers->firstWhere('exam_question_id', $q->id);
  $cellIniGagal = $ans !== null && $cellGagal !== null && (string) $cellGagal === (string) $ans->id && $errors->has('score_obtained');
@endphp
<td>
@if(!$ans) <span style="color:var(--text-muted);">-</span>
@elseif($q->type==='essay')
  {{-- `max` mengikuti bobot soal, sama dengan aturan di ExamAnswerController. Soal
       5 poin tidak boleh diberi 100, dan angka di sini yang menjelaskan itu. --}}
  <div>
    <form method="POST" action="{{ route('admin.cbt.answers.nilai', $ans->id) }}" class="d-flex gap-1">
      @csrf
      <input type="hidden" name="answer_id" value="{{ $ans->id }}" />
      <input type="number" name="score_obtained" step="0.01" min="0" max="{{ $q->score }}"
             value="{{ $cellIniGagal ? old('score_obtained', $ans->score_obtained) : $ans->score_obtained }}"
             class="form-control form-control-sm @if($cellIniGagal) is-invalid @endif" style="min-width:72px;" aria-label="Nilai soal {{ $loop->iteration }}" @if($cellIniGagal) data-cell-gagal="{{ $ans->id }}" @endif />
      <button type="submit" class="btn btn-nexus-outline btn-sm">Simpan</button>
    </form>
    @if($cellIniGagal)
      <div class="invalid-feedback d-block">{{ $errors->first('score_obtained') }}</div>
    @endif
  </div>
@else <span class="{{ $ans->is_correct ? 'text-success' : 'text-danger' }}">{{ $ans->is_correct ? '✓' : '✗' }} {{ $ans->score_obtained ?? 0 }}</span>
@endif
</td>
@endforeach
<td class="text-nowrap"><a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Monitor</a></td>
</tr>
@empty<tr><td colspan="{{ 3+$exam->questions->count()+1 }}" class="text-center py-4" style="color:var(--text-muted);">Belum ada peserta.</td></tr>@endforelse
</tbody>
</table></div></div></div>
<p class="mt-2" style="font-size:12px;color:var(--text-muted);">
  Soal pilihan dinilai otomatis. Kolom <strong>Qn</strong> untuk soal uraian bisa diisi manual di sini — nilai maksimum mengikuti bobot soal, dan skor sesi dihitung ulang setiap kali disimpan.
</p>
@endsection