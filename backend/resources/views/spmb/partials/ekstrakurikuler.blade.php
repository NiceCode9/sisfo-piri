@php
    // Kartu dibaca dari tabel `ekstrakurikulers` (dikelola di
    // admin/ekstrakurikulers). Versi lama menulis sembilan kartu hardcoded
    // yang hampir tidak ada di database — Basket, Robotika, KIR, dan
    // Jurnalistik tidak punya baris di tabel mana pun — sementara Paskibra,
    // PMR, dan Rohani Islam yang benar-benar ada tidak pernah disebut.

    // `kode` sudah berupa slug stabil, jadi ikon bisa dipetakan dari situ.
    // Kode yang tidak dikenal tetap dapat kartu, hanya dengan ikon generik.
    $ikonEkskul = [
        'PRAMUKA' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        'PASKIBRA' => 'M5 3v18M19 3v18M3 8h18M3 16h18',
        'FUTSAL' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a5 5 0 110-4h14a5 5 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        'PMR' => 'M12 3v18M8 7h8M6 11h12M4 15h16M6 11v9a2 2 0 002 2h8a2 2 0 002-2v-9',
        'ROHIS' => 'M12 21s-7-4.5-7-10a4 4 0 017-2.6A4 4 0 0119 11c0 5.5-7 10-7 10z',
    ];

    $ekstrakurikulers = $ekstrakurikulers ?? collect();

    // Warna kartu mengikuti index supaya urutannya berganti, sama seperti
    // section lain di halaman ini.
    $gradienEkskul = [
        'from-primary-500 to-primary-700',
        'from-accent-500 to-accent-700',
        'from-secondary-500 to-secondary-700',
        'from-purple-500 to-purple-700',
        'from-pink-500 to-pink-700',
        'from-indigo-500 to-indigo-700',
        'from-rose-500 to-rose-700',
    ];
@endphp

<section id="ekskul" class="py-20 bg-paper-alt">
    <div class="container mx-auto px-4">
        
            @include('spmb.partials.section-head', [
                'nomor' => '08',
                'judulAwal' => 'Ekstrakurikuler &',
                'judulAksen' => 'Program Unggulan',
                'sub' => 'Kembangkan bakat dan minat dengan berbagai pilihan kegiatan menarik',
            ])

        {{-- Grid Cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @forelse ($ekstrakurikulers as $ekskul)
                @php $gradien = $gradienEkskul[$loop->index % count($gradienEkskul)]; @endphp
                <div class="bg-white rounded-card p-8 shadow-sm card-hover">
                    <div class="w-20 h-20 bg-gradient-to-br {{ $gradien }} rounded-soft flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            // `kode` di-uppercase supaya admin yang mengetik "pramuka" tetap dapat ikon.
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $ikonEkskul[strtoupper($ekskul->kode)] ?? 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2z' }}"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">{{ $ekskul->nama }}</h3>
                    {{-- PMR dan Rohani Islam belum punya deskripsi, jadi blok ini
                         tidak boleh mengarang kalimat. --}}
                    @if ($ekskul->deskripsi)
                        <p class="text-sm text-gray-600 mb-4">{{ $ekskul->deskripsi }}</p>
                    @endif
                    @if ($ekskul->jadwal)
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $ekskul->jadwal }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="sm:col-span-2 lg:col-span-3 text-center bg-white rounded-card p-10 shadow-sm">
                    <p class="text-base font-semibold text-gray-700 mb-1">Belum ada kegiatan ekstrakurikuler</p>
                    <p class="text-sm text-gray-500">Silakan cek kembali atau hubungi sekolah untuk informasi kegiatan yang tersedia.</p>
                </div>
            @endforelse
        </div>

        {{-- Info Box --}}
        <div class="max-w-4xl mx-auto mt-16 bg-gradient-to-r from-primary-50 to-accent-50 rounded-card p-8 border-l-4 border-primary-600">
            <div class="flex items-start space-x-4">
                <svg class="w-10 h-10 text-primary-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Ketentuan Ekstrakurikuler</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><strong>Pramuka wajib</strong> diikuti semua siswa kelas 7</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Siswa <strong>wajib memilih minimal 1 ekskul pilihan</strong> sesuai minat dan bakat</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Kehadiran ekskul <strong>minimal 75%</strong> untuk mendapat nilai</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Biaya ekskul <strong>sudah termasuk dalam SPP</strong> bulanan</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>
