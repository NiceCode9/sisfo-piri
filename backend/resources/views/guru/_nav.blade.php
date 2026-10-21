<?php

/**
 * Tab navigasi area guru. Ke depan bisa ditambah modul baru tanpa menyentuh
 * halaman yang memakainya.
 */
?>
<div class="d-flex gap-2 flex-wrap mb-3">
    @php
        $tabs = [
            'dashboard' => ['label' => 'Dashboard', 'route' => 'guru.dashboard', 'icon' => 'fa-solid fa-th-large'],
            'absensi-qr' => ['label' => 'QR Absen', 'route' => 'guru.absensi-qr', 'icon' => 'fa-solid fa-qrcode'],
            'absensi' => ['label' => 'Absensi', 'route' => 'admin.absensis.index', 'icon' => 'fa-solid fa-clipboard-check'],
            'tugas' => ['label' => 'Tugas', 'route' => 'admin.tugas.index', 'icon' => 'fa-solid fa-clipboard-question'],
            'ujian' => ['label' => 'Ujian CBT', 'route' => 'admin.cbt.exams.index', 'icon' => 'fa-solid fa-file-question'],
        ];
    @endphp
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" class="btn btn-sm {{ ($tabAktif ?? '') === $key ? 'btn-primary' : 'btn-nexus-outline' }}"><i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}</a>
    @endforeach
</div>
