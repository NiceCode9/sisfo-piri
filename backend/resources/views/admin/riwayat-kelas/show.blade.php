@extends('layouts.app')

@section('title', 'Nexus Admin — Koreksi Riwayat Kelas')
@section('breadcrumb', 'Riwayat Kelas')

@section('content')
<div class="page-header d-flex justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $siswa->user->name ?? 'Siswa' }}</h1>
        <p class="page-subtitle mb-0">NIS {{ $siswa->nis ?? '—' }} • {{ $siswa->nisn ?? '—' }}</p>
    </div>
    <a href="{{ route('admin.riwayat-kelas.index') }}" class="btn btn-nexus-outline btn-sm">Kembali</a>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Baris Riwayat ({{ $riwayats->count() }})</h5>
        <p class="card-subtitle">Satu siswa boleh punya satu baris <code>aktif</code> per tahun ajaran</p></div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100">
            <thead><tr><th>Kelas</th><th>Tahun Ajaran</th><th>Status</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($riwayats as $r)
                    <tr>
                        <td colspan="5">
                            <form method="POST" action="{{ route('admin.riwayat-kelas.update', $r) }}" class="d-flex gap-2 flex-wrap align-items-end">
                                @csrf
                                @method('PUT')
                                <div style="width:150px;">
                                    <label class="form-label" style="font-size:11px;">Kelas</label>
                                    <select name="kelas_id" class="form-select form-select-sm">
                                        @foreach ($kelases as $k)
                                            <option value="{{ $k->id }}" @selected($k->id === $r->kelas_id)>{{ $k->nama_kelas }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width:150px;">
                                    <label class="form-label" style="font-size:11px;">Tahun Ajaran</label>
                                    <select name="tahun_ajaran_id" class="form-select form-select-sm">
                                        @foreach ($tahunAjarans as $t)
                                            <option value="{{ $t->id }}" @selected($t->id === $r->tahun_ajaran_id)>{{ $t->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width:130px;">
                                    <label class="form-label" style="font-size:11px;">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        @foreach (['aktif', 'mengulang', 'lulus', 'pindah', 'dropout'] as $s)
                                            <option value="{{ $s }}" @selected($s === $r->status)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="flex:1;min-width:160px;">
                                    <label class="form-label" style="font-size:11px;">Keterangan</label>
                                    <input type="text" name="keterangan" value="{{ $r->keterangan }}" class="form-control form-control-sm" maxlength="255" />
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.riwayat-kelas.destroy', $r) }}" class="mt-2 d-flex gap-2 align-items-center">
                                @csrf
                                @method('DELETE')
                                <label class="d-flex align-items-center gap-1" style="font-size:12px;color:var(--text-muted);">
                                    <input type="checkbox" name="sadar" value="1" />
                                    Saya paham baris ini dihapus dari rekap kelas
                                </label>
                                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Hapus baris riwayat ini? Rekap kehadiran dan nilai siswa untuk kelas tersebut akan langsung hilang dari tampilan.">
                                    <i class="fa-solid fa-trash"></i> Hapus Baris
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted);">Belum ada riwayat kelas.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection