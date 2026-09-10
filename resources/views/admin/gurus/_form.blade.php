<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nama" value="{{ old('nama', $guru->nama ?? '') }}" class="form-control @error('nama') is-invalid @enderror" id="nama" placeholder="Nama Lengkap" required />
            <label for="nama">Nama Lengkap <span class="text-danger">*</span></label>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="nip" value="{{ old('nip', $guru->nip ?? '') }}" class="form-control @error('nip') is-invalid @enderror" id="nip" placeholder="NIP" />
            <label for="nip">NIP (opsional)</label>
            @error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" id="jenis_kelamin" required>
                <option value="L" @selected(old('jenis_kelamin', $guru->jenis_kelamin ?? 'L')==='L')>Laki-laki</option>
                <option value="P" @selected(old('jenis_kelamin', $guru->jenis_kelamin ?? '')==='P')>Perempuan</option>
            </select>
            <label for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
            @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="telp" value="{{ old('telp', $guru->telp ?? '') }}" class="form-control @error('telp') is-invalid @enderror" id="telp" placeholder="No. HP" />
            <label for="telp">No. HP</label>
            @error('telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-check mt-4">
            <input type="hidden" name="is_aktif" value="0" />
            <input type="checkbox" name="is_aktif" value="1" id="is_aktif" class="form-check-input" @checked(old('is_aktif', ($guru->is_aktif ?? true) ? '1' : '0')==='1') />
            <label class="form-check-label" for="is_aktif">Aktif</label>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="form-floating">
            <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" id="alamat" placeholder="Alamat" style="height:80px;">{{ old('alamat', $guru->alamat ?? '') }}</textarea>
            <label for="alamat">Alamat</label>
            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<hr class="my-4" style="border-color: var(--border-color);" />
<h6 style="font-weight:700;font-size:14px;margin-bottom:12px;">Akun Login (role guru)</h6>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="username" value="{{ old('username', $guru->user->username ?? '') }}" class="form-control @error('username') is-invalid @enderror" id="username" placeholder="Username" required />
            <label for="username">Username <span class="text-danger">*</span></label>
            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Password" @if(!isset($guru)) required @endif />
            <label for="password">Password @if(!isset($guru))<span class="text-danger">*</span>@else (kosongkan bila tidak diubah)@endif</label>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Konfirmasi" @if(!isset($guru)) required @endif />
            <label for="password_confirmation">Konfirmasi Password</label>
        </div>
    </div>
</div>
