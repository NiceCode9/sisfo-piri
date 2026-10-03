@extends('layouts.app')

@section('title', 'Nexus Admin — Riwayat Kelas')
@section('breadcrumb', 'Riwayat Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Riwayat Kelas</h1>
        <p class="page-subtitle mb-0">Koreksi kelas dan tahun ajaran yang salah catat</p>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mb-3">
    <div class="card-body-nexus">
        <div class="alert alert-warning mb-0" style="font-size:13px;">
            <strong>Wajib dibaca.</strong> Riwayat kelas adalah sumber keanggotaan rombel, bukan sekadar catatan.
            Mengubah atau menghapus baris di sini langsung mengubah rekap kehadiran, nilai tugas, dan nilai
            ujian siswa tersebut untuk kelas yang dimaksud. Data absensi, tugas, dan ujian sendiri
            <em>tidak</em> ikut terhapus — hanya munculannya dari rekap yang hilang.
        </div>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Pilih Siswa</h5><p class="card-subtitle">{{ $siswas->total() }} siswa</p></div>
        <form method="GET" action="{{ route('admin.riwayat-kelas.index') }}" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa.." class="form-control form-control-sm" style="width:200px" />
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Tahun Ajaran</th><th>Jumlah Riwayat</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    <tr>
                        <td style="font-size:12.5px;">{{ $siswa->nis ?? '—' }}</td>
                        <td style="font-weight:600;font-size:13px;">{{ $siswa->user->name ?? '—' }}</td>
                        <td style="font-size:12.5px;">{{ $siswa->kelas?->nama_kelas ?? '—' }}</td>
                        <td style="font-size:12.5px;">{{ $siswa->tahunAjaran?->nama ?? '—' }}</td>
                        <td style="font-size:12.5px;">{{ $siswa->riwayatKelas()->count() }}</td>
                        <td><a href="{{ route('admin.riwayat-kelas.show', $siswa) }}" class="btn btn-nexus-outline btn-sm">Koreksi</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($siswas->hasPages())
        <div class="card-footer-nexus">{{ $siswas->links() }}</div>
    @endif
</div>
@endsection