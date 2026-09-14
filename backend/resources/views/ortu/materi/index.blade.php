@extends('layouts.app')

@section('title', 'Materi Anak')
@section('breadcrumb', 'Materi')

@section('content')
<div class="page-header"><h1 class="page-title">Materi Anak</h1><p class="page-subtitle mb-0">Materi untuk rombel anak Anda</p></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus p-0">
    <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
        <thead><tr><th>Judul</th><th>Mapel</th><th>Rombel</th><th>Tipe</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($materis as $m)
                <tr>
                    <td style="font-weight:600;">{{ $m->judul }}</td>
                    <td><span class="badge-nexus badge-info">{{ $m->mataPelajaran->kode ?? '-' }}</span></td>
                    <td>{{ $m->rombel->kelas->nama_kelas ?? '-' }}</td>
                    <td><span class="badge-nexus badge-neutral">{{ ucfirst($m->tipe) }}</span></td>
                    <td><a href="{{ route('ortu.materi.show', $m) }}" class="btn btn-nexus-outline btn-sm">Lihat</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada materi.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>@if($materis->hasPages())<div class="px-3 py-2">{{ $materis->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
