{{-- Tab navigasi area orang tua. $tabAktif: dashboard|profil|materi|tugas --}}
<div class="d-flex gap-2 flex-wrap mb-3">
    @php
        $tabs = [
            'dashboard' => ['label' => 'Dashboard', 'route' => 'ortu.dashboard', 'icon' => 'fa-solid fa-th-large'],
            'profil' => ['label' => 'Profil Saya', 'route' => 'ortu.profil', 'icon' => 'fa-solid fa-circle-user'],
            'materi' => ['label' => 'Materi', 'route' => 'ortu.materi.index', 'icon' => 'fa-solid fa-book-open-reader'],
            'tugas' => ['label' => 'Tugas', 'route' => 'ortu.tugas.index', 'icon' => 'fa-solid fa-clipboard-question'],
        ];
    @endphp
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" class="btn btn-sm {{ ($tabAktif ?? '') === $key ? 'btn-primary' : 'btn-nexus-outline' }}"><i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}</a>
    @endforeach
</div>