@extends('layouts.app')

@section('title', 'Jurnalistik — Komentar')
@section('breadcrumb', 'Komentar')

@section('content')
<div class="page-header">
    <h1 class="page-title">Moderasi Komentar</h1>
    <p class="page-subtitle mb-0">Setujui, tolak, atau hapus komentar pembaca.</p>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Komentar</h5>
            <p class="card-subtitle">Total {{ $komentars->total() }} komentar</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Berita</th>
                        <th>Pengirim</th>
                        <th>Isi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($komentars as $k)
                        <tr>
                            <td>
                                <a href="{{ route('berita.show', $k->berita) }}" class="text-decoration-none fw-semibold" target="_blank">
                                    {{ \Illuminate\Support\Str::limit($k->berita?->judul, 40) }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $k->nama ?: $k->user?->name }}</div>
                                @if($k->email)
                                    <small class="text-muted">{{ $k->email }}</small>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($k->isi, 80) }}</td>
                            <td>
                                @if($k->status === 'approved')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($k->status === 'rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('jurnalistik.komentars.approve', $k) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success" title="Setujui"><i class="fa-solid fa-check"></i></button>
                                </form>
                                <form method="POST" action="{{ route('jurnalistik.komentars.reject', $k) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-warning" title="Tolak"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                                <form method="POST" action="{{ route('jurnalistik.komentars.destroy', $k) }}" class="d-inline" onsubmit="return confirm('Hapus komentar ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada komentar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-body-nexus border-top">
        {{ $komentars->links() }}
    </div>
</div>
@endsection