{{-- $calon (nullable), $jalurs, $tahunAjarans, $tahunAktif, $isEdit --}}
{{-- Section A: Jalur & Tahun --}}
<div class="d-flex align-items-center gap-2 mb-3 mt-1">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:var(--accent-primary);color:#fff;"><i class="fa-solid fa-route"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Jalur & Tahun Ajaran</div>
        <div style="font-size:12px;color:var(--text-muted);">Pilih jalur pendaftaran dan tahun ajaran (kosongkan = otomatis aktif)</div>
    </div>
</div>
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

<hr class="my-4" style="border-color: var(--border-color);" />

{{-- Section B: Data Pribadi --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#4f46e5;color:#fff;"><i class="fa-solid fa-user"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Data Pribadi Siswa</div>
        <div style="font-size:12px;color:var(--text-muted);">Identitas calon siswa sesuai dokumen resmi</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $calon->nama_lengkap ?? '') }}" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" placeholder="Nama Lengkap" required />
            <label for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
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
            <label for="nik">NIK (16 digit) <span class="text-danger">*</span></label>
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
            <label for="tempat_lahir">Tempat Lahir <span class="text-danger">*</span></label>
            @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', isset($calon->tanggal_lahir) ? \Illuminate\Support\Carbon::parse($calon->tanggal_lahir)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_lahir') is-invalid @enderror" id="tanggal_lahir" required />
            <label for="tanggal_lahir">Tanggal Lahir <span class="text-danger">*</span></label>
            @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <label class="form-label" for="agama">Agama <span class="text-danger">*</span></label>
        <select name="agama" id="agama" class="form-select @error('agama') is-invalid @enderror" required>
            <option value="">— Pilih Agama —</option>
            @foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Khonghucu'] as $ag)
                <option value="{{ $ag }}" @selected(old('agama', $calon->agama ?? '') === $ag)>{{ $ag }}</option>
            @endforeach
        </select>
        @error('agama')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
            <input type="tel" name="no_hp" value="{{ old('no_hp', $calon->no_hp ?? '') }}" class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" placeholder="No HP" />
            <label for="no_hp">No. HP Siswa</label>
            @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4" style="border-color: var(--border-color);" />

