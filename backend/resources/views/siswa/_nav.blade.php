{{-- Tab navigasi area siswa. $tabAktif: dashboard|profil|kelas|absensi --}}
<div class="d-flex gap-2 flex-wrap mb-3">
    @php
        $tabs = [
            'dashboard' => ['label' => 'Pendaftaran', 'route' => 'siswa.dashboard', 'icon' => 'fa-solid fa-user-graduate'],
            'profil' => ['label' => 'Profil Saya', 'route' => 'siswa.profil', 'icon' => 'fa-solid fa-circle-user'],
            'kelas' => ['label' => 'Riwayat Kelas', 'route' => 'siswa.kelas', 'icon' => 'fa-solid fa-school-flag'],
            'absensi' => ['label' => 'Absensi Saya', 'route' => 'siswa.absensi', 'icon' => 'fa-solid fa-clipboard-check'],
            'materi' => ['label' => 'Materi', 'route' => 'siswa.materi.index', 'icon' => 'fa-solid fa-book-open-reader'],
            'tugas' => ['label' => 'Tugas', 'route' => 'siswa.tugas.index', 'icon' => 'fa-solid fa-clipboard-question'],
        ];
    @endphp
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" class="btn btn-sm {{ ($tabAktif ?? '') === $key ? 'btn-primary' : 'btn-nexus-outline' }}"><i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}</a>
    @endforeach
</div>
