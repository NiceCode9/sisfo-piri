@extends('layouts.app')

@section('title', 'Nexus Admin — Pengaturan')
@section('breadcrumb', 'Pengaturan')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pengaturan</h1>
        <p class="page-subtitle mb-0">Kelola pengaturan aplikasi — hanya super-admin</p>
    </div>
</div>

@include('layouts.partials.alert')

<form method="POST" action="{{ route('admin.pengaturans.update') }}">
    @csrf @method('PUT')

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><h5 class="card-title">Absensi</h5></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="time" name="batas_terlambat" value="{{ old('batas_terlambat', $pengaturan['batas_terlambat'] ?? '07:00') }}" class="form-control @error('batas_terlambat') is-invalid @enderror" id="batas_terlambat" required />
                        <label for="batas_terlambat">Batas Terlambat (HH:MM)</label>
                        @error('batas_terlambat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="time" name="jam_cek_belum_hadir" value="{{ old('jam_cek_belum_hadir', $pengaturan['jam_cek_belum_hadir'] ?? '08:00') }}" class="form-control @error('jam_cek_belum_hadir') is-invalid @enderror" id="jam_cek_belum_hadir" required />
                        <label for="jam_cek_belum_hadir">Jam Cek Belum Hadir</label>
                        @error('jam_cek_belum_hadir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" style="font-size:12px;">Cek Belum Hadir Terakhir: <strong>{{ $pengaturan['cek_belum_hadir_terakhir'] ?? '—' }}</strong></label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><h5 class="card-title">Semester</h5></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="semester_ganjil_mulai" value="{{ old('semester_ganjil_mulai', $pengaturan['semester_ganjil_mulai'] ?? '07-01') }}" placeholder="MM-DD" class="form-control @error('semester_ganjil_mulai') is-invalid @enderror" id="semester_ganjil_mulai" required />
                        <label for="semester_ganjil_mulai">Ganjil Mulai (MM-DD)</label>
                        @error('semester_ganjil_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="semester_ganjil_selesai" value="{{ old('semester_ganjil_selesai', $pengaturan['semester_ganjil_selesai'] ?? '12-31') }}" placeholder="MM-DD" class="form-control @error('semester_ganjil_selesai') is-invalid @enderror" id="semester_ganjil_selesai" required />
                        <label for="semester_ganjil_selesai">Ganjil Selesai (MM-DD)</label>
                        @error('semester_ganjil_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="semester_genap_mulai" value="{{ old('semester_genap_mulai', $pengaturan['semester_genap_mulai'] ?? '01-01') }}" placeholder="MM-DD" class="form-control @error('semester_genap_mulai') is-invalid @enderror" id="semester_genap_mulai" required />
                        <label for="semester_genap_mulai">Genap Mulai (MM-DD)</label>
                        @error('semester_genap_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="semester_genap_selesai" value="{{ old('semester_genap_selesai', $pengaturan['semester_genap_selesai'] ?? '06-30') }}" placeholder="MM-DD" class="form-control @error('semester_genap_selesai') is-invalid @enderror" id="semester_genap_selesai" required />
                        <label for="semester_genap_selesai">Genap Selesai (MM-DD)</label>
                        @error('semester_genap_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><h5 class="card-title">Sistem</h5></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="maintenance_mode" value="0" />
                        <input type="checkbox" name="maintenance_mode" value="1" class="form-check-input" id="maintenance_mode" @checked(old('maintenance_mode', $pengaturan['maintenance_mode'] ?? '0')==='1') />
                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="maintenance_pesan" class="form-control @error('maintenance_pesan') is-invalid @enderror" id="maintenance_pesan" style="height:80px" placeholder="Pesan maintenance">{{ old('maintenance_pesan', $pengaturan['maintenance_pesan'] ?? '') }}</textarea>
                        <label for="maintenance_pesan">Pesan Maintenance</label>
                        @error('maintenance_pesan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
</form>

<form method="POST" action="{{ route('admin.pengaturans.reset') }}" class="mt-3">
    @csrf
    <button type="submit" class="btn btn-nexus-outline btn-sm" data-confirm="Reset penanda cek belum-hadir hari ini?"><i class="fa-solid fa-rotate"></i> Re-trigger Cek Belum Hadir</button>
</form>
@endsection
