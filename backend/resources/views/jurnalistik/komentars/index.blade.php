@extends('layouts.app')
@section('title', 'Jurnalistik — Komentar')
@section('content')
<div class="page-header"><h1 class="page-title">Moderasi Komentar</h1></div>
@include('layouts.partials.alert')
<table class="table table-sm"><thead><tr><th>Berita</th><th>Nama</th><th>Isi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@foreach($komentars as $k)
<tr>
    <td>{{ $k->berita?->judul }}</td>
    <td>{{ $k->nama ?: $k->user?->name }}</td>
    <td>{{ \Illuminate\Support\Str::limit($k->isi, 80) }}</td>
    <td>{{ $k->status }}</td>
    <td>
        <form method="POST" action="{{ route('jurnalistik.komentars.approve', $k) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Setujui</button></form>
        <form method="POST" action="{{ route('jurnalistik.komentars.reject', $k) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">Tolak</button></form>
        <form method="POST" action="{{ route('jurnalistik.komentars.destroy', $k) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
    </td>
</tr>
@endforeach
</tbody></table>
{{ $komentars->links() }}
@endsection
