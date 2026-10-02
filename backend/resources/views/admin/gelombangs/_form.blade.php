{{-- $gelombang (nullable), $tahunAjarans, $tahunAktif --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" required>
            <option value="">— Pilih Tahun —</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $gelombang->tahun_ajaran_id ?? $tahunAktif->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="nama_gelombang" value="{{ old('nama_gelombang', $gelombang->nama_gelombang ?? '') }}" class="form-control @error('nama_gelombang') is-invalid @enderror" id="nama_gelombang" placeholder="Nama" required />
            <label for="nama_gelombang">Nama Gelombang <span class="text-danger">*</span></label>
            @error('nama_gelombang')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="nomor_urut" value="{{ old('nomor_urut', $gelombang->nomor_urut ?? 1) }}" class="form-control @error('nomor_urut') is-invalid @enderror" id="nomor_urut" min="1" required />
            <label for="nomor_urut">Nomor Urut</label>
            @error('nomor_urut')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="badge" value="{{ old('badge', $gelombang->badge ?? '') }}" class="form-control @error('badge') is-invalid @enderror" id="badge" placeholder="Badge" />
            <label for="badge">Badge (EARLY BIRD)</label>
            @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label" for="warna_border">Warna Border</label>
        <select name="warna_border" id="warna_border" class="form-select @error('warna_border') is-invalid @enderror">
            <option value="">— Default —</option>
            <option value="primary-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='primary-600')>Primary</option>
            <option value="secondary-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='secondary-600')>Secondary</option>
            <option value="accent-600" @selected(old('warna_border', $gelombang->warna_border ?? '')==='accent-600')>Accent</option>
        </select>
        @error('warna_border')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($gelombang->is_aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
</div>

{{-- ============ TAHAPAN (satu baris per tahap) ============ --}}
@php
    // Baris tahap yang akan dirender. Prioritas: input yang gagal validasi
    // (old), lalu tahap yang tersimpan, lalu satu baris default `pendaftaran`
    // supaya admin tidak pernah menyimpan gelombang tanpa jadwal.
    $tahapan = old('tahapan');
    if (! is_array($tahapan) || $tahapan === []) {
        $tahapan = $gelombang?->tahapan?->map(fn ($t) => [
            'tipe' => $t->tipe,
            'nama_tahap' => $t->nama_tahap,
            'tanggal_mulai' => $t->tanggal_mulai->format('Y-m-d'),
            'tanggal_selesai' => $t->tanggal_selesai?->format('Y-m-d'),
        ])->all() ?? [];
    }
    if ($tahapan === []) {
        $tahapan[] = ['tipe' => 'pendaftaran', 'nama_tahap' => 'Pendaftaran Online', 'tanggal_mulai' => '', 'tanggal_selesai' => ''];
    }
@endphp

<div class="row g-3 mb-2">
    <div class="col-12">
        <label class="form-label mb-1">
            Tahapan Gelombang
            <span class="text-muted" style="font-size:12.5px; font-weight:400;">
                — satu baris per tahap. Baris <strong>Pendaftaran</strong> wajib ada:
                tanpa itu gelombang tidak akan pernah bisa dipilih pendaftar.
            </span>
        </label>
    </div>
</div>

<div id="wrapper-tahapan" data-tahapan="{{ json_encode(array_map(fn ($t) => $t['tipe'] ?? 'lainnya', $tahapan)) }}">
    @foreach ($tahapan as $i => $t)
        <div class="row g-2 mb-2 align-items-end tahap-row">
            <div class="col-12 col-sm-3">
                <select name="tahapan[{{ $i }}][tipe]" class="form-select @error('tahapan.'.$i.'.tipe') is-invalid @enderror">
                    @foreach (\App\Models\GelombangTahap::TIPE as $value => $label)
                        <option value="{{ $value }}" @selected(($t['tipe'] ?? 'lainnya') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tahapan.'.$i.'.tipe')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-sm-3">
                <input type="text" name="tahapan[{{ $i }}][nama_tahap]" value="{{ $t['nama_tahap'] ?? '' }}"
                       class="form-control @error('tahapan.'.$i.'.nama_tahap') is-invalid @enderror" placeholder="Nama tahap" />
                @error('tahapan.'.$i.'.nama_tahap')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-sm-2">
                <input type="date" name="tahapan[{{ $i }}][tanggal_mulai]" value="{{ $t['tanggal_mulai'] ?? '' }}"
                       class="form-control @error('tahapan.'.$i.'.tanggal_mulai') is-invalid @enderror" />
                @error('tahapan.'.$i.'.tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-sm-2">
                <input type="date" name="tahapan[{{ $i }}][tanggal_selesai]" value="{{ $t['tanggal_selesai'] ?? '' }}"
                       class="form-control @error('tahapan.'.$i.'.tanggal_selesai') is-invalid @enderror" />
                @error('tahapan.'.$i.'.tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-sm-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100 h-38" onclick="hapusTahapan(this)">Hapus</button>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-2 mb-3">
    <div class="col-12">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahTahapan()">+ Tambah Tahap</button>
        <span class="text-muted" style="font-size:12.5px;">Tanggal selesai dikosongkan = tahap berlangsung satu hari.</span>
    </div>
</div>

@error('tahapan')<div class="text-danger mb-3" style="font-size:12.5px;">{{ $message }}</div>@enderror

@push('scripts')
<script>
    function tambahTahapan() {
        const wrapper = document.getElementById('wrapper-tahapan');
        const index = wrapper.querySelectorAll('.tahapan-row').length;
        const tipe = ['pendaftaran', 'verifikasi', 'tes', 'pengumuman', 'daftar_ulang', 'lainnya'];
        const nama = {
            pendaftaran: 'Pendaftaran Online', verifikasi: 'Verifikasi Berkas',
            tes: 'Tes Seleksi', pengumuman: 'Pengumuman Hasil',
            daftar_ulang: 'Daftar Ulang', lainnya: 'Lainnya',
        };
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-end tahap-row';
        row.innerHTML = `
            <div class="col-12 col-sm-3">
                <select name="tahapan[${index}][tipe]" class="form-select">
                    ${tipe.map(t => `<option value="${t}">${nama[t]}</option>`).join('')}
                </select>
            </div>
            <div class="col-12 col-sm-3">
                <input type="text" name="tahapan[${index}][nama_tahap]" class="form-control" placeholder="Nama tahap" value="Lainnya" />
            </div>
            <div class="col-12 col-sm-2"><input type="date" name="tahapan[${index}][tanggal_mulai]" class="form-control" /></div>
            <div class="col-12 col-sm-2"><input type="date" name="tahapan[${index}][tanggal_selesai]" class="form-control" /></div>
            <div class="col-12 col-sm-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="hapusTahapan(this)">Hapus</button>
            </div>`;
        wrapper.appendChild(row);
    }

    function hapusTahapan(button) {
        const wrapper = document.getElementById('wrapper-tahapan');
        if (wrapper.querySelectorAll('.tahapan-row').length <= 1) return;
        button.closest('.tahapan-row').remove();
    }
</script>
@endpush

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="kuota" value="{{ old('kuota', $gelombang->kuota ?? 80) }}" class="form-control @error('kuota') is-invalid @enderror" id="kuota" min="1" required />
            <label for="kuota">Kuota <span class="text-danger">*</span></label>
            @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="terisi" value="{{ old('terisi', $gelombang->terisi ?? 0) }}" class="form-control @error('terisi') is-invalid @enderror" id="terisi" min="0" />
            <label for="terisi">Terisi</label>
            @error('terisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="diskon_persen" value="{{ old('diskon_persen', $gelombang->diskon_persen ?? '') }}" class="form-control @error('diskon_persen') is-invalid @enderror" id="diskon_persen" min="0" max="100" placeholder="Diskon" />
            <label for="diskon_persen">Diskon %</label>
            @error('diskon_persen')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <textarea name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" placeholder="Keterangan" style="height:58px">{{ old('keterangan', $gelombang->keterangan ?? '') }}</textarea>
            <label for="keterangan">Keterangan</label>
            @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Keuntungan (isi 1 per baris, akan disimpan sebagai array)</label>
    @php $keuntungan = old('keuntungan', $gelombang->keuntungan ?? []); if (is_string($keuntungan)) $keuntungan = json_decode($keuntungan, true) ?? []; @endphp
    <div class="row g-2">
        @for ($i = 0; $i < 3; $i++)
            <div class="col-12 col-sm-4">
                <input type="text" name="keuntungan[]" value="{{ $keuntungan[$i] ?? '' }}" class="form-control @error('keuntungan.'.$i) is-invalid @enderror" placeholder="Keuntungan {{ $i+1 }}" />
                @error('keuntungan.'.$i)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @endfor
    </div>
    @error('keuntungan')<div class="text-danger" style="font-size:12.5px;">{{ $message }}</div>@enderror
</div>
