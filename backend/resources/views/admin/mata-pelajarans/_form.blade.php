<div class="row g-3 mb-3">
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="kode" value="{{ old('kode', $mapel->kode ?? '') }}" class="form-control @error('kode') is-invalid @enderror" id="kode" placeholder="MTK" required />
            <label for="kode">Kode <span class="text-danger">*</span></label>
            @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-9">
        <div class="form-floating">
            <input type="text" name="nama" value="{{ old('nama', $mapel->nama ?? '') }}" class="form-control @error('nama') is-invalid @enderror" id="nama" placeholder="Matematika" required />
            <label for="nama">Nama Mapel <span class="text-danger">*</span></label>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="kelompok" class="form-select @error('kelompok') is-invalid @enderror" id="kelompok" required>
                @foreach (['A', 'B', 'C'] as $k)
                    <option value="{{ $k }}" @selected(old('kelompok', $mapel->kelompok ?? 'A')===$k)>Kelompok {{ $k }}</option>
                @endforeach
            </select>
            <label for="kelompok">Kelompok <span class="text-danger">*</span></label>
            @error('kelompok')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" name="kkm" value="{{ old('kkm', $mapel->kkm ?? 75) }}" class="form-control @error('kkm') is-invalid @enderror" id="kkm" min="0" max="100" required />
            <label for="kkm">KKM <span class="text-danger">*</span></label>
            @error('kkm')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($mapel->is_aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
</div>
