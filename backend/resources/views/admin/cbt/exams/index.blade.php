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
<tr><td style="font-weight:600;">{{ $e->name }}</td><td>{{ $e->rombel->kelas->nama_kelas ?? '-' }}</td><td><span class="badge-nexus badge-info">{{ $e->status }}</span></td><td>{{ $e->duration_minutes }}m</td><td><div class="d-flex gap-1"><a href="{{ route('admin.cbt.exams.show', $e) }}" class="btn btn-nexus-outline btn-sm">Lihat</a><a href="{{ route('admin.cbt.monitoring.index', $e) }}" class="btn btn-nexus-outline btn-sm">Monitoring</a></div></td></tr>
@empty<tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada ujian.</td></tr>@endforelse
</tbody>
</table></div></div>@if($exams->hasPages())<div class="px-3 py-2">{{ $exams->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
