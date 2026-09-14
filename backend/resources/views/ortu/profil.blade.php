@extends('layouts.app')

@section('title', 'Profil Orang Tua')
@section('breadcrumb', 'Profil')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ganti Password</h1>
        <p class="page-subtitle mb-0">{{ auth()->user()->name }} — {{ auth()->user()->username }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ortu.dashboard') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('ortu.profil.update') }}">
            @csrf @method('PUT')
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="form-floating">
                        <input type="password" name="password_saat_ini" class="form-control @error('password_saat_ini') is-invalid @enderror" id="password_saat_ini" placeholder="Password saat ini" autocomplete="current-password" />
                        <label for="password_saat_ini">Password Saat Ini</label>
                        @error('password_saat_ini')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Password baru" autocomplete="new-password" />
                        <label for="password">Password Baru</label>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Ulangi" autocomplete="new-password" />
                        <label for="password_confirmation">Ulangi Password Baru</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </form>
    </div>
</div>
@endsection
