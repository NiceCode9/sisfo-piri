<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas ?? '') }}" class="form-control @error('nama_kelas') is-invalid @enderror" id="nama_kelas" placeholder="7A" required />
            <label for="nama_kelas">Nama Kelas <span class="text-danger">*</span></label>
            @error('nama_kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tingkat" class="form-select @error('tingkat') is-invalid @enderror" id="tingkat" required>
                @foreach (['7', '8', '9'] as $t)
                    <option value="{{ $t }}" @selected(old('tingkat', $kelas->tingkat ?? '7')===$t)>Tingkat {{ $t }}</option>
                @endforeach
            </select>
            <label for="tingkat">Tingkat <span class="text-danger">*</span></label>
            @error('tingkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" placeholder="Deskripsi" style="height:80px;">{{ old('deskripsi', $kelas->deskripsi ?? '') }}</textarea>
            <label for="deskripsi">Deskripsi</label>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
