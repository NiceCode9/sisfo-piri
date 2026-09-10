@extends('layouts.app')

@section('title', 'Nexus Admin — Profil Sekolah')
@section('breadcrumb', 'Profil Sekolah')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Profil Sekolah</h1>
        <p class="page-subtitle mb-0">Data tampil di landing page, halaman tentang, dan kontak</p>
    </div>
</div>

@include('layouts.partials.alert')

<form method="POST" action="{{ route('admin.profil-sekolah.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><div><h5 class="card-title">Identitas Sekolah</h5><p class="card-subtitle">Nama, NPSN, dan alamat</p></div></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $profil->nama_sekolah) }}" class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" placeholder="Nama Sekolah" required />
                        <label for="nama_sekolah">Nama Sekolah <span class="text-danger">*</span></label>
                        @error('nama_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="npsn" value="{{ old('npsn', $profil->npsn) }}" class="form-control @error('npsn') is-invalid @enderror" id="npsn" placeholder="NPSN" />
                        <label for="npsn">NPSN</label>
                        @error('npsn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" id="alamat" placeholder="Alamat" style="height:80px;">{{ old('alamat', $profil->alamat) }}</textarea>
                        <label for="alamat">Alamat Lengkap</label>
                        @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><div><h5 class="card-title">Kontak</h5><p class="card-subtitle">Tampil di navbar, footer, dan halaman kontak</p></div></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="text" name="telp" value="{{ old('telp', $profil->telp) }}" class="form-control @error('telp') is-invalid @enderror" id="telp" placeholder="Telepon" />
                        <label for="telp">No. Telepon</label>
                        @error('telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="email" name="email" value="{{ old('email', $profil->email) }}" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="Email" />
                        <label for="email">Email</label>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="url" name="website" value="{{ old('website', $profil->website) }}" class="form-control @error('website') is-invalid @enderror" id="website" placeholder="https://" />
                        <label for="website">Website (opsional)</label>
                        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="maps_embed_url">URL Embed Google Maps</label>
                    <input type="text" name="maps_embed_url" value="{{ old('maps_embed_url', $profil->maps_embed_url) }}" class="form-control @error('maps_embed_url') is-invalid @enderror" id="maps_embed_url" placeholder="https://www.google.com/maps/embed?..." />
                    <div class="form-text">Paste URL <em>src</em> iframe dari tombol Share Google Maps. Harus diawali <code>https://www.google.com/maps/embed</code>.</div>
                    @error('maps_embed_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><div><h5 class="card-title">Profil & Citra</h5><p class="card-subtitle">Tahun berdiri, akreditasi, logo, dan foto</p></div></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <input type="number" name="tahun_berdiri" value="{{ old('tahun_berdiri', $profil->tahun_berdiri) }}" class="form-control @error('tahun_berdiri') is-invalid @enderror" id="tahun_berdiri" placeholder="2000" min="1900" max="{{ date('Y') }}" />
                        <label for="tahun_berdiri">Tahun Berdiri</label>
                        @error('tahun_berdiri')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="form-floating">
                        <select name="akreditasi" class="form-select @error('akreditasi') is-invalid @enderror" id="akreditasi">
                            <option value="">— Pilih —</option>
                            @foreach (['A', 'B', 'C'] as $akr)
                                <option value="{{ $akr }}" @selected(old('akreditasi', $profil->akreditasi) === $akr)>{{ $akr }}</option>
                            @endforeach
                        </select>
                        <label for="akreditasi">Akreditasi</label>
                        @error('akreditasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                @foreach (['logo' => ['Logo Sekolah', 'logo_path', 'PNG/JPG/SVG maks 2MB'], 'foto_gedung' => ['Foto Gedung', 'foto_gedung_path', 'JPG/PNG maks 5MB'], 'foto_kepala' => ['Foto Kepala Sekolah', 'foto_kepala_path', 'JPG/PNG maks 5MB']] as $input => [$label, $col, $hint])
                    <div class="col-12 col-sm-4">
                        <label class="form-label" for="{{ $input }}">{{ $label }}</label>
                        @if ($profil->$col)
                            <div class="mb-2"><img src="{{ Storage::disk('public')->url($profil->$col) }}" alt="{{ $label }}" style="max-height:80px;border-radius:8px;" /></div>
                        @endif
                        <input type="file" name="{{ $input }}" id="{{ $input }}" accept="image/*" class="form-control @error($input) is-invalid @enderror" />
                        <div class="form-text">{{ $hint }}</div>
                        @error($input)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><div><h5 class="card-title">Sambutan, Visi & Misi</h5><p class="card-subtitle">Tampil di halaman tentang kami</p></div></div>
        <div class="card-body-nexus">
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="sambutan" class="form-control @error('sambutan') is-invalid @enderror" id="sambutan" placeholder="Sambutan" style="height:100px;">{{ old('sambutan', $profil->sambutan) }}</textarea>
                        <label for="sambutan">Sambutan Kepala Sekolah</label>
                        @error('sambutan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="visi" class="form-control @error('visi') is-invalid @enderror" id="visi" placeholder="Visi" style="height:80px;">{{ old('visi', $profil->visi) }}</textarea>
                        <label for="visi">Visi</label>
                        @error('visi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Misi</label>
                    <div id="misi-rows" class="d-flex flex-column gap-2">
                        @forelse (old('misi', $profil->misi ?? []) as $misi)
                            <div class="misi-row d-flex gap-2">
                                <input type="text" name="misi[]" value="{{ $misi }}" class="form-control" placeholder="Butir misi" />
                                <button type="button" class="misi-remove btn btn-nexus-outline btn-sm" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        @empty
                            <div class="misi-row d-flex gap-2">
                                <input type="text" name="misi[]" value="" class="form-control" placeholder="Butir misi" />
                                <button type="button" class="misi-remove btn btn-nexus-outline btn-sm" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" id="misi-add" class="btn btn-nexus-outline btn-sm mt-2"><i class="fa-solid fa-plus"></i> Tambah Misi</button>
                    @error('misi')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="nama_kepala" value="{{ old('nama_kepala', $profil->nama_kepala) }}" class="form-control @error('nama_kepala') is-invalid @enderror" id="nama_kepala" placeholder="Kepala Sekolah" />
                        <label for="nama_kepala">Nama Kepala Sekolah</label>
                        @error('nama_kepala')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Profil</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const rows = document.getElementById('misi-rows');
    const addBtn = document.getElementById('misi-add');
    if (!rows || !addBtn) return;

    function refresh() {
        const count = rows.querySelectorAll('.misi-row').length;
        rows.querySelectorAll('.misi-remove').forEach((btn) => {
            btn.style.display = count > 1 ? '' : 'none';
        });
    }

    addBtn.addEventListener('click', () => {
        if (rows.querySelectorAll('.misi-row').length >= 20) return;
        const div = document.createElement('div');
        div.className = 'misi-row d-flex gap-2';
        div.innerHTML = '<input type="text" name="misi[]" value="" class="form-control" placeholder="Butir misi" />' +
            '<button type="button" class="misi-remove btn btn-nexus-outline btn-sm" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button>';
        rows.appendChild(div);
        refresh();
    });

    rows.addEventListener('click', (e) => {
        const btn = e.target.closest('.misi-remove');
        if (!btn || rows.querySelectorAll('.misi-row').length <= 1) return;
        btn.closest('.misi-row').remove();
        refresh();
    });

    refresh();
})();
</script>
@endpush
