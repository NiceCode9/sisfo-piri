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

{{-- Form tambah baris. Hanya muncul kalau siswa belum punya baris `aktif`,
     karena itu keadaan yang membuat siswa lenyap dari rekap dan tidak punya
     jalur perbaikan lain di sistem ini. --}}
@if (! $riwayats->contains('status', 'aktif'))
    <div class="card-nexus mb-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Tambah Baris Riwayat</h5>
                <p class="card-subtitle mb-0">
                    Siswa ini belum punya baris berstatus <code>aktif</code>, jadi ia tidak muncul di rekap
                    absensi, nilai tugas, maupun nilai ujian.
                </p>
            </div>
        </div>
        <div class="card-body-nexus">
            <form method="POST" action="{{ route('admin.riwayat-kelas.store', $siswa) }}" class="d-flex gap-2 flex-wrap align-items-end">
                @csrf
                <div style="width:170px;">
                    <label class="form-label" style="font-size:11px;">Kelas</label>
                    <select name="kelas_id" class="form-select form-select-sm @error('kelas_id') is-invalid @enderror">
                        <option value="">— Pilih Kelas —</option>
                        @foreach ($kelases as $k)
                            <option value="{{ $k->id }}" @selected(old('kelas_id', $siswa->kelas_id) == $k->id)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    @error('kelas_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div style="width:170px;">
                    <label class="form-label" style="font-size:11px;">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" class="form-select form-select-sm @error('tahun_ajaran_id') is-invalid @enderror">
                        <option value="">— Pilih Tahun Ajaran —</option>
                        @foreach ($tahunAjarans as $t)
                            <option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', $siswa->tahun_ajaran_id) == $t->id)>{{ $t->nama_tahun_ajaran }}</option>
                        @endforeach
                    </select>
                    @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div style="width:150px;">
                    <label class="form-label" style="font-size:11px;">Status</label>
                    <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                        @foreach (['aktif', 'mengulang', 'lulus', 'pindah', 'dropout'] as $s)
                            <option value="{{ $s }}" @selected(old('status', 'aktif') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div style="flex:1;min-width:180px;">
                    <label class="form-label" style="font-size:11px;">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="form-control form-control-sm" maxlength="255" />
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah</button>
            </form>
            <p style="font-size:11.5px;color:var(--text-muted);margin-top:8px;margin-bottom:0;">
                Memilih status <code>aktif</code> sekaligus menyelaraskan data siswa (kelas dan tahun ajaran)
                agar keduanya tidak berbeda pendapat. Status lain hanya menambah catatan historis.
            </p>
        </div>
    </div>
@endif

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
                                            <option value="{{ $t->id }}" @selected($t->id === $r->tahun_ajaran_id)>{{ $t->nama_tahun_ajaran }}</option>
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
                                    {{-- Status `aktif` di tahun yang sudah lewat itu BENAR: siswa itu
                                     memang aktif di kelas itu sepanjang tahun itu.
                                     Yang perlu dijelaskan hanya bahwa ini bukan
                                     kelas sekarang. `pindah` hanya dipakai untuk
                                     pindah kelas DALAM satu tahun ajaran, dan baris
                                     ini tidak boleh diubah supaya sejarah tetap
                                     jujur. --}}
                                    @if ($r->status === 'aktif' && $tahunAktif && $r->tahun_ajaran_id !== $tahunAktif->id)
                                        <div style="font-size:11px;color:var(--text-muted);">kelas waktu itu, bukan kelas sekarang</div>
                                    @endif
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