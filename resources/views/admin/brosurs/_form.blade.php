<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="title" value="{{ old('title', $brosur->title ?? '') }}" class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Brosur SPMB" required />
            <label for="title">Judul <span class="text-danger">*</span></label>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="order" value="{{ old('order', $brosur->order ?? 0) }}" class="form-control @error('order') is-invalid @enderror" id="order" min="0" placeholder="0" />
            <label for="order">Urutan</label>
            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-check mt-4">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', ($brosur->is_active ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_active">Aktif (tampil di landing)</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <input type="text" name="desc" value="{{ old('desc', $brosur->desc ?? '') }}" class="form-control @error('desc') is-invalid @enderror" id="desc" placeholder="Deskripsi singkat" />
            <label for="desc">Deskripsi Singkat</label>
            @error('desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12">
        <label class="form-label" for="image">Gambar (JPG/PNG/WebP maks 5MB)</label>
        @if (! empty($brosur->image_path ?? null))
            <div class="mb-2"><img src="{{ Storage::disk('public')->url($brosur->image_path) }}" alt="{{ $brosur->title }}" style="max-height:160px;border-radius:8px;" /></div>
        @endif
        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image') is-invalid @enderror" />
        @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
