{{-- Form fields peran. Variabel: $role (nullable), $permissions --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}"
                class="form-control @error('name') is-invalid @enderror" id="name"
                placeholder="Nama Peran" required />
            <label for="name">Nama Peran</label>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size: 12px;">Gunakan huruf kecil, tanpa spasi (contoh: admin-sekolah).</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Permission</label>
    <div class="d-flex flex-wrap gap-3">
        @forelse ($permissions as $perm)
            <div class="form-check">
                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="perm-{{ $perm->name }}"
                    class="form-check-input"
                    @checked(in_array($perm->name, old('permissions', ($role ?? null)?->permissions->pluck('name')->all() ?? []))) />
                <label class="form-check-label" for="perm-{{ $perm->name }}" style="font-size: 13px;">{{ $perm->name }}</label>
            </div>
        @empty
            <span style="font-size: 13px; color: var(--text-muted);">Belum ada permission tersedia.</span>
        @endforelse
    </div>
    @error('permissions')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
</div>
