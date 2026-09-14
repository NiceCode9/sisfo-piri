@extends('layouts.app')

@section('title', 'Nexus Admin — Nilai Tugas')
@section('breadcrumb', 'Nilai Tugas')

@section('content')
<div class="page-header"><h1 class="page-title">Nilai: {{ $tugas->judul }}</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus p-0">
    <form method="POST" action="{{ route('admin.tugas.nilai.simpan', $tugas) }}">
        @csrf
        <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
            <thead><tr><th>Siswa</th><th>File</th><th>Nilai (0-100)</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($pengumpulans as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p->siswa->user->name ?? '-' }} @if($p->is_terlambat)<span class="badge-nexus badge-warning">Terlambat</span>@endif</td>
                        <td>@if($p->file_path)<a href="{{ Storage::disk('public')->url($p->file_path) }}" target="_blank" class="text-primary">Download</a>@else — @endif</td>
                        <td style="min-width:100px;"><input type="number" name="nilai[{{ $p->id }}]" value="{{ old('nilai.'.$p->id, $p->nilai) }}" min="0" max="100" class="form-control form-control-sm @error('nilai.'.$p->id) is-invalid @enderror" />@error('nilai.'.$p->id)<div class="invalid-feedback">{{ $message }}</div>@enderror</td>
                        <td><input type="text" name="catatan_guru[{{ $p->id }}]" value="{{ old('catatan_guru.'.$p->id, $p->catatan_guru) }}" class="form-control form-control-sm" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada pengumpulan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($pengumpulans->isNotEmpty())<div class="p-3"><button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan Nilai</button></div>@endif
    </form>
</div></div>
@endsection
