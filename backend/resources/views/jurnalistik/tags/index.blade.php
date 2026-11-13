@extends('layouts.app')
@section('title', 'Jurnalistik — Tag')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="page-title">Tag Berita</h1>
    <a href="{{ route('jurnalistik.tags.create') }}" class="btn btn-primary btn-sm">Tambah</a>
</div>
@include('layouts.partials.alert')
<table class="table table-sm"><thead><tr><th>Nama</th><th>Slug</th><th>Berita</th><th>Aksi</th></tr></thead><tbody>
@foreach($tags as $t)
<tr><td>{{ $t->nama }}</td><td>{{ $t->slug }}</td><td>{{ $t->beritas_count }}</td>
<td><a href="{{ route('jurnalistik.tags.edit', $t) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
<form method="POST" action="{{ route('jurnalistik.tags.destroy', $t) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr>
@endforeach
</tbody></table>
{{ $tags->links() }}
@endsection
