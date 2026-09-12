@extends('layouts.app')

@section('title', 'Nexus Admin — Kenaikan Kelas')
@section('breadcrumb', 'Kenaikan Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Kenaikan Kelas & Kelulusan</h1>
        <p class="page-subtitle mb-0">Pilih siswa, tentukan tujuan, lalu naikkan atau luluskan</p>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Siswa Aktif</h5>
            <p class="card-subtitle">{{ $siswas->total() }} siswa</p>
        </div>
        <form method="GET" action="{{ route('admin.kenaikan.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected((string) $tahunMode === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
            </select>
            <select name="kelas" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected(request('kelas')==$k->id)>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            @if($tahunMode!=='aktif'||request('kelas'))<a href="{{ route('admin.kenaikan.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <form method="POST" id="form-kenaikan">
            @csrf
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead><tr><th><input type="checkbox" id="check-all" class="form-check-input" aria-label="Pilih semua" /></th><th>Nama</th><th>Kelas</th><th>Tahun</th></tr></thead>
                    <tbody>
                        @forelse($siswas as $s)
                            <tr>
                                <td><input type="checkbox" name="siswa_ids[]" value="{{ $s->id }}" class="form-check-input check-row" /></td>
                                <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ $s->nis ?? $s->nisn ?? '-' }}</div></td>
                                <td style="font-size:13px;">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                                <td style="font-size:12.5px;">{{ $s->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa aktif pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($siswas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $siswas->links('pagination::bootstrap-5') }}</div>@endif

            @can('kenaikan-kelas.execute')
            <div class="p-3 border-top d-flex flex-wrap gap-2 align-items-end" style="border-color:var(--border-color)!important;">
                <div>
                    <label class="form-label" style="font-size:12px;" for="tahun_tujuan_id">Tahun Tujuan <span class="text-danger">*</span></label>
                    <select name="tahun_tujuan_id" id="tahun_tujuan_id" class="form-select form-select-sm" style="width:auto" required>
                        @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected(($tahunAktif->id ?? null)==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;" for="kelas_tujuan_id">Kelas Tujuan (khusus naik)</label>
                    <select name="kelas_tujuan_id" id="kelas_tujuan_id" class="form-select form-select-sm" style="width:auto">
                        <option value="">— Pilih —</option>
                        @foreach($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>@endforeach
                    </select>
                </div>
                <button type="submit" formaction="{{ route('admin.kenaikan.naikkan') }}" class="btn btn-primary btn-sm" data-confirm="Naikkan siswa terpilih?"><i class="fa-solid fa-arrow-up"></i> Naikkan Terpilih</button>
                <button type="submit" formaction="{{ route('admin.kenaikan.luluskan') }}" class="btn btn-nexus-outline btn-sm text-success" data-confirm="Luluskan siswa terpilih (akun dinonaktifkan)?"><i class="fa-solid fa-graduation-cap"></i> Luluskan Terpilih</button>
            </div>
            @endcan
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const all = document.getElementById('check-all');
    if (!all) return;
    all.addEventListener('change', () => {
        document.querySelectorAll('.check-row').forEach((c) => { c.checked = all.checked; });
    });
})();
</script>
@endpush
