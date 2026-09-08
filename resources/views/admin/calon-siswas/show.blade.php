@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Calon Siswa')
@section('breadcrumb', 'Detail Calon Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $calon->nama_lengkap }}</h1>
        <p class="page-subtitle mb-0">{{ $calon->no_pendaftaran }} • {{ $calon->jalurPendaftaran->nama_jalur ?? '-' }} • {{ $calon->tahunAjaran->nama_tahun_ajaran ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        @can('calon-siswas.edit')
            <a href="{{ route('admin.calon-siswas.edit', $calon) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <h5 class="card-title">Data Pendaftar</h5>
                <span class="badge-nexus {{ $calon->status_pendaftaran === 'diterima' ? 'badge-info' : ($calon->status_pendaftaran === 'ditolak' ? 'badge-danger' : 'badge-neutral') }}">{{ $calon->status_pendaftaran }}</span>
            </div>
            <div class="card-body-nexus">
                <div class="row g-3" style="font-size: 13px;">
                    <div class="col-6"><strong>NIK:</strong> {{ $calon->nik }}</div>
                    <div class="col-6"><strong>NISN:</strong> {{ $calon->nisn ?? '-' }}</div>
                    <div class="col-6"><strong>Jenis Kelamin:</strong> {{ $calon->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                    <div class="col-6"><strong>TTL:</strong> {{ $calon->tempat_lahir }}, {{ \Carbon\Carbon::parse($calon->tanggal_lahir)->format('d M Y') }}</div>
                    <div class="col-6"><strong>Agama:</strong> {{ $calon->agama }}</div>
                    <div class="col-6"><strong>Asal Sekolah:</strong> {{ $calon->asal_sekolah ?? '-' }}</div>
                    <div class="col-12"><strong>Alamat:</strong> {{ $calon->alamat }}</div>
                    <div class="col-6"><strong>No HP:</strong> {{ $calon->no_hp ?? '-' }}</div>
                    <div class="col-6"><strong>Email:</strong> {{ $calon->email ?? '-' }}</div>
                    <div class="col-6"><strong>Ayah:</strong> {{ $calon->nama_ayah ?? '-' }} ({{ $calon->pekerjaan_ayah ?? '-' }})</div>
                    <div class="col-6"><strong>Ibu:</strong> {{ $calon->nama_ibu ?? '-' }} ({{ $calon->pekerjaan_ibu ?? '-' }})</div>
                    <div class="col-6"><strong>No HP Ortu:</strong> {{ $calon->no_hp_orang_tua ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Berkas</h5></div>
            <div class="card-body-nexus">
                @if ($calon->berkasCalonSiswa)
                    @php $b = $calon->berkasCalonSiswa; @endphp
                    <div class="table-responsive">
                        <table class="table-nexus w-100" style="font-size: 13px;">
                            <thead><tr><th>Berkas</th><th>File</th></tr></thead>
                            <tbody>
                                @foreach (['ijazah_path'=>'Ijazah','kk_path'=>'KK','akta_path'=>'Akta','foto_path'=>'Foto','skl_path'=>'SKL'] as $field=>$label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>
                                            @if ($b->$field)
                                                <a href="{{ Storage::disk('public')->url($b->$field) }}" target="_blank" class="text-primary">Lihat</a>
                                            @else — @endif
                                            @if (in_array($field, $b->berkas_perlu_perbaikan ?? []))
                                                <span class="badge-nexus badge-danger">perlu perbaikan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3" style="font-size: 12px; color: var(--text-muted);">
                        Verifikasi: {{ $b->status_verifikasi ? 'Terverifikasi' : 'Belum' }} @if($b->alasan_penolakan) • Alasan: {{ $b->alasan_penolakan }} @endif
                    </div>
                @else
                    <p style="font-size: 13px; color: var(--text-muted);">Belum ada berkas.</p>
                @endif

                @can('berkas-calon-siswas.edit')
                <hr class="my-3" />
                <form method="POST" action="{{ route('admin.calon-siswas.berkas', $calon) }}" class="d-flex flex-column gap-2" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check">
                            <input type="hidden" name="status_verifikasi" value="0" />
                            <input type="checkbox" name="status_verifikasi" value="1" id="status_verifikasi" class="form-check-input" @checked(old('status_verifikasi', $calon->berkasCalonSiswa->status_verifikasi ?? false)) />
                            <label class="form-check-label" for="status_verifikasi">Terverifikasi</label>
                        </div>
                    </div>

                    <div style="font-weight:600;font-size:13px;margin-top:4px;">Ganti Berkas (kosongkan bila tidak diubah)</div>
                    <div style="font-size:11px;color:var(--text-muted);">PDF 5MB (ijazah/kk/akta/skl), Foto JPG/PNG 2MB. Akan hapus file lama bila diganti.</div>
                    @php $berkas2 = $calon->berkasCalonSiswa; @endphp
                    <div class="row g-2">
                        @foreach (['ijazah_path'=>['label'=>'Ijazah','accept'=>'.pdf'], 'kk_path'=>['label'=>'KK','accept'=>'.pdf'], 'akta_path'=>['label'=>'Akta','accept'=>'.pdf'], 'foto_path'=>['label'=>'Foto','accept'=>'image/*'], 'skl_path'=>['label'=>'SKL','accept'=>'.pdf']] as $fld=>$meta)
                            <div class="col-12 col-sm-6">
                                <label class="form-label" style="font-size:12px;" for="berkas-{{ $fld }}">{{ $meta['label'] }}</label>
                                @if ($berkas2 && $berkas2->$fld)
                                    <div style="font-size:11px;">Saat ini: <a href="{{ Storage::disk('public')->url($berkas2->$fld) }}" target="_blank" class="text-primary">Lihat</a></div>
                                @endif
                                <input type="file" name="{{ $fld }}" id="berkas-{{ $fld }}" accept="{{ $meta['accept'] }}" class="form-control form-control-sm @error($fld) is-invalid @enderror" />
                                @error($fld)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <label class="form-label" style="font-size: 12px;">Berkas perlu perbaikan</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['ijazah_path','kk_path','akta_path','foto_path','skl_path'] as $f)
                            <div class="form-check">
                                <input type="checkbox" name="berkas_perlu_perbaikan[]" value="{{ $f }}" id="perlu-{{ $f }}" class="form-check-input" @checked(in_array($f, old('berkas_perlu_perbaikan', $calon->berkasCalonSiswa->berkas_perlu_perbaikan ?? []))) />
                                <label class="form-check-label" style="font-size: 12px;" for="perlu-{{ $f }}">{{ $f }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-floating">
                        <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-control" placeholder="Alasan" style="height: 70px">{{ old('alasan_penolakan', $calon->berkasCalonSiswa->alasan_penolakan ?? '') }}</textarea>
                        <label for="alasan_penolakan">Alasan Penolakan</label>
                    </div>
                    <div class="form-floating">
                        <textarea name="catatan_berkas" id="catatan_berkas" class="form-control" placeholder="Catatan" style="height: 70px">{{ old('catatan_berkas', $calon->berkasCalonSiswa->catatan_berkas ?? '') }}</textarea>
                        <label for="catatan_berkas">Catatan Berkas</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan Verifikasi</button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Status Pendaftaran</h5></div>
            <div class="card-body-nexus">
                @can('calon-siswas.edit')
                <form method="POST" action="{{ route('admin.calon-siswas.status', $calon) }}">
                    @csrf @method('PATCH')
                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="menunggu" @selected($calon->status_pendaftaran==='menunggu')>Menunggu</option>
                            <option value="diterima" @selected($calon->status_pendaftaran==='diterima')>Diterima</option>
                            <option value="ditolak" @selected($calon->status_pendaftaran==='ditolak')>Ditolak</option>
                            <option value="daftar_ulang" @selected($calon->status_pendaftaran==='daftar_ulang')>Daftar Ulang</option>
                        </select>
                    </div>
                    <div class="form-floating mb-2">
                        <textarea name="catatan" id="catatan_status" class="form-control" placeholder="Catatan" style="height: 60px"></textarea>
                        <label for="catatan_status">Catatan (opsional)</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Ubah Status</button>
                </form>
                @else
                    <p style="font-size: 13px;">{{ $calon->status_pendaftaran }}</p>
                @endcan

                <hr class="my-3" />
                <h6 style="font-size: 13px; font-weight: 600;">Riwayat Status</h6>
                <div class="d-flex flex-column gap-2">
                    @forelse ($calon->logStatusPendaftaran->sortByDesc('created_at') as $log)
                        <div style="font-size: 12px; border-left: 2px solid var(--border-color); padding-left: 8px;">
                            <div><strong>{{ $log->status_sebelumnya ?? '—' }} → {{ $log->status_baru }}</strong> • {{ $log->created_at->format('d M Y H:i') }}</div>
                            <div style="color: var(--text-muted);">oleh {{ $log->user->name ?? 'sistem' }} @if($log->catatan) — {{ $log->catatan }} @endif</div>
                        </div>
                    @empty
                        <span style="font-size: 12px; color: var(--text-muted);">Belum ada log.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
