<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="rombel_id" class="form-select @error('rombel_id') is-invalid @enderror" id="rombel_id" required>
                <option value="">— Pilih Rombel —</option>
                @foreach($rombels as $r)<option value="{{ $r->id }}" @selected(old('rombel_id', $materi->rombel_id ?? '')==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <label for="rombel_id">Rombel <span class="text-danger">*</span></label>
            @error('rombel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="mata_pelajaran_id" class="form-select @error('mata_pelajaran_id') is-invalid @enderror" id="mata_pelajaran_id" required>
                <option value="">— Pilih Mapel —</option>
                @foreach($mapels as $m)<option value="{{ $m->id }}" @selected(old('mata_pelajaran_id', $materi->mata_pelajaran_id ?? '')==$m->id)>{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
            </select>
            <label for="mata_pelajaran_id">Mapel <span class="text-danger">*</span></label>
            @error('mata_pelajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <input type="text" name="judul" value="{{ old('judul', $materi->judul ?? '') }}" class="form-control @error('judul') is-invalid @enderror" id="judul" required />
            <label for="judul">Judul <span class="text-danger">*</span></label>
            @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12">
        <div class="form-floating">
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" style="height:80px">{{ old('deskripsi', $materi->deskripsi ?? '') }}</textarea>
            <label for="deskripsi">Deskripsi</label>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="tipe" class="form-select @error('tipe') is-invalid @enderror" id="tipe" required>
                @foreach(['dokumen','video','link'] as $t)<option value="{{ $t }}" @selected(old('tipe', $materi->tipe ?? 'dokumen')===$t)>{{ ucfirst($t) }}</option>@endforeach
            </select>
            <label for="tipe">Tipe <span class="text-danger">*</span></label>
            @error('tipe')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" class="form-check-input" id="is_aktif" @checked(old('is_aktif', $materi->is_aktif ?? '1')==='1' || old('is_aktif', $materi->is_aktif ?? true)) />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <label class="form-label" style="font-size:12px;" for="file">File (dokumen/video)</label>
        <input type="file" name="file" id="file" class="form-control form-control-sm @error('file') is-invalid @enderror" />
        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if(!empty($materi->file_path))<div style="font-size:12px;color:var(--text-muted);">Saat ini: {{ $materi->file_path }}</div>@endif
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="url" name="url" value="{{ old('url', $materi->url ?? '') }}" class="form-control @error('url') is-invalid @enderror" id="url" placeholder="https://..." />
            <label for="url">URL (tipe link)</label>
            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
