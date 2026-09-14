@extends('layouts.app')

@section('title', 'Nexus Admin — Histori Rombel')
@section('breadcrumb', 'Histori Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Rombel {{ $rombel->kelas->nama_kelas ?? '-' }}</h1>
        <p class="page-subtitle mb-0">{{ $rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }} • Wali: {{ $rombel->waliGuru->nama ?? '—' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        @can('rombels.edit')
            <a href="{{ route('admin.rombels.edit', $rombel) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Penugasan ({{ $histori['penugasan']->count() }}/{{ $mapels->count() }})</h5><p class="card-subtitle">Pilih guru untuk tiap mapel, lalu simpan sekaligus</p></div>
        @can('pengampus.create')
            @if($mapels->isNotEmpty())
                <button type="submit" form="form-penugasan" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            @endif
        @endcan
    </div>
    <div class="card-body-nexus p-0">
        @can('pengampus.create')
            <form id="form-penugasan" method="POST" action="{{ route('admin.rombels.pengampus.batch', $rombel) }}">@csrf</form>
            @if($errors->has('guru'))<div class="alert alert-danger m-3 mb-0" role="alert">{{ $errors->first('guru') }}</div>@endif
        @endcan
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Mapel</th><th style="min-width:220px;">Guru</th><th style="width:60px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($mapels as $m)
                        @php $tugas = $pengampuPerMapel->get($m->id); @endphp
                        <tr>
                            <td><span class="badge-nexus badge-info">{{ $m->kode }}</span> <span style="font-size:12.5px;">{{ $m->nama }}</span></td>
                            <td>
                                @can('pengampus.create')
                                    <select name="guru[{{ $m->id }}]" form="form-penugasan" class="form-select form-select-sm @error('guru.'.$m->id) is-invalid @enderror" aria-label="Guru {{ $m->kode }}">
                                        <option value="">— Belum ada guru —</option>
                                        @foreach($gurus as $g)<option value="{{ $g->id }}" @selected((string) old('guru.'.$m->id, $tugas?->guru_id ?? '') === (string) $g->id)>{{ $g->nama }}</option>@endforeach
                                    </select>
                                    @error('guru.'.$m->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @else
                                    <span style="font-weight:600;font-size:13px;">{{ $tugas?->guru->nama ?? '—' }}</span>
                                @endcan
                            </td>
                            <td>
                                @if($tugas)
                                    @can('pengampus.delete')
                                        <form action="{{ route('admin.pengampus.destroy', $tugas) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus penugasan ini?"><i class="fa-solid fa-trash"></i></button></form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Belum ada mata pelajaran aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Siswa ({{ $histori['siswa']->count() }})</h5><p class="card-subtitle">Terdaftar di rombel ini tahun berjalan</p></div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>NISN</th></tr></thead>
                <tbody>
                    @forelse($histori['siswa'] as $s)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ $s->nisn ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-4" style="color:var(--text-muted);">Belum ada siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
