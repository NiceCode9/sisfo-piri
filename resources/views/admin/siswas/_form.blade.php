{{-- $siswa (nullable), $kelases, $tahunAjarans, $tahunAktif --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama" value="{{ old('nama', $siswa->user->name ?? $siswa->calonSiswa->nama_lengkap ?? '') }}" class="form-control @error('nama') is-invalid @enderror" id="nama" placeholder="Nama Lengkap" required />
            <label for="nama">Nama Lengkap <span class="text-danger">*</span></label>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size:11px;">Dipakai juga sebagai nama akun login.</div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="nisn" value="{{ old('nisn', $siswa->nisn ?? '') }}" maxlength="10" class="form-control @error('nisn') is-invalid @enderror" id="nisn" placeholder="NISN" required />
            <label for="nisn">NISN (10 digit) <span class="text-danger">*</span></label>
            @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size:11px;">NISN = username login, password awal = NISN.</div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="nis" value="{{ old('nis', $siswa->nis ?? '') }}" class="form-control @error('nis') is-invalid @enderror" id="nis" placeholder="NIS" />
            <label for="nis">NIS (opsional)</label>
            @error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran</label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror">
            <option value="">— Pilih Tahun —</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $siswa->tahun_ajaran_id ?? $tahunAktif->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label" for="kelas_id">Kelas</label>
        <select name="kelas_id" id="kelas_id" class="form-select @error('kelas_id') is-invalid @enderror">
            <option value="">— Pilih Kelas —</option>
            @foreach ($kelases as $k)
                <option value="{{ $k->id }}" @selected((string) old('kelas_id', $siswa->kelas_id ?? '') === (string) $k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>
            @endforeach
        </select>
        @error('kelas_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text" style="font-size:11px;">Ganti kelas otomatis mencatat riwayat.</div>
    </div>
    <div class="col-12 col-sm-2">
        <div class="form-floating">
            <input type="date" name="tanggal_diterima" value="{{ old('tanggal_diterima', isset($siswa->tanggal_diterima) ? \Carbon\Carbon::parse($siswa->tanggal_diterima)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_diterima') is-invalid @enderror" id="tanggal_diterima" />
            <label for="tanggal_diterima">Tgl Diterima</label>
            @error('tanggal_diterima')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-2">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($siswa->is_aktif ?? true) ? '1' : '0') === '1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
        @error('is_aktif')<div class="text-danger" style="font-size:12.5px;">{{ $message }}</div>@enderror
    </div>
</div>

<hr class="my-4" style="border-color: var(--border-color);" />

<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#f59e0b;color:#fff;"><i class="fa-solid fa-users"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Data Orang Tua</div>
        @if(!empty($siswa->calon_siswa_id ?? null))
            <div style="font-size:12px;color:var(--text-muted);">Siswa dari PPDB — data berikut read-only dari calon siswa.</div>
        @else
            <div style="font-size:12px;color:var(--text-muted);">Informasi wali yang dapat dihubungi.</div>
        @endif
    </div>
</div>

@php
    $dariCalon = !empty($siswa->calon_siswa_id ?? null);
    $ortu = [
        'nama_ayah' => $dariCalon ? ($siswa->calonSiswa->nama_ayah ?? '') : ($siswa->nama_ayah ?? ''),
        'pekerjaan_ayah' => $dariCalon ? ($siswa->calonSiswa->pekerjaan_ayah ?? '') : ($siswa->pekerjaan_ayah ?? ''),
        'nama_ibu' => $dariCalon ? ($siswa->calonSiswa->nama_ibu ?? '') : ($siswa->nama_ibu ?? ''),
        'pekerjaan_ibu' => $dariCalon ? ($siswa->calonSiswa->pekerjaan_ibu ?? '') : ($siswa->pekerjaan_ibu ?? ''),
        'no_hp_orang_tua' => $dariCalon ? ($siswa->calonSiswa->no_hp_orang_tua ?? '') : ($siswa->no_hp_orang_tua ?? ''),
    ];
@endphp

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $ortu['nama_ayah']) }}" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah" placeholder="Nama Ayah" @if($dariCalon) disabled @endif />
            <label for="nama_ayah">Nama Ayah</label>
            @error('nama_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $ortu['pekerjaan_ayah']) }}" class="form-control @error('pekerjaan_ayah') is-invalid @enderror" id="pekerjaan_ayah" placeholder="Pekerjaan Ayah" @if($dariCalon) disabled @endif />
            <label for="pekerjaan_ayah">Pekerjaan Ayah</label>
            @error('pekerjaan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $ortu['nama_ibu']) }}" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu" placeholder="Nama Ibu" @if($dariCalon) disabled @endif />
            <label for="nama_ibu">Nama Ibu</label>
            @error('nama_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $ortu['pekerjaan_ibu']) }}" class="form-control @error('pekerjaan_ibu') is-invalid @enderror" id="pekerjaan_ibu" placeholder="Pekerjaan Ibu" @if($dariCalon) disabled @endif />
            <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
            @error('pekerjaan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="tel" name="no_hp_orang_tua" value="{{ old('no_hp_orang_tua', $ortu['no_hp_orang_tua']) }}" class="form-control @error('no_hp_orang_tua') is-invalid @enderror" id="no_hp_orang_tua" placeholder="No HP Orang Tua" @if($dariCalon) disabled @endif />
            <label for="no_hp_orang_tua">No HP Orang Tua</label>
            @error('no_hp_orang_tua')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
