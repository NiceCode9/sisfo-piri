<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="jenis" list="jenis-list" value="{{ old('jenis', $fasilitas->jenis ?? '') }}" class="form-control @error('jenis') is-invalid @enderror" id="jenis" placeholder="Lab. IPA" required />
            <label for="jenis">Jenis Fasilitas <span class="text-danger">*</span></label>
            @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <datalist id="jenis-list">
            @foreach ($jenisList as $jenis)
                <option value="{{ $jenis }}">
            @endforeach
        </datalist>
    </div>
    <div class="col-12 col-sm-5">
        <div class="form-floating">
            <input type="text" name="nama" value="{{ old('nama', $fasilitas->nama ?? '') }}" class="form-control @error('nama') is-invalid @enderror" id="nama" placeholder="Lab IPA 1" required />
            <label for="nama">Nama Fasilitas <span class="text-danger">*</span></label>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-2">
        <div class="form-floating">
            <input type="number" name="urutan" value="{{ old('urutan', $fasilitas->urutan ?? 0) }}" class="form-control @error('urutan') is-invalid @enderror" id="urutan" min="0" placeholder="0" />
            <label for="urutan">Urutan</label>
            @error('urutan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-1 d-flex align-items-center">
        <div class="form-check">
            <input type="hidden" name="aktif" value="0" />
            <input type="checkbox" name="aktif" value="1" id="aktif" class="form-check-input" @checked(old('aktif', ($fasilitas->aktif ?? true) ? '1' : '0') === '1') />
            <label class="form-check-label" for="aktif">Aktif</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" placeholder="Deskripsi" style="height:90px">{{ old('deskripsi', $fasilitas->deskripsi ?? '') }}</textarea>
            <label for="deskripsi">Deskripsi</label>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <label class="form-label" for="fotos">Foto Fasilitas (bisa lebih dari satu, JPG/PNG/WebP maks 5MB)</label>
        @if (! empty($fasilitas->fotos))
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach ($fasilitas->fotos as $foto)
                    <div class="position-relative">
                        <img src="{{ Storage::disk('public')->url($foto) }}" alt="{{ $fasilitas->nama }}" style="height:80px;border-radius:6px;" />
                        <div class="form-check mt-1">
                            <input type="checkbox" name="hapus_foto[]" value="{{ $foto }}" id="hapus_{{ md5($foto) }}" class="form-check-input" />
                            <label class="form-check-label small" for="hapus_{{ md5($foto) }}">Hapus</label>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <input type="file" name="fotos[]" id="fotos" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror" multiple />
        @error('fotos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('fotos.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>