{{-- $pengumuman (nullable), $tahunAjarans, $tahunAktif --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" required>
            <option value="">— Pilih Tahun —</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $pengumuman->tahun_ajaran_id ?? $tahunAktif->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="date" name="tanggal_pengumuman" value="{{ old('tanggal_pengumuman', isset($pengumuman->tanggal_pengumuman) ? \Carbon\Carbon::parse($pengumuman->tanggal_pengumuman)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d')) }}" class="form-control @error('tanggal_pengumuman') is-invalid @enderror" id="tanggal_pengumuman" required />
            <label for="tanggal_pengumuman">Tanggal Pengumuman <span class="text-danger">*</span></label>
            @error('tanggal_pengumuman')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <input type="text" name="judul" value="{{ old('judul', $pengumuman->judul ?? '') }}" class="form-control @error('judul') is-invalid @enderror" id="judul" placeholder="Judul" required />
            <label for="judul">Judul Pengumuman <span class="text-danger">*</span></label>
            @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <label class="form-label" for="isi">Isi Pengumuman <span class="text-danger">*</span></label>
        <textarea name="isi" id="isi" rows="6" class="form-control @error('isi') is-invalid @enderror" placeholder="Isi pengumuman..." required>{{ old('isi', $pengumuman->isi ?? '') }}</textarea>
        @error('isi')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-check">
            <input type="hidden" name="status_aktif" value="0" />
            <input type="checkbox" name="status_aktif" value="1" id="status_aktif" class="form-check-input" @checked(old('status_aktif', ($pengumuman->status_aktif ?? true) ? '1' : '0') === '1') />
            <label class="form-check-label" for="status_aktif">Aktif (tampilkan di publik)</label>
        </div>
        @error('status_aktif')<div class="text-danger" style="font-size:12.5px;">{{ $message }}</div>@enderror
    </div>
</div>
