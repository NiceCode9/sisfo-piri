@extends('layouts.app')

@section('title', 'Jurnalistik — Tag')
@section('breadcrumb', 'Tag')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tag Berita</h1>
        <p class="page-subtitle mb-0">Kelola tag untuk penandaan berita.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="openTagModal()">
        <i class="fa-solid fa-plus me-1"></i> Tambah Tag
    </button>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Tag</h5>
            <p class="card-subtitle">Total {{ $tags->total() }} tag</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Berita</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tagTableBody">
                    @foreach($tags as $t)
                        <tr data-id="{{ $t->id }}">
                            <td class="fw-semibold">{{ $t->nama }}</td>
                            <td><code>{{ $t->slug }}</code></td>
                            <td>{{ $t->beritas_count }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editTag({{ $t->id }}, '{{ addslashes($t->nama) }}', '{{ $t->slug }}')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTag({{ $t->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-body-nexus border-top">
        {{ $tags->links() }}
    </div>
</div>

{{-- Modal Create/Edit --}}
<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tagModalLabel">Tambah Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="tagForm" onsubmit="submitTag(event)">
                @csrf
                <input type="hidden" name="_method" id="tagMethod" value="POST">
                <input type="hidden" name="id" id="tagId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" id="tagName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (opsional)</label>
                        <input type="text" name="slug" id="tagSlug" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="tagSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const tagStoreUrl = '{{ route("jurnalistik.tags.store") }}';
const tagBaseUrl = '{{ url("jurnalistik/tags") }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function openTagModal() {
    document.getElementById('tagModalLabel').textContent = 'Tambah Tag';
    document.getElementById('tagMethod').value = 'POST';
    document.getElementById('tagId').value = '';
    document.getElementById('tagName').value = '';
    document.getElementById('tagSlug').value = '';
}

function editTag(id, nama, slug) {
    document.getElementById('tagModalLabel').textContent = 'Edit Tag';
    document.getElementById('tagMethod').value = 'PUT';
    document.getElementById('tagId').value = id;
    document.getElementById('tagName').value = nama;
    document.getElementById('tagSlug').value = slug;
    new bootstrap.Modal(document.getElementById('tagModal')).show();
}

async function submitTag(e) {
    e.preventDefault();
    const id = document.getElementById('tagId').value;
    const method = document.getElementById('tagMethod').value;
    const url = method === 'PUT' ? `${tagBaseUrl}/${id}` : tagStoreUrl;

    const formData = new FormData(document.getElementById('tagForm'));
    formData.delete('_method');
    if (method === 'PUT') formData.append('_method', 'PUT');

    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData,
    });
    const data = await res.json();
    if (data.success) {
        location.reload();
    } else {
        alert(data.message || 'Gagal menyimpan.');
    }
}

async function deleteTag(id) {
    if (!confirm('Hapus tag ini?')) return;
    const res = await fetch(`${tagBaseUrl}/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_method=DELETE',
    });
    const data = await res.json();
    if (data.success) {
        location.reload();
    } else {
        alert(data.message || 'Gagal menghapus.');
    }
}
</script>
@endpush
@endsection