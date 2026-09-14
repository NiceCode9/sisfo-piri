<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tipe" class="form-select @error('tipe') is-invalid @enderror" id="tipe" required>
                <option value="galeri" @selected(old('tipe', $galeri->tipe ?? 'galeri') === 'galeri')>Galeri (foto kegiatan)</option>
                <option value="prestasi" @selected(old('tipe', $galeri->tipe ?? '') === 'prestasi')>Prestasi</option>
            </select>
            <label for="tipe">Tipe <span class="text-danger">*</span></label>
            @error('tipe')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="title" value="{{ old('title', $galeri->title ?? '') }}" class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Judul" required />
            <label for="title">Judul <span class="text-danger">*</span></label>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <input type="text" name="desc" value="{{ old('desc', $galeri->desc ?? '') }}" class="form-control @error('desc') is-invalid @enderror" id="desc" placeholder="Deskripsi singkat" />
            <label for="desc">Deskripsi Singkat</label>
            @error('desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6" id="wrap-tanggal" style="display:none;">
        <div class="form-floating">
            <input type="date" name="tanggal" value="{{ old('tanggal', isset($galeri->tanggal) ? \Carbon\Carbon::parse($galeri->tanggal)->format('Y-m-d') : '') }}" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" />
            <label for="tanggal">Tanggal Prestasi</label>
            @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="order" value="{{ old('order', $galeri->order ?? 0) }}" class="form-control @error('order') is-invalid @enderror" id="order" min="0" placeholder="0" />
            <label for="order">Urutan</label>
            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-check mt-4">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', ($galeri->is_active ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_active">Aktif (tampil di landing)</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label" for="image">Gambar (JPG/PNG/WebP maks 5MB) — wajib untuk tipe galeri</label>
        @if (! empty($galeri->image_path ?? null))
            <div class="mb-2"><img src="{{ Storage::disk('public')->url($galeri->image_path) }}" alt="{{ $galeri->title }}" style="max-height:160px;border-radius:8px;" /></div>
        @endif
        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image') is-invalid @enderror" />
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tipe = document.getElementById('tipe');
    const wrapTanggal = document.getElementById('wrap-tanggal');
    if (!tipe || !wrapTanggal) return;
    function toggle() {
        wrapTanggal.style.display = tipe.value === 'prestasi' ? '' : 'none';
    }
    tipe.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
