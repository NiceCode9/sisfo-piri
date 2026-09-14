@extends('layouts.app')

@section('title', 'Tugas Saya')
@section('breadcrumb', 'Tugas')

@section('content')
<div class="page-header"><h1 class="page-title">Tugas</h1><p class="page-subtitle mb-0">Tugas untuk rombel Anda</p></div>
@include('layouts.partials.alert')
@include('siswa._nav', ['tabAktif' => 'tugas'])
<div class="card-nexus"><div class="card-body-nexus p-0">
    <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
        <thead><tr><th>Judul</th><th>Mapel</th><th>Deadline</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($tugas as $t)
                @php $sudah = in_array($t->id, $dikumpul); @endphp
                <tr>
                    <td style="font-weight:600;">{{ $t->judul }}</td>
                    <td><span class="badge-nexus badge-info">{{ $t->mataPelajaran->kode ?? '-' }}</span></td>
                    <td>{{ $t->deadline ? $t->deadline->format('d M Y H:i') : '—' }}</td>
                    <td>@if($sudah)<span class="badge-nexus badge-info">Sudah</span>@else<span class="badge-nexus">Belum</span>@endif</td>
                    <td><a href="{{ route('siswa.tugas.show', $t) }}" class="btn btn-nexus-outline btn-sm">Lihat</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada tugas.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div>@if($tugas->hasPages())<div class="px-3 py-2">{{ $tugas->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
