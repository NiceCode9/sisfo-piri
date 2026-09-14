<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="kelas_id" class="form-select @error('kelas_id') is-invalid @enderror" id="kelas_id" required>
                <option value="">— Pilih Kelas —</option>
                @foreach($kelases as $k)<option value="{{ $k->id }}" @selected(old('kelas_id', ($rombel->kelas_id ?? ''))==$k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>@endforeach
            </select>
            <label for="kelas_id">Kelas <span class="text-danger">*</span></label>
            @error('kelas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" id="tahun_ajaran_id" required>
                <option value="">— Pilih Tahun Ajaran —</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', ($rombel->tahun_ajaran_id ?? ($tahunAktif->id ?? '')))==$t->id)>{{ $t->nama_tahun_ajaran }} {{ $t->status_aktif ? '(aktif)' : '' }}</option>@endforeach
            </select>
            <label for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
            @error('tahun_ajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <select name="wali_guru_id" class="form-select @error('wali_guru_id') is-invalid @enderror" id="wali_guru_id">
                <option value="">— Tanpa Wali —</option>
                @foreach($gurus as $g)<option value="{{ $g->id }}" @selected(old('wali_guru_id', ($rombel->wali_guru_id ?? ''))==$g->id)>{{ $g->nama }}</option>@endforeach
            </select>
            <label for="wali_guru_id">Wali Kelas</label>
            @error('wali_guru_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<p style="font-size:12px;color:var(--text-muted);">Satu kelas satu rombel per tahun ajaran. Wali tercatat di sini (bukan tabel terpisah).</p>
