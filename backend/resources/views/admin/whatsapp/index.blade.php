@extends('layouts.app')

@section('title', 'Nexus Admin — WhatsApp')
@section('breadcrumb', 'WhatsApp')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">WhatsApp Gateway</h1>
        <p class="page-subtitle mb-0">Nomor tertaut, status, dan QR — manual refresh</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" id="btn-refresh" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-rotate"></i> Refresh</button>
        @can('whatsapp.manage')
            <form method="POST" action="{{ route('admin.whatsapp.disconnect') }}" class="d-inline">@csrf<button type="submit" class="btn btn-nexus-outline btn-sm text-danger" data-confirm="Disconnect WA? Perlu scan ulang untuk terhubung lagi."><i class="fa-solid fa-link-slash"></i> Disconnect</button></form>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mb-3">
    <div class="card-header-nexus"><h5 class="card-title">Status</h5></div>
    <div class="card-body-nexus">
        <div id="wa-status">
            @if(($status['ok'] ?? false) && ($status['siap'] ?? false))
                <span class="badge-nexus badge-info">Terhubung</span>
                <span class="ms-2" style="font-size:13px;">Nomor: <strong>{{ $status['nomor'] ?? '—' }}</strong></span>
            @elseif($status['qrTersedia'] ?? false)
                <span class="badge-nexus badge-warning">Butuh scan QR</span>
            @else
                <span class="badge-nexus badge-neutral">Tak terhubung</span>
                <span class="ms-2" style="font-size:12px;color:var(--text-muted);">Gateway tidak merespons (pastikan wa-gateway jalan di 127.0.0.1:3001)</span>
            @endif
        </div>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><h5 class="card-title">QR Code</h5><span style="font-size:12px;color:var(--text-muted);">Scan dengan WA HP sekolah</span></div>
    <div class="card-body-nexus text-center">
        <img id="wa-qr" src="{{ route('admin.whatsapp.qr') }}" alt="QR WhatsApp" style="max-width:320px; width:100%; display:none;" onerror="this.style.display='none'" onload="this.style.display='block'" />
        <p id="wa-qr-hint" style="font-size:13px;color:var(--text-muted);">Refresh untuk memuat QR terbaru.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btn-refresh').addEventListener('click', function () {
    const img = document.getElementById('wa-qr');
    img.src = "{{ route('admin.whatsapp.qr') }}?t=" + Date.now();
    fetch("{{ route('admin.whatsapp.status') }}").then(r => r.json()).then(s => {
        const el = document.getElementById('wa-status');
        if (s.siap) el.innerHTML = '<span class="badge-nexus badge-info">Terhubung</span> <span class="ms-2" style="font-size:13px;">Nomor: <strong>' + (s.nomor || '—') + '</strong></span>';
        else if (s.qrTersedia) el.innerHTML = '<span class="badge-nexus badge-warning">Butuh scan QR</span>';
        else el.innerHTML = '<span class="badge-nexus badge-neutral">Tak terhubung</span>';
    });
});
</script>
@endpush
