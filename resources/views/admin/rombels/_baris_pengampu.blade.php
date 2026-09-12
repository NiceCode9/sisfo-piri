{{-- Satu baris dinamis form batch pengampu. $index: int|string, $row: array{guru_id?, mata_pelajaran_id?} --}}
<div class="baris-pengampu row g-2 mb-2">
    <div class="col-12 col-md-5">
        <select name="baris[{{ $index }}][guru_id]" class="form-select form-select-sm" required aria-label="Guru">
            <option value="">— Pilih Guru —</option>
            @foreach($gurus as $g)<option value="{{ $g->id }}" @selected(($row['guru_id'] ?? '')==$g->id)>{{ $g->nama }}</option>@endforeach
        </select>
    </div>
    <div class="col-10 col-md-6">
        <select name="baris[{{ $index }}][mata_pelajaran_id]" class="form-select form-select-sm" required aria-label="Mata pelajaran">
            <option value="">— Pilih Mapel —</option>
            @foreach($mapels as $m)<option value="{{ $m->id }}" @selected(($row['mata_pelajaran_id'] ?? '')==$m->id) @disabled(in_array($m->id, $mapelTerisi))>{{ $m->kode }} — {{ $m->nama }}{{ in_array($m->id, $mapelTerisi) ? ' (sudah ada)' : '' }}</option>@endforeach
        </select>
    </div>
    <div class="col-2 col-md-1 d-flex align-items-center">
        <button type="button" class="btn-icon btn btn-nexus-outline btn-sm text-danger" onclick="hapusBarisPengampu(this)" aria-label="Hapus baris"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>
