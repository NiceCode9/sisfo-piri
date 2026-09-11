@extends('layouts.app')

@section('title', 'Nexus Admin — Salin Rombel')
@section('breadcrumb', 'Salin Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Salin Rombel</h1>
        <p class="page-subtitle mb-0">Bentuk rombel + salin penugasan dari tahun lain (yang sudah ada dilewati)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Sumber & Tujuan</h5><p class="card-subtitle">Tahun sumber: {{ $jumlahSumber }} rombel</p></div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.rombels.salin.proses') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6">
                    <label class="form-label" for="tahun_sumber_id">Tahun Sumber <span class="text-danger">*</span></label>
                    <select name="tahun_sumber_id" id="tahun_sumber_id" class="form-select @error('tahun_sumber_id') is-invalid @enderror" required>
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" @selected((string) old('tahun_sumber_id', $tahunSumber->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('tahun_sumber_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label" for="tahun_tujuan_id">Tahun Tujuan <span class="text-danger">*</span></label>
                    <select name="tahun_tujuan_id" id="tahun_tujuan_id" class="form-select @error('tahun_tujuan_id') is-invalid @enderror" required>
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" @selected((string) old('tahun_tujuan_id', $tahunTujuan->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('tahun_tujuan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="alert alert-info py-2" style="font-size:12.5px;">Rombel (beserta wali) dibentuk di tahun tujuan, lalu penugasannya disalin. Baris yang sudah ada dilewati dan dilaporkan. Setelah salin, ubah manual yang bertukar guru.</div>
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-copy"></i> Salin Sekarang</button>
            </div>
        </form>
    </div>
</div>
@endsection
