@extends('layouts.app')

@section('title', 'Materi Saya')
@section('breadcrumb', 'Materi')

@section('content')
<div class="page-header"><h1 class="page-title">Materi</h1><p class="page-subtitle mb-0">Materi untuk rombel Anda</p></div>
@include('layouts.partials.alert')
@include('siswa._nav', ['tabAktif' => 'materi'])
<div class="card-nexus"><div class="card-body-nexus p-0">
    <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
        <thead><tr><th>Judul</th><th>Mapel</th><th>Tipe</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($materis as $m)
                <tr>
                    <td style="font-weight:600;">{{ $m->judul }}</td>
                    <td><span class="badge-nexus badge-info">{{ $m->mataPelajaran->kode ?? '-' }}</span></td>
                    <td><span class="badge-nexus badge-neutral">{{ ucfirst($m->tipe) }}</span></td>
                    <td><a href="{{ route('siswa.materi.show', $m) }}" class="btn btn-nexus-outline btn-sm">Lihat</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada materi.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>@if($materis->hasPages())<div class="px-3 py-2">{{ $materis->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
