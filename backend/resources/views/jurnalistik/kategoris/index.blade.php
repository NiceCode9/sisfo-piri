@extends('layouts.app')
@section('title', 'Jurnalistik — Kategori')
@section('breadcrumb', 'Kategori')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="page-title">Kategori Berita</h1>
    <a href="{{ route('jurnalistik.kategoris.create') }}" class="btn btn-primary btn-sm">Tambah</a>
</div>
@include('layouts.partials.alert')
<table class="table table-sm"><thead><tr><th>Nama</th><th>Slug</th><th>Berita</th><th>Aksi</th></tr></thead><tbody>
@foreach($kategoris as $k)
<tr><td>{{ $k->nama }}</td><td>{{ $k->slug }}</td><td>{{ $k->beritas_count }}</td>
<td><a href="{{ route('jurnalistik.kategoris.edit', $k) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
<form method="POST" action="{{ route('jurnalistik.kategoris.destroy', $k) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>
@endforeach
</tbody></table>
{{ $kategoris->links() }}
@endsection