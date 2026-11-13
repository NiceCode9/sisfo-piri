<section id="berita" class="bg-kertas-100 py-24 sm:py-32">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Berita <span class="text-primary-700">Terbaru</span>
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Berita dan artikel jurnalistik terbaru dari sekolah.
                </p>
            </div>
            @if (collect($beritaTerbaru ?? [])->isNotEmpty())
                <a href="{{ route('berita.index') }}" class="btn btn-quiet">Lihat Semua Berita</a>
            @endif
        </div>

        @forelse (collect($beritaTerbaru ?? []) as $b)
            <article class="reveal mt-6 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-t border-kertas-300 py-5">
                <div class="min-w-0 flex-1">
                    <div class="text-sm text-ink-500">
                        @if ($b->published_at)
                            <time datetime="{{ $b->published_at->toDateString() }}">{{ $b->published_at->translatedFormat('j M Y') }}</time>
                        @endif
                        @if ($b->kategori)
                            <span>· {{ $b->kategori->nama }}</span>
                        @endif
                    </div>
                    <h3 class="mt-1.5 font-display text-lg font-extrabold text-ink-900">{{ $b->judul }}</h3>
                    <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-ink-600">
                        {{ \Illuminate\Support\Str::limit(strip_tags($b->ringkasan ?: $b->konten), 140) }}
                    </p>
                </div>
                <a href="{{ route('berita.show', $b) }}"
                   class="inline-flex min-h-11 shrink-0 items-center font-semibold text-primary-800 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                    Baca
                </a>
            </article>
        @empty
            <div class="reveal mt-10 max-w-xl rounded-md border border-kertas-300 bg-kertas-200 p-8">
                <h3 class="font-display text-lg font-extrabold text-ink-900">Belum ada berita</h3>
                <p class="mt-2 text-base leading-relaxed text-ink-600">
                    Berita terbaru dari sekolah akan muncul di sini.
                </p>
            </div>
        @endforelse
    </div>
</section>