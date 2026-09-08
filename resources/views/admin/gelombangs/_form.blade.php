{{-- $gelombang (nullable), $tahunAjarans, $tahunAktif --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" required>
            <option value="">— Pilih Tahun —</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $gelombang->tahun_ajaran_id ?? $tahunAktif->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="nama_gelombang" value="{{ old('nama_gelombang', $gelombang->nama_gelombang ?? '') }}" class="form-control @error('nama_gelombang') is-invalid @enderror" id="nama_gelombang" placeholder="Nama" required />
            <label for="nama_gelombang">Nama Gelombang <span class="text-danger">*</span></label>
            @error('nama_gelombang')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="nomor_urut" value="{{ old('nomor_urut', $gelombang->nomor_urut ?? 1) }}" class="form-control @error('nomor_urut') is-invalid @enderror" id="nomor_urut" min="1" required />
            <label for="nomor_urut">Nomor Urut</label>
            @error('nomor_urut')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="badge" value="{{ old('badge', $gelombang->badge ?? '') }}" class="form-control @error('badge') is-invalid @enderror" id="badge" placeholder="Badge" />
            <label for="badge">Badge (EARLY BIRD)</label>
            @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label" for="warna_border">Warna Border</label>
        <select name="warna_border" id="warna_border" class="form-select @error('warna_border') is-invalid @enderror">
            <option value="">— Default —</option>
            <option value="primary-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='primary-600')>Primary</option>
            <option value="secondary-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='secondary-600')>Secondary</option>
            <option value="accent-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='accent-600')>Accent</option>
        </select>
        @error('warna_border')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($gelombang->is_aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_buka" value="{{ old('tanggal_buka', isset($gelombang->tanggal_buka) ? \Carbon\Carbon::parse($gelombang->tanggal_buka)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_buka') is-invalid @enderror" id="tanggal_buka" required />
            <label for="tanggal_buka">Tgl Buka <span class="text-danger">*</span></label>
            @error('tanggal_buka')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_tutup" value="{{ old('tanggal_tutup', isset($gelombang->tanggal_tutup) ? \Carbon\Carbon::parse($gelombang->tanggal_tutup)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_tutup') is-invalid @enderror" id="tanggal_tutup" required />
            <label for="tanggal_tutup">Tgl Tutup <span class="text-danger">*</span></label>
            @error('tanggal_tutup')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_tes" value="{{ old('tanggal_tes', isset($gelombang->tanggal_tes) ? \Carbon\Carbon::parse($gelombang->tanggal_tes)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_tes') is-invalid @enderror" id="tanggal_tes" />
            <label for="tanggal_tes">Tgl Tes</label>
            @error('tanggal_tes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_pengumuman" value="{{ old('tanggal_pengumuman', isset($gelombang->tanggal_pengumuman) ? \Carbon\Carbon::parse($gelombang->tanggal_pengumuman)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_pengumuman') is-invalid @enderror" id="tanggal_pengumuman" />
            <label for="tanggal_pengumuman">Tgl Pengumuman</label>
            @error('tanggal_pengumuman')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="kuota" value="{{ old('kuota', $gelombang->kuota ?? 80) }}" class="form-control @error('kuota') is-invalid @enderror" id="kuota" min="1" required />
            <label for="kuota">Kuota <span class="text-danger">*</span></label>
            @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="terisi" value="{{ old('terisi', $gelombang->terisi ?? 0) }}" class="form-control @error('terisi') is-invalid @enderror" id="terisi" min="0" />
            <label for="terisi">Terisi</label>
            @error('terisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="diskon_persen" value="{{ old('diskon_persen', $gelombang->diskon_persen ?? '') }}" class="form-control @error('diskon_persen') is-invalid @enderror" id="diskon_persen" min="0" max="100" placeholder="Diskon" />
            <label for="diskon_persen">Diskon %</label>
            @error('diskon_persen')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <textarea name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" placeholder="Keterangan" style="height:58px">{{ old('keterangan', $gelombang->keterangan ?? '') }}</textarea>
            <label for="keterangan">Keterangan</label>
            @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Keuntungan (isi 1 per baris, akan disimpan sebagai array)</label>
    @php $keuntungan = old('keuntungan', $gelombang->keuntungan ?? []); if (is_string($keuntungan)) $keuntungan = json_decode($keuntungan, true) ?? []; @endphp
    <div class="row g-2">
        @for ($i = 0; $i < 3; $i++)
            <div class="col-12 col-sm-4">
                <input type="text" name="keuntungan[]" value="{{ $keuntungan[$i] ?? '' }}" class="form-control @error('keuntungan.'.$i) is-invalid @enderror" placeholder="Keuntungan {{ $i+1 }}" />
                @error('keuntungan.'.$i)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @endfor
    </div>
    @error('keuntungan')<div class="text-danger" style="font-size:12.5px;">{{ $message }}</div>@enderror
</div>
