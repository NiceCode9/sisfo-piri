{{-- Form fields menu. Variabel: $menu (nullable), $parentMenus, $permissions, $permissionIds (edit only) --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="name" value="{{ old('name', $menu->name ?? '') }}"
                class="form-control @error('name') is-invalid @enderror" id="name"
                placeholder="Nama Menu" required />
            <label for="name">Nama Menu</label>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="icon" value="{{ old('icon', $menu->icon ?? '') }}"
                class="form-control @error('icon') is-invalid @enderror" id="icon"
                placeholder="Icon (fa-solid fa-...)" />
            <label for="icon">Icon (fa-solid ...)</label>
            @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="route" value="{{ old('route', $menu->route ?? '') }}"
                class="form-control @error('route') is-invalid @enderror" id="route"
                placeholder="Route name" />
            <label for="route">Route (opsional)</label>
            @error('route')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <input type="text" name="url" value="{{ old('url', $menu->url ?? '') }}"
                class="form-control @error('url') is-invalid @enderror" id="url"
                placeholder="URL" />
            <label for="url">URL (opsional)</label>
            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <label class="form-label" for="parent_id">Parent Menu</label>
        <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">— Tanpa Parent</option>
            @foreach ($parentMenus as $pm)
                <option value="{{ $pm->id }}" @selected((string) old('parent_id', $menu->parent_id ?? '') === (string) $pm->id)>{{ $pm->name }}</option>
            @endforeach
        </select>
        @error('parent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="number" name="order" value="{{ old('order', $menu->order ?? 0) }}"
                class="form-control @error('order') is-invalid @enderror" id="order" min="0" required />
            <label for="order">Urutan</label>
            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="text" name="group" value="{{ old('group', $menu->group ?? '') }}"
                class="form-control @error('group') is-invalid @enderror" id="group"
                placeholder="Group" />
            <label for="group">Group (opsional)</label>
            @error('group')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="form-floating">
            <select name="permission" id="permission" class="form-select @error('permission') is-invalid @enderror">
                <option value="">— Tanpa Permission (publik)</option>
                @foreach ($permissions as $perm)
                    <option value="{{ $perm->name }}" @selected(old('permission', $menu->permission ?? '') === $perm->name)>{{ $perm->name }}</option>
                @endforeach
            </select>
            <label for="permission">Permission (legacy, opsional)</label>
            @error('permission')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text" style="font-size: 11px;">Kolom legacy — tetap dipertahankan. Pilih salah satu jika menu butuh 1 permission.</div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="d-flex gap-3 pt-2">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                    @checked(old('is_active', ($menu->is_active ?? true) ? '1' : '0') === '1') />
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_header" value="0" />
                <input type="checkbox" name="is_header" value="1" id="is_header" class="form-check-input"
                    @checked(old('is_header', ($menu->is_header ?? false) ? '1' : '0') === '1') />
                <label class="form-check-label" for="is_header">Header (label saja)</label>
            </div>
        </div>
        @error('is_active')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
        @error('is_header')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Permission Pivot (multi, opsional)</label>
    <div class="d-flex flex-wrap gap-3">
        @forelse ($permissions as $perm)
            <div class="form-check">
                <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}" id="perm-{{ $perm->id }}"
                    class="form-check-input"
                    @checked(in_array($perm->id, old('permission_ids', $permissionIds ?? ($menu ?? null)?->permissions->pluck('id')->all() ?? []))) />
                <label class="form-check-label" for="perm-{{ $perm->id }}" style="font-size: 13px;">{{ $perm->name }}</label>
            </div>
        @empty
            <span style="font-size: 13px; color: var(--text-muted);">Belum ada permission.</span>
        @endforelse
    </div>
    @error('permission_ids')<div class="text-danger" style="font-size: 12.5px;">{{ $message }}</div>@enderror
    <div class="form-text" style="font-size: 11px;">Jika diisi, akan di-sync ke tabel <code>menu_permission</code>. Kosongkan untuk menu publik.</div>
</div>
