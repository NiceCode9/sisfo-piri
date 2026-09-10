{{-- $pembayaran (nullable), $calons, $biayas --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="calon_siswa_id">Calon Siswa <span class="text-danger">*</span></label>
        <select name="calon_siswa_id" id="calon_siswa_id" class="form-select @error('calon_siswa_id') is-invalid @enderror" required>
            <option value="">— Pilih Calon —</option>
            @foreach ($calons as $c)
                <option value="{{ $c->id }}" @selected((string) old('calon_siswa_id', $pembayaran->calon_siswa_id ?? '') === (string) $c->id)>{{ $c->no_pendaftaran }} — {{ $c->nama_lengkap }} ({{ $c->jalurPendaftaran->nama_jalur ?? '' }})@isset($c->sisa_tagihan) — Sisa Rp {{ number_format($c->sisa_tagihan, 0, ',', '.') }}@endisset</option>
            @endforeach
        </select>
        @error('calon_siswa_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text" style="font-size:11px;">Hanya calon dengan sisa tagihan yang ditampilkan.</div>
    </div>
    <div class="col-12 col-sm-6">
        <label class="form-label" for="biaya_pendaftaran_id">Biaya</label>
        <select name="biaya_pendaftaran_id" id="biaya_pendaftaran_id" class="form-select @error('biaya_pendaftaran_id') is-invalid @enderror">
            <option value="">— Pilih Biaya —</option>
            @foreach ($biayas as $b)
                <option value="{{ $b->id }}" data-dapat-diangsur="{{ $b->dapat_diangsur ? 1 : 0 }}" data-max-cicilan="{{ $b->max_cicilan ?? '' }}" data-min-dp="{{ $b->min_dp ?? 0 }}" @selected((string) old('biaya_pendaftaran_id', $pembayaran->biaya_pendaftaran_id ?? '') === (string) $b->id)>{{ $b->jenis_biaya }} — Rp {{ number_format($b->jumlah,0,',','.') }}{{ $b->dapat_diangsur ? ' (dapat diangsur)' : '' }}</option>
            @endforeach
        </select>
        @error('biaya_pendaftaran_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" step="0.01" name="jumlah" value="{{ old('jumlah', $pembayaran->jumlah ?? '') }}" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah" placeholder="Jumlah" required />
            <label for="jumlah">Jumlah <span class="text-danger">*</span></label>
            @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label" for="metode_pembayaran">Metode <span class="text-danger">*</span></label>
        <select name="metode_pembayaran" id="metode_pembayaran" class="form-select @error('metode_pembayaran') is-invalid @enderror" required>
            <option value="transfer" @selected(old('metode_pembayaran', $pembayaran->metode_pembayaran ?? 'transfer')==='transfer')>Transfer</option>
            <option value="tunai" @selected(old('metode_pembayaran', $pembayaran->metode_pembayaran ?? '')==='tunai')>Tunai</option>
        </select>
        @error('metode_pembayaran')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label" for="jenis_pembayaran">Jenis</label>
        <select name="jenis_pembayaran" id="jenis_pembayaran" class="form-select @error('jenis_pembayaran') is-invalid @enderror">
            <option value="penuh" @selected(old('jenis_pembayaran', $pembayaran->jenis_pembayaran ?? 'penuh')==='penuh')>Penuh</option>
            <option value="dp_angsuran" @selected(old('jenis_pembayaran', $pembayaran->jenis_pembayaran ?? '')==='dp_angsuran')>DP Angsuran</option>
            <option value="cicilan_angsuran" @selected(old('jenis_pembayaran', $pembayaran->jenis_pembayaran ?? '')==='cicilan_angsuran')>Cicilan</option>
        </select>
        @error('jenis_pembayaran')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="buat_angsuran" value="1" id="buat_angsuran" class="form-check-input" @checked(old('buat_angsuran', false)) />
            <label class="form-check-label" for="buat_angsuran">Buat sebagai angsuran <span style="font-size:11px;color:var(--text-muted);">(hanya untuk biaya yang dapat diangsur — tagihan + DP + jadwal cicilan dibuat sekaligus)</span></label>
        </div>
        @error('buat_angsuran')<div class="text-danger" style="font-size:12.5px;">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3 d-none" id="angsuran-fields">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" step="0.01" name="dp_dibayar" value="{{ old('dp_dibayar') }}" class="form-control @error('dp_dibayar') is-invalid @enderror" id="dp_dibayar" placeholder="DP" min="0" />
            <label for="dp_dibayar">DP Dibayar</label>
            @error('dp_dibayar')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="number" name="jumlah_cicilan" value="{{ old('jumlah_cicilan') }}" class="form-control @error('jumlah_cicilan') is-invalid @enderror" id="jumlah_cicilan" placeholder="Cicilan" min="1" max="60" />
            <label for="jumlah_cicilan">Jumlah Cicilan</label>
            @error('jumlah_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" id="tanggal_mulai" />
            <label for="tanggal_mulai">Tanggal Mulai Cicilan</label>
            @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var check = document.getElementById('buat_angsuran');
    var box = document.getElementById('angsuran-fields');
    var biaya = document.getElementById('biaya_pendaftaran_id');
    if (!check || !box || !biaya) return;

    function refresh() {
        var opt = biaya.selectedOptions[0];
        var bisa = opt && opt.dataset.dapatDiangsur === '1';
        check.disabled = !bisa;
        if (!bisa) { check.checked = false; }
        box.classList.toggle('d-none', !check.checked);
        ['dp_dibayar', 'jumlah_cicilan', 'tanggal_mulai'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.required = check.checked;
        });
        var max = opt && opt.dataset.maxCicilan ? parseInt(opt.dataset.maxCicilan, 10) : null;
        var cicilan = document.getElementById('jumlah_cicilan');
        if (cicilan) {
            if (max) { cicilan.max = max; } else { cicilan.removeAttribute('max'); }
        }
    }

    check.addEventListener('change', refresh);
    biaya.addEventListener('change', refresh);
    refresh();
})();
</script>
@endpush

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="bukti_pembayaran_path">Bukti Pembayaran</label>
        @if (!empty($pembayaran->bukti_pembayaran_path ?? null))
            <div style="font-size:11px;">Saat ini: <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="text-primary">Lihat</a></div>
        @endif
        <input type="file" name="bukti_pembayaran_path" id="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('bukti_pembayaran_path') is-invalid @enderror" />
        @error('bukti_pembayaran_path')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text" style="font-size:11px;">PDF/JPG 5MB. Admin upload otomatis berhasil.</div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="date" name="tanggal_pembayaran" value="{{ old('tanggal_pembayaran', isset($pembayaran->tanggal_pembayaran) ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('Y-m-d') : '') }}" class="form-control @error('tanggal_pembayaran') is-invalid @enderror" id="tanggal_pembayaran" />
            <label for="tanggal_pembayaran">Tgl Bayar</label>
            @error('tanggal_pembayaran')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <label class="form-label" for="status">Status</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
            <option value="menunggu" @selected(old('status', $pembayaran->status ?? 'menunggu')==='menunggu')>Menunggu</option>
            <option value="berhasil" @selected(old('status', $pembayaran->status ?? '')==='berhasil')>Berhasil</option>
            <option value="gagal" @selected(old('status', $pembayaran->status ?? '')==='gagal')>Gagal</option>
        </select>
        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <textarea name="catatan" id="catatan" class="form-control @error('catatan') is-invalid @enderror" placeholder="Catatan" style="height:58px">{{ old('catatan', $pembayaran->catatan ?? '') }}</textarea>
            <label for="catatan">Catatan</label>
            @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <textarea name="keterangan_angsuran" id="keterangan_angsuran" class="form-control @error('keterangan_angsuran') is-invalid @enderror" placeholder="Keterangan Angsuran" style="height:58px">{{ old('keterangan_angsuran', $pembayaran->keterangan_angsuran ?? '') }}</textarea>
            <label for="keterangan_angsuran">Keterangan Angsuran</label>
            @error('keterangan_angsuran')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
