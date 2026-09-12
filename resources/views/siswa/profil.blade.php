@extends('layouts.app')

@section('title', 'Profil Saya')
@section('breadcrumb', 'Profil')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-subtitle mb-0">{{ auth()->user()->name }} — NISN {{ auth()->user()->username }}</p>
    </div>
</div>

@include('layouts.partials.alert')

@include('siswa._nav', ['tabAktif' => 'profil'])

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Data Resmi</h5></div>
            <div class="card-body-nexus text-center">
                @if($siswa?->foto_path)
                    <img src="{{ Storage::disk('public')->url($siswa->foto_path) }}" alt="Foto" class="rounded mb-2" style="width:140px;height:180px;object-fit:cover;" />
                @else
                    <div class="rounded mx-auto mb-2 d-flex align-items-center justify-content-center fw-bold" style="width:140px;height:180px;background:var(--bg-surface);color:var(--text-muted);font-size:48px;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                @endif
                <div style="font-size:13px;" class="text-start mt-2">
                    <div><strong>NIS:</strong> {{ $siswa->nis ?? '-' }}</div>
                    <div><strong>NISN:</strong> {{ $siswa->nisn ?? auth()->user()->username }}</div>
                    <div><strong>Kelas:</strong> {{ $siswa->kelas->nama_kelas ?? '-' }}</div>
                    <div><strong>Status:</strong> {{ $siswa ? ($siswa->is_aktif ? 'aktif' : 'nonaktif') : '-' }}</div>
                </div>
                <p class="mt-2 mb-0" style="font-size:11.5px;color:var(--text-muted);">Data resmi terkunci. Hubungi TU bila ada kesalahan.</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Ubah Data</h5></div>
            <div class="card-body-nexus">
                @if(!$siswa)
                    <div class="alert alert-warning" role="alert" style="font-size:13px;">Belum ada data siswa terkait akun ini. Kontak dan foto hanya tersedia setelah menjadi siswa.</div>
                @endif
                <form method="POST" action="{{ route('siswa.profil.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="password" name="password_saat_ini" class="form-control @error('password_saat_ini') is-invalid @enderror" id="password_saat_ini" placeholder="Kosongkan bila tidak ganti password" autocomplete="current-password" />
                                <label for="password_saat_ini">Password Saat Ini (wajib bila ganti password)</label>
                                @error('password_saat_ini')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Kosongkan bila tidak diubah" autocomplete="new-password" />
                                <label for="password">Password Baru</label>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-floating">
                                <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Ulangi password" autocomplete="new-password" />
                                <label for="password_confirmation">Ulangi Password</label>
                            </div>
                        </div>
                    </div>
                    @if($siswa)
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <div class="form-floating">
                                    <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $siswa->nama_ayah) }}" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah" placeholder="Nama ayah" />
                                    <label for="nama_ayah">Nama Ayah</label>
                                    @error('nama_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-floating">
                                    <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $siswa->pekerjaan_ayah) }}" class="form-control @error('pekerjaan_ayah') is-invalid @enderror" id="pekerjaan_ayah" placeholder="Pekerjaan ayah" />
                                    <label for="pekerjaan_ayah">Pekerjaan Ayah</label>
                                    @error('pekerjaan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-floating">
                                    <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $siswa->nama_ibu) }}" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu" placeholder="Nama ibu" />
                                    <label for="nama_ibu">Nama Ibu</label>
                                    @error('nama_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-floating">
                                    <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $siswa->pekerjaan_ibu) }}" class="form-control @error('pekerjaan_ibu') is-invalid @enderror" id="pekerjaan_ibu" placeholder="Pekerjaan ibu" />
                                    <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
                                    @error('pekerjaan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-floating">
                                    <input type="text" name="no_hp_orang_tua" value="{{ old('no_hp_orang_tua', $siswa->no_hp_orang_tua) }}" class="form-control @error('no_hp_orang_tua') is-invalid @enderror" id="no_hp_orang_tua" placeholder="No HP ortu" />
                                    <label for="no_hp_orang_tua">No HP Ortu</label>
                                    @error('no_hp_orang_tua')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" style="font-size:12px;" for="foto">Foto (maks 2MB)</label>
                                <input type="file" name="foto" id="foto" accept="image/*" class="form-control form-control-sm @error('foto') is-invalid @enderror" />
                                @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
