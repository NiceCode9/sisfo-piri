@extends('layouts.app')

@section('title', 'Hasil Ujian')
@section('breadcrumb', 'Hasil')

@section('content')
<div class="page-header d-flex justify-content-between"><div><h1 class="page-title">Hasil: {{ $exam->name }}</h1><p class="page-subtitle">{{ $exam->mataPelajaran->nama ?? '-' }} • {{ $exam->rombel->kelas->nama_kelas ?? '-' }} • {{ $exam->questions->count() }} soal</p></div><a href="{{ route('admin.cbt.results.export', $exam) }}" class="btn btn-primary btn-sm">Export CSV</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus p-0"><div class="table-responsive"><table class="table-nexus w-100" style="font-size:12px;">
<thead><tr><th>Siswa</th><th>Status</th><th>Skor</th>@foreach($exam->questions as $q)<th title="{{ Str::limit($q->question_text, 60) }}">Q{{ $loop->iteration }}</th>@endforeach<th>Aksi</th></tr></thead>
<tbody>
@forelse($sessions as $s)
<tr>
<td style="font-weight:600;">{{ $s->user->name ?? '-' }}</td>
<td><span class="badge-nexus badge-info">{{ $s->status }}</span></td>
<td style="font-weight:700;">{{ $s->score ?? 0 }}</td>
@foreach($exam->questions as $q)
@php $ans = $s->answers->firstWhere('exam_question_id', $q->id); @endphp
<td>
@if(!$ans) <span style="color:var(--text-muted);">-</span>
@elseif($q->type==='essay') <span class="badge-nexus {{ $ans->score_obtained>0 ? 'badge-info' : '' }}">{{ $ans->score_obtained ?? 0 }}/{{ $q->score }}</span>
@else <span class="{{ $ans->is_correct ? 'text-success' : 'text-danger' }}">{{ $ans->is_correct ? '✓' : '✗' }} {{ $ans->score_obtained ?? 0 }}</span>
@endif
</td>
@endforeach
<td><a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Monitor</a></td>
</tr>
@empty<tr><td colspan="{{ 3+$exam->questions->count()+1 }}" class="text-center py-4" style="color:var(--text-muted);">Belum ada peserta.</td></tr>@endforelse
</tbody>
</table></div></div></div>
<p class="mt-2" style="font-size:12px;color:var(--text-muted);">Essay dikoreksi via <code>POST admin/cbt/answers/{answer}/nilai</code> per soal — nilai otomatis re-hitung skor sesi.</p>
@endsection
