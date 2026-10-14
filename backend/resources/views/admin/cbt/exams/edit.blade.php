@extends('layouts.app')

@section('title', 'Edit Ujian')
@section('breadcrumb', 'Edit Ujian')

@section('content')
<div class="page-header"><h1 class="page-title">Edit Ujian</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.exams.update', $exam) }}">
@csrf @method('PUT')
{{-- Partial yang sama dengan form Buat Ujian. Sebelumnya keduanya ditulis
     terpisah dan langsung berbeda: kolom `max_violation_count` dan
     `duration_minutes` tidak ada di form Edit padahal controller mewajibkannya,
     jadi Edit selalu gagal tersimpan, dan deskripsi ikut terhapus karena tidak
     pernah dikirim. --}}
@include('admin.cbt.exams._form')
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection