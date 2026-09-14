<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" id="tahun_ajaran_id" required>
                <option value="">— Pilih Tahun Ajaran —</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', ($jadwal->tahun_ajaran_id ?? ($tahunAktif->id ?? '')))==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <label for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
            @error('tahun_ajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_jadwal" value="{{ old('nama_jadwal', $jadwal->nama_jadwal ?? '') }}" class="form-control @error('nama_jadwal') is-invalid @enderror" id="nama_jadwal" placeholder="Pendaftaran" required />
            <label for="nama_jadwal">Nama Fase <span class="text-danger">*</span></label>
            @error('nama_jadwal')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', isset($jadwal->tanggal_mulai) ? \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" id="tanggal_mulai" required />
            <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
            @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', isset($jadwal->tanggal_selesai) ? \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_selesai') is-invalid @enderror" id="tanggal_selesai" required />
            <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
            @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" placeholder="Keterangan" style="height:80px;">{{ old('keterangan', $jadwal->keterangan ?? '') }}</textarea>
            <label for="keterangan">Keterangan</label>
            @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
