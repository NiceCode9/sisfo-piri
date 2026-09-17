@extends('layouts.app')

@section('title', 'Nexus Admin — Bank Soal')
@section('breadcrumb', 'Bank Soal')

@section('content')
<div class="page-header d-flex justify-content-between gap-3"><div><h1 class="page-title">Bank Soal</h1><p class="page-subtitle">Per mata pelajaran oleh pengampu — lintas rombel/tahun</p></div><a href="{{ route('admin.cbt.banks.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Buat Bank</a></div>
@include('layouts.partials.alert')
<form method="GET" class="mb-3 d-flex gap-2">
<select name="mapel" class="form-select form-select-sm" style="max-width:220px;"><option value="">— Semua Mapel —</option>@foreach($mapels as $m)<option value="{{ $m->id }}" @selected(request('mapel')==$m->id)>{{ $m->nama }}</option>@endforeach</select>
<button type="submit" class="btn btn-nexus-outline btn-sm">Filter</button>
</form>
<div class="card-nexus"><div class="card-body-nexus p-0"><div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
<thead><tr><th>Nama</th><th>Mapel</th><th>Guru</th><th>Soal</th><th>Aksi</th></tr></thead>
<tbody>
@forelse($banks as $b)
<tr><td style="font-weight:600;">{{ $b->nama }}</td><td>{{ $b->mataPelajaran->nama ?? '-' }}</td><td>{{ $b->guru->nama ?? '-' }}</td><td>{{ $b->questions_count }}</td><td><a href="{{ route('admin.cbt.banks.show', $b) }}" class="btn btn-nexus-outline btn-sm">Lihat</a></td></tr>
@empty<tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada bank.</td></tr>@endforelse
</tbody>
</table></div></div>@if($banks->hasPages())<div class="px-3 py-2">{{ $banks->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
