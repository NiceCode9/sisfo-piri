@extends('layouts.app')

@section('title', 'Nexus Admin — Riwayat Kelas')
@section('breadcrumb', 'Riwayat Kelas')

@section('content')
<div class="page-header">
    <h1 class="page-title">Riwayat Kelas</h1>
    <p class="page-subtitle mb-0">Koreksi kelas dan tahun ajaran yang salah catat</p>
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

{{-- Filter anomali. Tanpa ini, admin harus menggulir seluruh daftar siswa
     untuk menemukan yang salah — dan sebagian besar memang benar. --}}
<div class="card-nexus mb-3">
    <div class="card-body-nexus">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @php
                $jumlahFilter = [
                    'semua' => null,
                    'tanpa-aktif' => $jumlahTanpaAktif,
                    'drift' => $jumlahDrift,
                    'ganda-aktif' => $jumlahGanda,
                ];
                $labelFilter = [
                    'semua' => 'Semua siswa',
                    'tanpa-aktif' => 'Tanpa baris aktif',
                    'drift' => 'Pointer tidak sinkron',
                    'ganda-aktif' => 'Dua baris aktif',
                ];
                $totalBermasalah = $jumlahTanpaAktif + $jumlahDrift + $jumlahGanda;
            @endphp

            @if ($totalBermasalah > 0)
                <span class="badge-nexus badge-danger">{{ $totalBermasalah }} siswa perlu diperiksa</span>
            @else
                <span class="badge-nexus badge-success">Semua siswa punya riwayat yang sinkron</span>
            @endif

            <span style="color:var(--text-muted);font-size:12px;">Saring:</span>

            @foreach ($filterTersedia as $f)
                <a href="{{ route('admin.riwayat-kelas.index', array_filter(['filter' => $f === 'semua' ? null : $f, 'search' => request('search')])) }}"
                   class="btn btn-sm {{ $filter === $f ? 'btn-primary' : 'btn-nexus-outline' }}">
                    {{ $labelFilter[$f] }}
                    @if ($jumlahFilter[$f] !== null)
                        ({{ $jumlahFilter[$f] }})
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">{{ $labelFilter[$filter] }}</h5><p class="card-subtitle">{{ $siswas->total() }} siswa</p></div>
        <form method="GET" action="{{ route('admin.riwayat-kelas.index') }}" class="d-flex gap-2">
            @if ($filter !== 'semua')<input type="hidden" name="filter" value="{{ $filter }}">@endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, atau NISN.." class="form-control form-control-sm" style="width:240px" />
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Tahun Ajaran</th><th>Status Data</th><th>Jumlah Riwayat</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    @php
                        $id = $siswa->id;
                        $adaTanpa = in_array($id, $tanpaAktif, true);
                        $adaDrift = in_array($id, $drift, true);
                        $adaGanda = in_array($id, $ganda, true);
                    @endphp
                    <tr>
                        <td style="font-size:12.5px;">{{ $siswa->nis ?? '—' }}</td>
                        <td style="font-weight:600;font-size:13px;">{{ $siswa->user->name ?? '—' }}</td>
                        <td style="font-size:12.5px;">{{ $siswa->kelas?->nama_kelas ?? '—' }}</td>
                        <td style="font-size:12.5px;">{{ $siswa->tahunAjaran?->nama_tahun_ajaran ?? '—' }}</td>
                        <td style="font-size:12px;">
                            @if ($adaTanpa)
                                <span class="badge-nexus badge-danger">Tanpa baris aktif</span>
                            @elseif ($adaGanda)
                                <span class="badge-nexus badge-danger">Dua baris aktif</span>
                            @elseif ($adaDrift)
                                <span class="badge-nexus badge-warning">Pointer tidak sinkron</span>
                            @else
                                <span class="badge-nexus badge-success">Sehat</span>
                            @endif
                        </td>
                        <td style="font-size:12.5px;">{{ $siswa->riwayat_kelas_count }}</td>
                        <td><a href="{{ route('admin.riwayat-kelas.show', $siswa) }}" class="btn btn-nexus-outline btn-sm">Koreksi</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">
                        @if ($filter !== 'semua')
                            Tidak ada siswa dengan masalah ini.
                        @else
                            Tidak ada siswa ditemukan.
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($siswas->hasPages())
        <div class="card-footer-nexus">{{ $siswas->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
