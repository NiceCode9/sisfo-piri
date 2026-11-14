@extends('layouts.app')

@section('title', 'Jurnalistik — Kategori')
@section('breadcrumb', 'Kategori')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Kategori Berita</h1>
        <p class="page-subtitle mb-0">Kelola kategori untuk pengelompokan berita.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kategoriModal" onclick="openKategoriModal()">
        <i class="fa-solid fa-plus me-1"></i> Tambah Kategori
    </button>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Kategori</h5>
            <p class="card-subtitle">Total {{ $kategoris->total() }} kategori</p>
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
                <tbody id="kategoriTableBody">
                    @foreach($kategoris as $k)
                        <tr data-id="{{ $k->id }}">
                            <td class="fw-semibold">{{ $k->nama }}</td>
                            <td><code>{{ $k->slug }}</code></td>
                            <td>{{ $k->beritas_count }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editKategori({{ $k->id }}, '{{ addslashes($k->nama) }}', '{{ $k->slug }}', '{{ addslashes($k->deskripsi ?? '') }}')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteKategori({{ $k->id }})">
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
        {{ $kategoris->links() }}
    </div>
</div>

{{-- Modal Create/Edit --}}
<div class="modal fade" id="kategoriModal" tabindex="-1" aria-labelledby="kategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kategoriModalLabel">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="kategoriForm" onsubmit="submitKategori(event)">
                @csrf
                <input type="hidden" name="_method" id="kategoriMethod" value="POST">
                <input type="hidden" name="id" id="kategoriId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" id="kategoriNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (opsional)</label>
                        <input type="text" name="slug" id="kategoriSlug" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="kategoriDeskripsi" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="kategoriSubmitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const kategoriStoreUrl = '{{ route("jurnalistik.kategoris.store") }}';
const kategoriBaseUrl = '{{ url("jurnalistik/kategoris") }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function openKategoriModal() {
    document.getElementById('kategoriModalLabel').textContent = 'Tambah Kategori';
    document.getElementById('kategoriMethod').value = 'POST';
    document.getElementById('kategoriId').value = '';
    document.getElementById('kategoriNama').value = '';
    document.getElementById('kategoriSlug').value = '';
    document.getElementById('kategoriDeskripsi').value = '';
}

function editKategori(id, nama, slug, deskripsi) {
    document.getElementById('kategoriModalLabel').textContent = 'Edit Kategori';
    document.getElementById('kategoriMethod').value = 'PUT';
    document.getElementById('kategoriId').value = id;
    document.getElementById('kategoriNama').value = nama;
    document.getElementById('kategoriSlug').value = slug;
    document.getElementById('kategoriDeskripsi').value = deskripsi;
    new bootstrap.Modal(document.getElementById('kategoriModal')).show();
}

async function submitKategori(e) {
    e.preventDefault();
    const id = document.getElementById('kategoriId').value;
    const method = document.getElementById('kategoriMethod').value;
    const url = method === 'PUT' ? `${kategoriBaseUrl}/${id}` : kategoriStoreUrl;

    const formData = new FormData(document.getElementById('kategoriForm'));
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

async function deleteKategori(id) {
    if (!confirm('Hapus kategori ini?')) return;
    const res = await fetch(`${kategoriBaseUrl}/${id}`, {
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