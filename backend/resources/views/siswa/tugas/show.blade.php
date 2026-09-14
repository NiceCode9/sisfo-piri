@extends('layouts.app')

@section('title', 'Detail Tugas')
@section('breadcrumb', 'Tugas')

@section('content')
<div class="page-header d-flex justify-content-between gap-3"><div><h1 class="page-title">{{ $tugas->judul }}</h1><p class="page-subtitle mb-0">{{ $tugas->mataPelajaran->kode ?? '-' }} • {{ $tugas->deadline ? $tugas->deadline->format('d M Y H:i') : 'Tanpa deadline' }}</p></div><a href="{{ route('siswa.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Kembali</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <p style="font-size:13px;">{{ $tugas->deskripsi ?? '—' }}</p>
    @if($pengumpulan)
        <div class="alert alert-info" style="font-size:13px;">
            <div>Sudah dikumpulkan @if($pengumpulan->is_terlambat)<span class="badge-nexus badge-warning">Terlambat</span>@endif</div>
            @if($pengumpulan->file_path)<div><a href="{{ Storage::disk('public')->url($pengumpulan->file_path) }}" target="_blank" class="text-primary">File Anda</a></div>@endif
            @if($pengumpulan->nilai !== null)<div><strong>Nilai: {{ $pengumpulan->nilai }}</strong> @if($pengumpulan->nilai < ($tugas->mataPelajaran->kkm ?? 75))<span class="badge-nexus badge-danger">Di bawah KKM</span>@endif</div>@endif
            @if($pengumpulan->catatan_guru)<div>Catatan: {{ $pengumpulan->catatan_guru }}</div>@endif
        </div>
    @endif
    <form method="POST" action="{{ route('siswa.tugas.kumpul', $tugas) }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-12"><label class="form-label" style="font-size:12px;" for="file">File</label><input type="file" name="file" id="file" class="form-control form-control-sm @error('file') is-invalid @enderror" />@error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" style="font-size:12px;" for="jawaban_text">Jawaban Teks</label><textarea name="jawaban_text" id="jawaban_text" class="form-control @error('jawaban_text') is-invalid @enderror" style="height:100px">{{ old('jawaban_text', $pengumpulan->jawaban_text ?? '') }}</textarea>@error('jawaban_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload"></i> Kumpulkan</button>
    </form>
</div></div>
@endsection
