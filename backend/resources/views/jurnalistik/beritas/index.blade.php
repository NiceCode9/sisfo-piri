@extends('layouts.app')

@section('title', 'Jurnalistik — Berita')
@section('breadcrumb', 'Berita')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="page-title">Daftar Berita</h1>
    <a href="{{ route('jurnalistik.beritas.create') }}" class="btn btn-primary btn-sm">Tambah Berita</a>
</div>
@include('layouts.partials.alert')
<table class="table table-sm">
    <thead><tr><th>Judul</th><th>Status</th><th>Kategori</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach($beritas as $b)
        <tr>
            <td>{{ $b->judul }}</td>
            <td>{{ $b->status }}</td>
            <td>{{ $b->kategori?->nama }}</td>
            <td>
                <a href="{{ route('jurnalistik.beritas.edit', $b) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="POST" action="{{ route('jurnalistik.beritas.publish', $b) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Toggle</button></form>
                <form method="POST" action="{{ route('jurnalistik.beritas.destroy', $b) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')">Hapus</button></form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $beritas->links() }}
@endsection