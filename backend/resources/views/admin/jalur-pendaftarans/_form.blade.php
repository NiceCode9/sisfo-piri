<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_jalur" value="{{ old('nama_jalur', $jalur->nama_jalur ?? '') }}" class="form-control @error('nama_jalur') is-invalid @enderror" id="nama_jalur" placeholder="Zonasi" required />
            <label for="nama_jalur">Nama Jalur <span class="text-danger">*</span></label>
            @error('nama_jalur')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-check mt-4">
            <input type="hidden" name="aktif" value="0" />
            <input type="checkbox" name="aktif" value="1" id="aktif" class="form-check-input" @checked(old('aktif', ($jalur->aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="aktif">Aktif (tampil di pendaftaran publik)</label>
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-check mt-4">
            <input type="hidden" name="wajib_sertifikat" value="0" />
            <input type="checkbox" name="wajib_sertifikat" value="1" id="wajib_sertifikat" class="form-check-input" @checked(old('wajib_sertifikat', ($jalur->wajib_sertifikat ?? false) ? '1' : '0')==='1') />
            <label class="form-check-label" for="wajib_sertifikat">Wajib sertifikat prestasi</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" placeholder="Deskripsi" style="height:80px;">{{ old('deskripsi', $jalur->deskripsi ?? '') }}</textarea>
            <label for="deskripsi">Deskripsi</label>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
