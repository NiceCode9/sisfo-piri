@php
    // Dokumen dari database (dikelola via halaman admin Brosur); section
    // disembunyikan bila kosong, karena menampilkan "brosur segera hadir" untuk
    // dokumen yang memang belum ada sama saja tidak berguna.
    $dokumenUnduhan = ($brosurs ?? collect())
        ->filter(fn ($b) => ! empty($b->image_path))
        ->map(fn ($b) => [
            'title' => $b->title,
            'desc' => $b->desc,
            'image' => Storage::disk('public')->url($b->image_path),
        ])
        ->values();
@endphp

@if ($dokumenUnduhan->isNotEmpty())
    <section id="unduhan" class="bg-kertas-200 py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">

            {{-- Judul rata kiri, bukan di tengah. Section ini dibaca orang tua
                 yang sedang mencari satu file, bukan orang yang sedang
                 dijelajahi. --}}
            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Unduh Brosur &amp; Alur Pendaftaran
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Lihat langsung isinya di sini, atau unduh untuk disimpan
                    dan dibagikan ke keluarga lain.
                </p>
            </div>

            <ul class="mt-10 grid gap-6 sm:grid-cols-2">
                @foreach ($dokumenUnduhan as $dokumen)
                    <li class="reveal flex flex-col overflow-hidden rounded-md border border-kertas-300 bg-kertas-200">
                        <button type="button"
                                class="js-open-preview block aspect-[3/4] w-full bg-kertas-300"
                                aria-haspopup="dialog"
                                data-image="{{ $dokumen['image'] }}"
                                data-title="{{ $dokumen['title'] }}"
                                data-downloadable="true">
                            <img width="600" height="800" src="{{ $dokumen['image'] }}" alt="{{ $dokumen['title'] }}"
                                 class="h-full w-full object-cover"
                                 loading="lazy" decoding="async">
                            <span class="sr-only">Perbesar {{ $dokumen['title'] }}</span>
                        </button>

                        <div class="flex flex-1 flex-col justify-between gap-4 p-5">
                            <div class="min-w-0">
                                <h3 class="font-display text-base font-extrabold text-ink-900">{{ $dokumen['title'] }}</h3>
                                @if ($dokumen['desc'])
                                    <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $dokumen['desc'] }}</p>
                                @endif
                            </div>
                            <a href="{{ $dokumen['image'] }}" download
                               class="btn btn-primary self-start">
                                Unduh Berkas
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

    </section>
@endif
