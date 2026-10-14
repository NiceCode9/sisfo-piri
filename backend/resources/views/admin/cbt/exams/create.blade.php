@extends('layouts.app')

@section('title', 'Buat Ujian')
@section('breadcrumb', 'Buat Ujian')

@section('content')
<div class="page-header"><h1 class="page-title">Buat Ujian</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
<form method="POST" action="{{ route('admin.cbt.exams.store') }}">
@csrf
@include('admin.cbt.exams._form')
<button type="submit" class="btn btn-primary btn-sm">Simpan</button>
</form>
</div></div>
@endsection