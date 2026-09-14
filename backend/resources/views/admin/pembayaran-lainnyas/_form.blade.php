{{-- $pembayaran (nullable), $calons --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="calon_siswa_id">Calon Siswa <span class="text-danger">*</span></label>
        <select name="calon_siswa_id" id="calon_siswa_id" class="form-select @error('calon_siswa_id') is-invalid @enderror" required>
            <option value="">— Pilih Calon —</option>
            @foreach ($calons as $c)
                <option value="{{ $c->id }}" @selected((string) old('calon_siswa_id', $pembayaran->calon_siswa_id ?? '') === (string) $c->id)>{{ $c->no_pendaftaran }} — {{ $c->nama_lengkap }} ({{ $c->jalurPendaftaran->nama_jalur ?? '' }})</option>
            @endforeach
        </select>
        @error('calon_siswa_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama_biaya" value="{{ old('nama_biaya', $pembayaran->nama_biaya ?? '') }}" class="form-control @error('nama_biaya') is-invalid @enderror" id="nama_biaya" placeholder="Nama Biaya" required />
            <label for="nama_biaya">Nama Biaya <span class="text-danger">*</span></label>
            @error('nama_biaya')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size:11px;">Nama bebas di luar biaya pendaftaran master.</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="number" step="0.01" name="jumlah" value="{{ old('jumlah', $pembayaran->jumlah ?? '') }}" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah" placeholder="Jumlah" required />
            <label for="jumlah">Jumlah <span class="text-danger">*</span></label>
            @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <label class="form-label" for="metode_pembayaran">Metode <span class="text-danger">*</span></label>
        <select name="metode_pembayaran" id="metode_pembayaran" class="form-select @error('metode_pembayaran') is-invalid @enderror" required>
            <option value="transfer" @selected(old('metode_pembayaran', $pembayaran->metode_pembayaran ?? 'transfer')==='transfer')>Transfer</option>
            <option value="tunai" @selected(old('metode_pembayaran', $pembayaran->metode_pembayaran ?? '')==='tunai')>Tunai</option>
        </select>
        @error('metode_pembayaran')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="bukti_pembayaran_path">Bukti Pembayaran</label>
        @if (!empty($pembayaran->bukti_pembayaran_path ?? null))
            <div style="font-size:11px;">Saat ini: <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="text-primary">Lihat</a></div>
        @endif
        <input type="file" name="bukti_pembayaran_path" id="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('bukti_pembayaran_path') is-invalid @enderror" />
        @error('bukti_pembayaran_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text" style="font-size:11px;">PDF/JPG 5MB. <strong>Wajib</strong> bila metode transfer, opsional bila tunai.</div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_pembayaran" value="{{ old('tanggal_pembayaran', isset($pembayaran->tanggal_pembayaran) ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_pembayaran') is-invalid @enderror" id="tanggal_pembayaran" />
            <label for="tanggal_pembayaran">Tgl Bayar</label>
            @error('tanggal_pembayaran')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        @if(!empty($pembayaran) && $pembayaran->exists)
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                <option value="menunggu" @selected(old('status', $pembayaran->status ?? 'menunggu')==='menunggu')>Menunggu</option>
                <option value="berhasil" @selected(old('status', $pembayaran->status ?? '')==='berhasil')>Berhasil</option>
                <option value="gagal" @selected(old('status', $pembayaran->status ?? '')==='gagal')>Gagal</option>
            </select>
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @else
            <input type="hidden" name="status" value="berhasil" />
            <div class="alert alert-info mb-0 py-2" style="font-size:12px;">Input admin otomatis <strong>berhasil</strong>.</div>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="catatan" id="catatan" class="form-control @error('catatan') is-invalid @enderror" placeholder="Catatan" style="height:58px">{{ old('catatan', $pembayaran->catatan ?? '') }}</textarea>
            <label for="catatan">Catatan</label>
            @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var metode = document.getElementById('metode_pembayaran');
    var bukti = document.getElementById('bukti_pembayaran_path');
    if (metode && bukti) {
        var syncBukti = function () { bukti.required = metode.value === 'transfer'; };
        metode.addEventListener('change', syncBukti);
        syncBukti();
    }
})();
</script>
@endpush
