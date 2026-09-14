@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Peran')
@section('breadcrumb', 'Ubah Peran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Peran</h1>
        <p class="page-subtitle mb-0">{{ $role->name }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-nexus-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Data Peran</h5>
            <p class="card-subtitle">Perbarui nama atau permission peran</p>
        </div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.roles._form', ['role' => $role, 'permissions' => $permissions])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
