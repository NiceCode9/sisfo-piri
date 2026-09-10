<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="guru_id" class="form-select @error('guru_id') is-invalid @enderror" id="guru_id" required>
                <option value="">— Pilih Guru —</option>
                @foreach($gurus as $g)<option value="{{ $g->id }}" @selected(old('guru_id', ($pengampu->guru_id ?? ''))==$g->id)>{{ $g->nama }}</option>@endforeach
            </select>
            <label for="guru_id">Guru <span class="text-danger">*</span></label>
            @error('guru_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="mata_pelajaran_id" class="form-select @error('mata_pelajaran_id') is-invalid @enderror" id="mata_pelajaran_id" required>
                <option value="">— Pilih Mapel —</option>
                @foreach($mapels as $m)<option value="{{ $m->id }}" @selected(old('mata_pelajaran_id', ($pengampu->mata_pelajaran_id ?? ''))==$m->id)>{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
            </select>
            <label for="mata_pelajaran_id">Mata Pelajaran <span class="text-danger">*</span></label>
            @error('mata_pelajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="kelas_id" class="form-select @error('kelas_id') is-invalid @enderror" id="kelas_id" required>
                <option value="">— Pilih Kelas —</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected(old('kelas_id', ($pengampu->kelas_id ?? ''))==$k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>@endforeach
            </select>
            <label for="kelas_id">Kelas <span class="text-danger">*</span></label>
            @error('kelas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" id="tahun_ajaran_id" required>
                <option value="">— Pilih Tahun Ajaran —</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', ($pengampu->tahun_ajaran_id ?? ($tahunAktif->id ?? '')))==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <label for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
            @error('tahun_ajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<p style="font-size:12px;color:var(--text-muted);">Satu mapel hanya diampu satu guru per kelas per tahun. Tahun baru = baris baru, histori lama utuh.</p>