{{-- Section C: Kontak & Alamat --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#06b6d4;color:#fff;"><i class="fa-solid fa-envelope"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Kontak & Alamat</div>
        <div style="font-size:12px;color:var(--text-muted);">Alamat domisili dan kontak yang dapat dihubungi</div>
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
            <textarea name="alamat" id="alamat" rows="3" class="form-control @error('alamat') is-invalid @enderror" placeholder="Alamat" style="height: 80px; min-height:80px;" required>{{ old('alamat', $calon->alamat ?? '') }}</textarea>
            <label for="alamat">Alamat Lengkap <span class="text-danger">*</span></label>
            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4" style="border-color: var(--border-color);" />

{{-- Section D: Orang Tua --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#f59e0b;color:#fff;"><i class="fa-solid fa-users"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Data Orang Tua</div>
        <div style="font-size:12px;color:var(--text-muted);">Informasi wali yang dapat dihubungi</div>
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
            <input type="tel" name="no_hp_orang_tua" value="{{ old('no_hp_orang_tua', $calon->no_hp_orang_tua ?? '') }}" class="form-control @error('no_hp_orang_tua') is-invalid @enderror" id="no_hp_orang_tua" placeholder="No HP Orang Tua" />
            <label for="no_hp_orang_tua">No HP Orang Tua</label>
            @error('no_hp_orang_tua')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4" style="border-color: var(--border-color);" />

{{-- Section E: Berkas --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#10b981;color:#fff;"><i class="fa-solid fa-cloud-arrow-up"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Berkas Persyaratan</div>
        <div style="font-size:12px;color:var(--text-muted);">Upload berkas (admin nullable, kosongkan bila tidak diubah). PDF 5MB, Foto JPG/PNG 2MB.</div>
    </div>
</div>

@php
    $berkas = $calon->berkasCalonSiswa ?? null;
    $berkasFields = [
        'ijazah_path' => ['label' => 'Ijazah (PDF)', 'accept' => '.pdf', 'icon' => 'fa-file-pdf', 'hint' => 'PDF maksimal 5MB'],
        'kk_path' => ['label' => 'Kartu Keluarga (PDF)', 'accept' => '.pdf', 'icon' => 'fa-file-pdf', 'hint' => 'PDF maksimal 5MB'],
        'akta_path' => ['label' => 'Akta Kelahiran (PDF)', 'accept' => '.pdf', 'icon' => 'fa-file-pdf', 'hint' => 'PDF maksimal 5MB'],
        'foto_path' => ['label' => 'Pas Foto (JPG/PNG)', 'accept' => 'image/*', 'icon' => 'fa-image', 'hint' => 'JPG/PNG maksimal 2MB'],
        'skl_path' => ['label' => 'SKL (PDF)', 'accept' => '.pdf', 'icon' => 'fa-file-pdf', 'hint' => 'PDF maksimal 5MB'],
    ];
@endphp

<div class="row g-3">
    @foreach ($berkasFields as $field => $meta)
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $field }}">{{ $meta['label'] }}</label>
            @if ($berkas && $berkas->$field)
                <div class="mb-1" style="font-size: 12px;">
                    Saat ini: <a href="{{ Storage::disk('public')->url($berkas->$field) }}" target="_blank" class="text-primary"><i class="fa-solid fa-eye"></i> Lihat file</a>
                    <span style="color: var(--text-muted);">— ganti file di bawah bila perlu</span>
                </div>
            @endif
            <div class="position-relative border border-2 border-dashed rounded-3 p-4 text-center bg-white @error($field) border-danger @enderror" style="border-color: var(--border-color) !important;">
                <div class="mb-2"><i class="fa-solid {{ $meta['icon'] }} fa-2x" style="color: var(--text-muted);"></i></div>
                <div style="font-size: 13px; color: var(--text-secondary);"><strong>Klik untuk upload</strong> atau drag & drop</div>
                <div style="font-size: 11px; color: var(--text-muted);">{{ $meta['hint'] }}</div>
                <input type="file" name="{{ $field }}" id="{{ $field }}" accept="{{ $meta['accept'] }}" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 @error($field) is-invalid @enderror" style="cursor: pointer;" />
            </div>
            @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text file-name" data-for="{{ $field }}" style="font-size: 12px; color: var(--accent-primary); display:none;"></div>
        </div>
    @endforeach
</div>

<hr class="my-4" style="border-color: var(--border-color);" />

{{-- Section F: Sertifikat Prestasi --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px;height:36px;background:#f59e0b;color:#fff;"><i class="fa-solid fa-medal"></i></div>
    <div>
        <div style="font-weight:700;font-size:14px;color:var(--text-primary);">Sertifikat Prestasi</div>
        <div style="font-size:12px;color:var(--text-muted);">Wajib bila jalur ber-flag sertifikat. Maks 5 file, PDF/JPG maks 5MB per file.</div>
    </div>
</div>

@php $existingSertifikat = $calon->sertifikatPrestasis ?? collect(); @endphp
@if ($existingSertifikat->isNotEmpty())
    <div class="table-responsive mb-3">
        <table class="table-nexus w-100" style="font-size: 13px;">
            <thead><tr><th>Nama Kejuaraan</th><th>File</th><th></th></tr></thead>
            <tbody>
                @foreach ($existingSertifikat as $s)
                    <tr>
                        <td>{{ $s->nama_sertifikat }}</td>
                        <td><a href="{{ Storage::disk('public')->url($s->file_path) }}" target="_blank" class="text-primary"><i class="fa-solid fa-eye"></i> Lihat</a></td>
                        <td>
                            <form action="{{ route('admin.calon-siswas.sertifikat.destroy', $s) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus sertifikat {{ $s->nama_sertifikat }}?"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div id="sertifikat-rows" class="d-flex flex-column gap-2">
    <div class="sertifikat-row row g-2">
        <div class="col-12 col-md-6">
            <input type="text" name="sertifikat[0][nama]" class="form-control" placeholder="Nama kejuaraan (cth: Juara 1 Pencak Silat Provinsi 2025)" />
        </div>
        <div class="col-12 col-md-5">
            <input type="file" name="sertifikat[0][file]" accept=".pdf,.jpg,.jpeg,.png" class="form-control" />
        </div>
        <div class="col-12 col-md-1">
            <button type="button" class="sertifikat-remove btn btn-nexus-outline btn-sm w-100" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button>
        </div>
    </div>
</div>
<button type="button" id="sertifikat-add" class="btn btn-nexus-outline btn-sm mt-2"><i class="fa-solid fa-plus"></i> Tambah Sertifikat (maks 5)</button>
@error('sertifikat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

@push('scripts')
<script>
(function () {
    const rows = document.getElementById('sertifikat-rows');
    const addBtn = document.getElementById('sertifikat-add');
    if (!rows || !addBtn) return;
    let idx = rows.querySelectorAll('.sertifikat-row').length;

    function refresh() {
        const count = rows.querySelectorAll('.sertifikat-row').length;
        rows.querySelectorAll('.sertifikat-remove').forEach((btn) => {
            btn.style.display = count > 1 ? '' : 'none';
        });
        addBtn.disabled = count >= 5;
    }

    addBtn.addEventListener('click', () => {
        if (rows.querySelectorAll('.sertifikat-row').length >= 5) return;
        const i = idx++;
        const div = document.createElement('div');
        div.className = 'sertifikat-row row g-2';
        div.innerHTML =
            `<div class="col-12 col-md-6"><input type="text" name="sertifikat[${i}][nama]" class="form-control" placeholder="Nama kejuaraan" /></div>` +
            `<div class="col-12 col-md-5"><input type="file" name="sertifikat[${i}][file]" accept=".pdf,.jpg,.jpeg,.png" class="form-control" /></div>` +
            `<div class="col-12 col-md-1"><button type="button" class="sertifikat-remove btn btn-nexus-outline btn-sm w-100" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button></div>`;
        rows.appendChild(div);
        refresh();
    });

    rows.addEventListener('click', (e) => {
        const btn = e.target.closest('.sertifikat-remove');
        if (!btn || rows.querySelectorAll('.sertifikat-row').length <= 1) return;
        btn.closest('.sertifikat-row').remove();
        refresh();
    });

    refresh();
})();
document.querySelectorAll('input[type="file"][name$="_path"]').forEach(input => {
    input.addEventListener('change', function() {
        const nameEl = document.querySelector('.file-name[data-for="'+this.name+'"]');
        if (this.files && this.files[0]) {
            nameEl.textContent = 'File terpilih: ' + this.files[0].name;
            nameEl.style.display = 'block';
            this.closest('.border-dashed').style.borderColor = 'var(--accent-primary)';
        } else {
            nameEl.style.display = 'none';
        }
    });
});
</script>
@endpush
