<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_tahun_ajaran" value="{{ old('nama_tahun_ajaran', $tahunAjaran->nama_tahun_ajaran ?? '') }}" class="form-control @error('nama_tahun_ajaran') is-invalid @enderror" id="nama_tahun_ajaran" placeholder="2025/2026" required />
            <label for="nama_tahun_ajaran">Nama Tahun Ajaran <span class="text-danger">*</span></label>
            @error('nama_tahun_ajaran')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-check mt-4">
            <input type="hidden" name="status_aktif" value="0" />
            <input type="checkbox" name="status_aktif" value="1" id="status_aktif" class="form-check-input" @checked(old('status_aktif', ($tahunAjaran->status_aktif ?? false) ? '1' : '0')==='1') />
            <label class="form-check-label" for="status_aktif">Aktif (otomatis menonaktifkan tahun lain)</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', isset($tahunAjaran->tanggal_mulai) ? \Carbon\Carbon::parse($tahunAjaran->tanggal_mulai)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" id="tanggal_mulai" required />
            <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
            @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', isset($tahunAjaran->tanggal_selesai) ? \Carbon\Carbon::parse($tahunAjaran->tanggal_selesai)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_selesai') is-invalid @enderror" id="tanggal_selesai" required />
            <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
            @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
