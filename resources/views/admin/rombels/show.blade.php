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
        <div><h5 class="card-title">Penugasan ({{ $histori['penugasan']->count() }})</h5><p class="card-subtitle">Siapa mengajar mapel apa di rombel ini</p></div>
        @can('pengampus.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPengampu"><i class="fa-solid fa-plus"></i> Tambah</button>
        @endcan
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Guru</th><th>Mapel</th><th style="width:60px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($histori['penugasan'] as $p)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $p->guru->nama ?? '-' }}</td>
                            <td><span class="badge-nexus badge-info">{{ $p->mataPelajaran->kode ?? '-' }}</span> <span style="font-size:12.5px;">{{ $p->mataPelajaran->nama ?? '' }}</span></td>
                            <td>
                                @can('pengampus.delete')
                                    <form action="{{ route('admin.pengampus.destroy', $p) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus penugasan ini?"><i class="fa-solid fa-trash"></i></button></form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Belum ada penugasan.</td></tr>
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

@can('pengampus.create')
<div class="modal fade" id="modalTambahPengampu" tabindex="-1" aria-labelledby="modalTambahPengampuLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahPengampuLabel">Tambah Pengampu — {{ $rombel->kelas->nama_kelas ?? '-' }} ({{ $rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.rombels.pengampus.batch', $rombel) }}">
                @csrf
                <div class="modal-body">
                    @if($histori['penugasan']->isNotEmpty())
                        <p class="mb-2" style="font-size:12.5px;color:var(--text-muted);">Mapel sudah terisi:</p>
                        <div class="d-flex gap-1 flex-wrap mb-3">
                            @foreach($histori['penugasan'] as $p)<span class="badge-nexus badge-info">{{ $p->mataPelajaran->kode ?? '?' }}</span>@endforeach
                        </div>
                    @endif
                    @php $galatBaris = collect($errors->messages())->filter(fn ($msgs, $key) => $key === 'baris' || str_starts_with($key, 'baris.'))->flatten(); @endphp
                    @if($galatBaris->isNotEmpty())
                        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($galatBaris as $msg)<li>{{ $msg }}</li>@endforeach</ul></div>
                    @endif
                    <div id="baris-container">
                        @foreach(old('baris', [['guru_id' => '', 'mata_pelajaran_id' => '']]) as $i => $row)
                            @include('admin.rombels._baris_pengampu', ['index' => $i, 'row' => $row])
                        @endforeach
                    </div>
                    <template id="template-baris-pengampu">
                        @include('admin.rombels._baris_pengampu', ['index' => '__INDEX__', 'row' => []])
                    </template>
                    <button type="button" id="tambah-baris-pengampu" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-plus"></i> Tambah Baris</button>
                    <p class="mt-2 mb-0" style="font-size:12px;color:var(--text-muted);">Semua baris tersimpan sekaligus. Satu mapel hanya diampu satu guru per rombel.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-nexus-outline btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let barisPengampuIndex = document.querySelectorAll('#baris-container .baris-pengampu').length;

document.getElementById('tambah-baris-pengampu').addEventListener('click', function () {
    const html = document.getElementById('template-baris-pengampu').innerHTML.replaceAll('__INDEX__', barisPengampuIndex);
    document.getElementById('baris-container').insertAdjacentHTML('beforeend', html);
    barisPengampuIndex++;
});

function hapusBarisPengampu(btn) {
    const rows = document.querySelectorAll('#baris-container .baris-pengampu');
    if (rows.length <= 1) {
        rows[0].querySelectorAll('select').forEach(function (s) { s.value = ''; });
        return;
    }
    btn.closest('.baris-pengampu').remove();
}
</script>
@if(session('bukaModalPengampu'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahPengampu')).show();
});
</script>
@endif
@endcan
@endsection
