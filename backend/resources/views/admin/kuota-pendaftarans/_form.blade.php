<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" id="tahun_ajaran_id" required>
                <option value="">— Pilih Tahun Ajaran —</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', ($kuota->tahun_ajaran_id ?? ($tahunAktif->id ?? '')))==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <label for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
            @error('tahun_ajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="jalur_pendaftaran_id" class="form-select @error('jalur_pendaftaran_id') is-invalid @enderror" id="jalur_pendaftaran_id" required>
                <option value="">— Pilih Jalur —</option>
                @foreach($jalurs as $j)<option value="{{ $j->id }}" @selected(old('jalur_pendaftaran_id', ($kuota->jalur_pendaftaran_id ?? ''))==$j->id)>{{ $j->nama_jalur }}</option>@endforeach
            </select>
            <label for="jalur_pendaftaran_id">Jalur Pendaftaran <span class="text-danger">*</span></label>
            @error('jalur_pendaftaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="number" name="kuota" value="{{ old('kuota', $kuota->kuota ?? '') }}" class="form-control @error('kuota') is-invalid @enderror" id="kuota" min="0" placeholder="200" required />
            <label for="kuota">Kuota <span class="text-danger">*</span></label>
            @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="number" name="terisi" value="{{ old('terisi', $kuota->terisi ?? 0) }}" class="form-control @error('terisi') is-invalid @enderror" id="terisi" min="0" placeholder="0" />
            <label for="terisi">Terisi (koreksi manual, ≤ kuota)</label>
            @error('terisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" placeholder="Keterangan" style="height:80px;">{{ old('keterangan', $kuota->keterangan ?? '') }}</textarea>
            <label for="keterangan">Keterangan</label>
            @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
