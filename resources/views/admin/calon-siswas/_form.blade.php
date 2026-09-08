{{-- $calon (nullable), $jalurs, $tahunAjarans, $tahunAktif, $isEdit --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="jalur_pendaftaran_id">Jalur Pendaftaran <span class="text-danger">*</span></label>
        <select name="jalur_pendaftaran_id" id="jalur_pendaftaran_id" class="form-select @error('jalur_pendaftaran_id') is-invalid @enderror" required>
            <option value="">— Pilih Jalur —</option>
            @foreach ($jalurs as $j)
                <option value="{{ $j->id }}" @selected((string) old('jalur_pendaftaran_id', $calon->jalur_pendaftaran_id ?? '') === (string) $j->id)>{{ $j->nama_jalur }}</option>
            @endforeach
        </select>
        @error('jalur_pendaftaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-6">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran</label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror">
            <option value="">Otomatis (aktif: {{ $tahunAktif->nama_tahun_ajaran ?? '-' }})</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $calon->tahun_ajaran_id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $calon->nama_lengkap ?? '') }}" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" placeholder="Nama Lengkap" required />
            <label for="nama_lengkap">Nama Lengkap</label>
            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
        <select name="jenis_kelamin" id="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
            <option value="">— Pilih —</option>
            <option value="L" @selected(old('jenis_kelamin', $calon->jenis_kelamin ?? '') === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin', $calon->jenis_kelamin ?? '') === 'P')>Perempuan</option>
        </select>
        @error('jenis_kelamin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="nik" value="{{ old('nik', $calon->nik ?? '') }}" maxlength="16" class="form-control @error('nik') is-invalid @enderror" id="nik" placeholder="NIK" required />
            <label for="nik">NIK (16 digit)</label>
            @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="nisn" value="{{ old('nisn', $calon->nisn ?? '') }}" maxlength="10" class="form-control @error('nisn') is-invalid @enderror" id="nisn" placeholder="NISN" />
            <label for="nisn">NISN (10 digit, opsional)</label>
            @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $calon->tempat_lahir ?? '') }}" class="form-control @error('tempat_lahir') is-invalid @enderror" id="tempat_lahir" placeholder="Tempat Lahir" required />
            <label for="tempat_lahir">Tempat Lahir</label>
            @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', isset($calon->tanggal_lahir) ? \Illuminate\Support\Carbon::parse($calon->tanggal_lahir)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_lahir') is-invalid @enderror" id="tanggal_lahir" required />
            <label for="tanggal_lahir">Tanggal Lahir</label>
            @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="agama" value="{{ old('agama', $calon->agama ?? '') }}" class="form-control @error('agama') is-invalid @enderror" id="agama" placeholder="Agama" required />
            <label for="agama">Agama</label>
            @error('agama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $calon->asal_sekolah ?? '') }}" class="form-control @error('asal_sekolah') is-invalid @enderror" id="asal_sekolah" placeholder="Asal Sekolah" />
            <label for="asal_sekolah">Asal Sekolah</label>
            @error('asal_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="no_hp" value="{{ old('no_hp', $calon->no_hp ?? '') }}" class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" placeholder="No HP" />
            <label for="no_hp">No. HP Siswa</label>
            @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="email" name="email" value="{{ old('email', $calon->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="Email" />
            <label for="email">Email (opsional)</label>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <textarea name="alamat" id="alamat" rows="2" class="form-control @error('alamat') is-invalid @enderror" placeholder="Alamat" style="height: 58px" required>{{ old('alamat', $calon->alamat ?? '') }}</textarea>
            <label for="alamat">Alamat</label>
            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $calon->nama_ayah ?? '') }}" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah" placeholder="Nama Ayah" />
            <label for="nama_ayah">Nama Ayah</label>
            @error('nama_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $calon->pekerjaan_ayah ?? '') }}" class="form-control @error('pekerjaan_ayah') is-invalid @enderror" id="pekerjaan_ayah" placeholder="Pekerjaan Ayah" />
            <label for="pekerjaan_ayah">Pekerjaan Ayah</label>
            @error('pekerjaan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $calon->nama_ibu ?? '') }}" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu" placeholder="Nama Ibu" />
            <label for="nama_ibu">Nama Ibu</label>
            @error('nama_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $calon->pekerjaan_ibu ?? '') }}" class="form-control @error('pekerjaan_ibu') is-invalid @enderror" id="pekerjaan_ibu" placeholder="Pekerjaan Ibu" />
            <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
            @error('pekerjaan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="no_hp_orang_tua" value="{{ old('no_hp_orang_tua', $calon->no_hp_orang_tua ?? '') }}" class="form-control @error('no_hp_orang_tua') is-invalid @enderror" id="no_hp_orang_tua" placeholder="No HP Orang Tua" />
            <label for="no_hp_orang_tua">No HP Orang Tua</label>
            @error('no_hp_orang_tua')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
