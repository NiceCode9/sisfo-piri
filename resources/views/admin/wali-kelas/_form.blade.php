<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="kelas_id" class="form-select @error('kelas_id') is-invalid @enderror" id="kelas_id" required>
                <option value="">— Pilih Kelas —</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected(old('kelas_id', ($wali->kelas_id ?? ''))==$k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>@endforeach
            </select>
            <label for="kelas_id">Kelas <span class="text-danger">*</span></label>
            @error('kelas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="guru_id" class="form-select @error('guru_id') is-invalid @enderror" id="guru_id" required>
                <option value="">— Pilih Guru —</option>
                @foreach($gurus as $g)<option value="{{ $g->id }}" @selected(old('guru_id', ($wali->guru_id ?? ''))==$g->id)>{{ $g->nama }}</option>@endforeach
            </select>
            <label for="guru_id">Guru <span class="text-danger">*</span></label>
            @error('guru_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" id="tahun_ajaran_id" required>
                <option value="">— Pilih Tahun Ajaran —</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', ($wali->tahun_ajaran_id ?? ($tahunAktif->id ?? '')))==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <label for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
            @error('tahun_ajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<p style="font-size:12px;color:var(--text-muted);">Satu kelas satu wali per tahun. Tahun baru = penetapan baru.</p>
