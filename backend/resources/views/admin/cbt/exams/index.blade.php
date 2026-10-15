@extends('layouts.app')

@section('title', 'Nexus Admin — Ujian CBT')
@section('breadcrumb', 'Ujian')

@section('content')
<div class="page-header d-flex justify-content-between gap-3"><div><h1 class="page-title">Ujian CBT</h1></div><a href="{{ route('admin.cbt.exams.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Buat Ujian</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus p-0"><div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
<thead><tr><th>Nama</th><th>Rombel</th><th>Status</th><th>Durasi</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($exams as $e)
<tr>
<td style="font-weight:600;">{{ $e->name }}</td>
<td>{{ $e->rombel->kelas->nama_kelas ?? '-' }}</td>
<td><span class="badge-nexus {{ $e->statusBadge() }}">{{ $e->statusLabel() }}</span></td>
<td>{{ $e->duration_minutes }}m</td>
<td><div class="d-flex gap-1 flex-wrap">
<a href="{{ route('admin.cbt.exams.show', $e) }}" class="btn btn-nexus-outline btn-sm">Lihat</a>
@can('update', $e)<a href="{{ route('admin.cbt.exams.edit', $e) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil" aria-hidden="true"></i> Edit</a>@endcan
<a href="{{ route('admin.cbt.results.index', $e) }}" class="btn btn-nexus-outline btn-sm">Hasil</a>
<a href="{{ route('admin.cbt.monitoring.index', $e) }}" class="btn btn-nexus-outline btn-sm">Monitoring</a>
@can('delete', $e)<form action="{{ route('admin.cbt.exams.destroy', $e) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus ujian &quot;{{ $e->name }}&quot; beserta soal dan tokennya?">Hapus</button></form>@endcan
</div></td>
</tr>
@empty<tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada ujian.</td></tr>@endforelse
</tbody>
</table></div></div>@if($exams->hasPages())<div class="px-3 py-2">{{ $exams->links('pagination::bootstrap-5') }}</div>@endif</div>

{{--
  Kolom Aksi sengaja penuh, bukan menu titik tiga. Guru yang sedang mencari
  apa saja yang tersedia untuk satu ujian harus bisa melihat semuanya tanpa
  klik dulu, dan Hasil serta Monitoring memang sering dipakai berulang.
--}}
@endsection