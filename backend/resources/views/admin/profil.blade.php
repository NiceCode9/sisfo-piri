@extends('layouts.app')

@section('title', 'Nexus Admin — Profil Saya')
@section('breadcrumb', 'Profil')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-subtitle mb-0">{{ auth()->user()->name }} — {{ auth()->user()->username }}</p>
    </div>
</div>

@include('layouts.partials.alert', ['errorDitampilkan' => ['password', 'password_saat_ini']])

<div class="card-nexus" style="max-width:560px;">
    <div class="card-header-nexus"><h5 class="card-title">Ubah Password</h5></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.profil.update') }}">
            @csrf @method('PUT')
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Nama" />
                        <label for="name">Nama</label>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="Email" />
                        <label for="email">Email</label>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
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
                        <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Ulangi password" autocomplete="new-password" />
                        <label for="password_confirmation">Ulangi Password</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </form>
    </div>
</div>
@endsection