{{-- Form fields pengguna. Variabel: $user (nullable), $roles, $isEdit --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}"
                class="form-control @error('username') is-invalid @enderror" id="username"
                placeholder="Username" autocomplete="username" required />
            <label for="username">Username</label>
            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
                class="form-control @error('name') is-invalid @enderror" id="name"
                placeholder="Nama Lengkap" autocomplete="name" required />
            <label for="name">Nama Lengkap</label>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                class="form-control @error('email') is-invalid @enderror" id="email"
                placeholder="Email (opsional)" autocomplete="email" />
            <label for="email">Email (opsional)</label>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <label class="form-label">Role</label>
        <div class="d-flex flex-wrap gap-3">
            @forelse ($roles as $role)
                <div class="form-check">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->name }}"
                        class="form-check-input"
                        @checked(in_array($role->name, old('roles', ($user ?? null)?->roles->pluck('name')->all() ?? []))) />
                    <label class="form-check-label" for="role-{{ $role->name }}" style="font-size: 13px;">{{ $role->name }}</label>
                </div>
            @empty
                <span style="font-size: 13px; color: var(--text-muted);">Belum ada role tersedia.</span>
            @endforelse
        </div>
        @error('roles')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="password" name="password"
                class="form-control @error('password') is-invalid @enderror" id="password"
                placeholder="Password" autocomplete="new-password" @if (! ($isEdit ?? false)) required @endif />
            <label for="password">Password{{ ($isEdit ?? false) ? ' (kosongkan bila tidak diubah)' : '' }}</label>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation"
                placeholder="Konfirmasi Password" autocomplete="new-password" @if (! ($isEdit ?? false)) required @endif />
            <label for="password_confirmation">Konfirmasi Password</label>
        </div>
    </div>
</div>
