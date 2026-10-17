@php
    // Kartu dibaca dari tabel `ekstrakurikulers` (dikelola di
    // admin/ekstrakurikulers). Versi lama menulis sembilan kartu hardcoded
    // yang hampir tidak ada di database, sementara Paskibra, PMR, dan Rohani
    // Islam yang benar-benar ada tidak pernah disebut.

    // `kode` sudah berupa slug stabil, jadi ikon bisa dipetakan dari situ.
    // Kode yang tidak dikenal tetap dapat kartu, hanya dengan ikon generik.
    $ikonEkskul = [
        'PRAMUKA' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        'PASKIBRA' => 'M5 3v18M19 3v18M3 8h18M3 16h18',
        'FUTSAL' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a5 5 0 110-4h14a5 5 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
        'PMR' => 'M12 3v18M8 7h8M6 11h12M4 15h16M6 11v9a2 2 0 002 2h8a2 2 0 002-2v-9',
        'ROHIS' => 'M12 21s-7-4.5-7-10a4 4 0 017-2.6A4 4 0 0119 11c0 5.5-7 10-7 10z',
    ];

    $ekskulList = ($ekstrakurikulers ?? collect())->sortBy('nama')->values();

    // Ketentuan berikut masih hardcode: tabel ekstrakurikulers tidak punya
    // kolom kewajiban, kuota kehadiran, atau hubungan biaya dengan SPP.
    // Semuanya perlu dikonfirmasi sekolah dulu sebelum jadi aturan resmi.
    $ketentuanEkskul = [
        '<strong>Pramuka wajib</strong> diikuti semua siswa kelas 7',
        'Siswa <strong>wajib memilih minimal 1 ekskul pilihan</strong> sesuai minat bakat',
        'Kehadiran ekskul <strong>minimal 75%</strong> untuk mendapat nilai',
        'Biaya ekskul <strong>sudah termasuk dalam SPP</strong> bulanan',
    ];

    $centang = 'M5 13l4 4L19 7';
@endphp

<section id="ekskul" class="bg-kertas-200 py-20 sm:py-24">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="max-w-2xl">
            <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                Ekstrakurikuler &amp; <span class="text-primary-700">Program Unggulan</span>
            </h2>
            <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                Kembangkan bakat dan minat dengan berbagai pilihan kegiatan
                menarik.
            </p>
        </div>

        {{-- Jadwal ditulis paling depan karena itu yang pertama ditanyakan
             anak, bukan deskripsi. Daftar, bukan grid kartu: enam kegiatan
             tidak perlu jadi enam kotak besar yang sama persis.

             Siklus tujuh gradien (hijau, biru, emas, ungu, merah muda,
             indigo, merah) juga dihapus. Mengganti warna kartu sesuai urutan
             tidak menambah informasi apa pun (R-29). --}}
        @forelse ($ekskulList as $ekskul)
            @php
                $ikon = $ikonEkskul[strtoupper((string) $ekskul->kode)]
                    ?? 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2z';
            @endphp

            <article class="reveal mt-8 flex flex-col gap-5 border-t border-kertas-400 pt-8 sm:flex-row sm:items-start sm:gap-7">
                <span aria-hidden="true"
                      class="grid h-12 w-12 shrink-0 place-items-center rounded-md border border-primary-200 bg-primary-50 text-primary-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikon }}"/>
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="font-display text-lg font-extrabold text-ink-900">{{ $ekskul->nama }}</h3>

                    {{-- PMR dan Rohani Islam belum punya deskripsi, jadi blok ini
                         tidak boleh mengarang kalimat. --}}
                    @if ($ekskul->deskripsi)
                        <p class="mt-1.5 text-base leading-relaxed text-ink-600">{{ $ekskul->deskripsi }}</p>
                    @endif

                    @if ($ekskul->jadwal || $ekskul->pembina)
                        <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-1.5 text-sm">
                            @if ($ekskul->jadwal)
                                <div class="flex gap-2">
                                    <dt class="sr-only">Jadwal</dt>
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                         stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <dd class="text-ink-700">{{ $ekskul->jadwal }}</dd>
                                </div>
                            @endif
                            @if ($ekskul->pembina)
                                <div class="flex gap-2">
                                    <dt class="sr-only">Pembina</dt>
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                         stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <dd class="text-ink-700">Pembina: {{ $ekskul->pembina }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </div>
            </article>
        @empty
            {{-- Empty state (R-27): menyebut sebab dan jalan keluar. --}}
            <div class="reveal mt-8 max-w-xl rounded-md border border-kertas-300 bg-kertas-100 p-8">
                <h3 class="font-display text-lg font-extrabold text-ink-900">Belum ada kegiatan yang terdaftar</h3>
                <p class="mt-2 text-base leading-relaxed text-ink-600">
                    Sekolah belum memasukkan daftar kegiatan ke dalam sistem.
                    Tanya langsung ke sekolah untuk tahu kegiatan apa saja yang
                    sedang berjalan tahun ini.
                </p>
                @if ($profileSekolah?->whatsapp)
                    <a href="https://wa.me/{{ $profileSekolah->whatsapp }}" class="btn btn-quiet mt-6">
                        Tanya ke Sekolah
                    </a>
                @endif
            </div>
        @endforelse

        {{-- Ketentuan ekskul. --}}
        <div class="reveal mt-16 max-w-3xl rounded-lg border border-kertas-300 bg-kertas-100 p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-secondary-50 text-secondary-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.228 9c.549-1.165 2.03-2 3.772-2s3.223.835 3.772 2m-7.544 0a8.25 8.25 0 113.772 0M12 12.75h.008v.008H12v-.008zm.375 3h-.75v4.5h3v-3.75m-3-3h3v2.25H9.375z"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <h3 class="font-display text-lg font-extrabold text-ink-900">Ketentuan Ekstrakurikuler</h3>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($ketentuanEkskul as $ketentuan)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-ink-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                     stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $centang }}"/>
                                </svg>
                                <span>{!! $ketentuan !!}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
