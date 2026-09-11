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
    <div class="col-12">
        <div class="form-floating">
            <select name="rombel_id" class="form-select @error('rombel_id') is-invalid @enderror" id="rombel_id" required>
                <option value="">— Pilih Rombel (Kelas — Tahun Ajaran) —</option>
                @foreach($rombels as $r)<option value="{{ $r->id }}" @selected(old('rombel_id', ($pengampu->rombel_id ?? ''))==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}{{ $r->waliGuru ? ' (wali: '.$r->waliGuru->nama.')' : '' }}</option>@endforeach
            </select>
            <label for="rombel_id">Rombel <span class="text-danger">*</span></label>
            @error('rombel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<p style="font-size:12px;color:var(--text-muted);">Satu mapel hanya diampu satu guru per rombel. Tahun baru = rombel baru, histori lama utuh.</p>
