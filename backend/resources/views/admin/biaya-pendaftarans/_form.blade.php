{{-- $biaya (nullable), $tahunAjarans, $tahunAktif --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="tahun_ajaran_id">Tahun Ajaran <span class="text-danger">*</span></label>
        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-select @error('tahun_ajaran_id') is-invalid @enderror" required>
            <option value="">— Pilih Tahun —</option>
            @foreach ($tahunAjarans as $ta)
                <option value="{{ $ta->id }}" @selected((string) old('tahun_ajaran_id', $biaya->tahun_ajaran_id ?? $tahunAktif->id ?? '') === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }} {{ $ta->status_aktif ? '(aktif)' : '' }}</option>
            @endforeach
        </select>
        @error('tahun_ajaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="jenis_biaya" value="{{ old('jenis_biaya', $biaya->jenis_biaya ?? '') }}" class="form-control @error('jenis_biaya') is-invalid @enderror" id="jenis_biaya" placeholder="Jenis Biaya" required />
            <label for="jenis_biaya">Jenis Biaya <span class="text-danger">*</span></label>
            @error('jenis_biaya')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" step="0.01" name="jumlah" value="{{ old('jumlah', $biaya->jumlah ?? '') }}" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah" placeholder="Jumlah" required />
            <label for="jumlah">Jumlah (IDR) <span class="text-danger">*</span></label>
            @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-2">
        <div class="form-floating">
            <input type="text" name="mata_uang" value="{{ old('mata_uang', $biaya->mata_uang ?? 'IDR') }}" class="form-control @error('mata_uang') is-invalid @enderror" id="mata_uang" placeholder="Mata Uang" />
            <label for="mata_uang">Mata Uang</label>
            @error('mata_uang')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-check mt-3">
            <input type="hidden" name="wajib_bayar" value="0" />
            <input type="checkbox" name="wajib_bayar" value="1" id="wajib_bayar" class="form-check-input" @checked(old('wajib_bayar', ($biaya->wajib_bayar ?? true) ? '1' : '0') === '1') />
            <label class="form-check-label" for="wajib_bayar">Wajib Bayar</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="dapat_diangsur" value="0" />
            <input type="checkbox" name="dapat_diangsur" value="1" id="dapat_diangsur" class="form-check-input" @checked(old('dapat_diangsur', ($biaya->dapat_diangsur ?? false) ? '1' : '0') === '1') />
            <label class="form-check-label" for="dapat_diangsur">Dapat Diangsur</label>
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="max_cicilan" value="{{ old('max_cicilan', $biaya->max_cicilan ?? '') }}" class="form-control @error('max_cicilan') is-invalid @enderror" id="max_cicilan" placeholder="Max Cicilan" />
            <label for="max_cicilan">Max Cicilan</label>
            @error('max_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" step="0.01" name="min_dp" value="{{ old('min_dp', $biaya->min_dp ?? '') }}" class="form-control @error('min_dp') is-invalid @enderror" id="min_dp" placeholder="Min DP" />
            <label for="min_dp">Min DP</label>
            @error('min_dp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" name="jangka_waktu_hari" value="{{ old('jangka_waktu_hari', $biaya->jangka_waktu_hari ?? '') }}" class="form-control @error('jangka_waktu_hari') is-invalid @enderror" id="jangka_waktu_hari" placeholder="Jangka Hari" />
            <label for="jangka_waktu_hari">Jangka Waktu (hari)</label>
            @error('jangka_waktu_hari')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <textarea name="keterangan" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" placeholder="Keterangan" style="height:58px">{{ old('keterangan', $biaya->keterangan ?? '') }}</textarea>
            <label for="keterangan">Keterangan</label>
            @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
