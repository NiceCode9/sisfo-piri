@extends('layouts.app')
@section('title', 'Jurnalistik — Media')
@section('content')
<div class="page-header"><h1 class="page-title">Media</h1></div>
@include('layouts.partials.alert')
<form method="POST" action="{{ route('jurnalistik.media.store') }}" enctype="multipart/form-data" class="mb-3 d-flex gap-2">
    @csrf
    <input type="file" name="file" class="form-control" accept="image/*" required>
    <button class="btn btn-primary">Unggah</button>
</form>
<table class="table table-sm"><thead><tr><th>File</th><th>URL</th><th></th></tr></thead><tbody>
@foreach($files as $f)
<tr>
    <td>{{ $f['name'] }}</td>
    <td><input class="form-control form-control-sm" value="{{ $f['url'] }}" readonly></td>
    <td><form method="POST" action="{{ route('jurnalistik.media.destroy') }}">@csrf @method('DELETE')<input type="hidden" name="path" value="{{ $f['path'] }}"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td>
</tr>
@endforeach
</tbody></table>
@endsection
