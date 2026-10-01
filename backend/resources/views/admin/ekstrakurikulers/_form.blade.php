{{-- Pilihan pembina: select list guru + opsi pembina luar sekolah (input manual). --}}
@php
    $pembinaSaatIni = old('pembina', $ekstrakurikuler->pembina ?? '');
    $daftarNamaGuru = $gurus->pluck('nama')->all();
    $pembinaDariGuru = in_array($pembinaSaatIni, $daftarNamaGuru, true);
    $pilihAwal = old('pembina_pilih', $pembinaDariGuru ? $pembinaSaatIni : ($pembinaSaatIni !== '' ? '__luar__' : ''));
    $manualAwal = old('pembina_manual', $pembinaDariGuru ? '' : $pembinaSaatIni);
@endphp

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="kode" value="{{ old('kode', $ekstrakurikuler->kode ?? '') }}" class="form-control @error('kode') is-invalid @enderror" id="kode" placeholder="PRAMUKA" required />
            <label for="kode">Kode <span class="text-danger">*</span></label>
            @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-9">
        <div class="form-floating">
            <input type="text" name="nama" value="{{ old('nama', $ekstrakurikuler->nama ?? '') }}" class="form-control @error('nama') is-invalid @enderror" id="nama" placeholder="Pramuka" required />
            <label for="nama">Nama Ekstrakurikuler <span class="text-danger">*</span></label>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="pembina_pilih" id="pembina_pilih" class="form-select @error('pembina_manual') is-invalid @enderror">
                <option value="">— Tanpa Pembina —</option>
                @foreach ($gurus as $g)
                    <option value="{{ $g->nama }}" @selected($pilihAwal === $g->nama)>{{ $g->nama }}</option>
                @endforeach
                <option value="__luar__" @selected($pilihAwal === '__luar__')>Pembina Luar Sekolah (isi manual)</option>
            </select>
            <label for="pembina_pilih">Pembina</label>
            @error('pembina_manual')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6" id="pembina_manual_wrap" style="{{ $pilihAwal === '__luar__' ? '' : 'display:none;' }}">
        <div class="form-floating">
            <input type="text" name="pembina_manual" value="{{ $manualAwal }}" class="form-control @error('pembina_manual') is-invalid @enderror" id="pembina_manual" placeholder="Nama pembina luar sekolah" />
            <label for="pembina_manual">Nama Pembina Luar <span class="text-danger">*</span></label>
            @error('pembina_manual')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-8">
        <div class="form-floating">
            <input type="text" name="jadwal" value="{{ old('jadwal', $ekstrakurikuler->jadwal ?? '') }}" class="form-control @error('jadwal') is-invalid @enderror" id="jadwal" placeholder="Sabtu 08:00-10:00" />
            <label for="jadwal">Jadwal</label>
            @error('jadwal')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($ekstrakurikuler->is_aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" placeholder="Deskripsi" style="height:90px;">{{ old('deskripsi', $ekstrakurikuler->deskripsi ?? '') }}</textarea>
            <label for="deskripsi">Deskripsi</label>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const pilih = document.getElementById('pembina_pilih');
    const wrap = document.getElementById('pembina_manual_wrap');
    const manual = document.getElementById('pembina_manual');
    if (!pilih || !wrap || !manual) return;

    function toggleManual() {
        const luar = pilih.value === '__luar__';
        wrap.style.display = luar ? '' : 'none';
        manual.required = luar;
        if (!luar) manual.value = '';
    }

    pilih.addEventListener('change', toggleManual);
    toggleManual();
})();
</script>
@endpush
