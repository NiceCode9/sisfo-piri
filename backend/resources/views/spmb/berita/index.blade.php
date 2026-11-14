@extends('layouts.spmb')

@section('title', 'Berita - ' . ($namaSekolah ?? 'Sekolah'))
@section('meta_description', 'Berita dan artikel jurnalistik terbaru dari ' . ($namaSekolah ?? 'Sekolah') . '.')

@section('content')
<main id="konten-utama">
    @include('spmb.partials.navbar')

    <section class="bg-kertas-200 pt-28 pb-10 sm:pt-32">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="reveal font-display text-2xl font-extrabold leading-tight text-ink-900 sm:text-3xl">
                        Berita <span class="text-primary-700">&amp; Artikel</span>
                    </h1>
                    <p class="reveal mt-2 text-sm leading-relaxed text-ink-600">
                        Kumpulan berita, pengumuman, dan artikel jurnalistik dari {{ $namaSekolah ?? 'Sekolah' }}.
                    </p>
                </div>
                <a href="{{ route('berita.index') }}" class="btn btn-quiet text-sm">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Semua
                </a>
            </div>
        </div>
    </section>

    <section class="bg-kertas-100 py-10 sm:py-14">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            @if ($beritas->isEmpty())
                <div class="reveal rounded-md border border-kertas-300 bg-kertas-200 p-10 text-center">
                    <i class="fa-regular fa-newspaper text-3xl text-ink-400"></i>
                    <h2 class="mt-3 font-display text-lg font-extrabold text-ink-900">Belum ada berita</h2>
                    <p class="mt-1 text-sm leading-relaxed text-ink-600">
                        Belum ada berita yang diterbitkan. Silakan kembali lagi nanti.
                    </p>
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
                    {{-- Sidebar filter --}}
                    <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                        <form method="GET" action="{{ route('berita.index') }}" class="rounded-xl border border-kertas-300 bg-white p-4">
                            <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Filter</h3>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-ink-600">Cari</label>
                                    <div class="relative mt-1">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-ink-400 text-xs"></i>
                                        <input type="search" name="q" value="{{ request('q') }}"
                                               placeholder="Judul..."
                                               class="min-h-9 w-full rounded-md border border-kertas-300 bg-kertas-50 pl-9 pr-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-ink-600">Kategori</label>
                                    <select name="kategori" class="mt-1 min-h-9 w-full rounded-md border border-kertas-300 bg-kertas-50 px-3 py-1.5 text-sm">
                                        <option value="">Semua</option>
                                        @foreach ($kategoris as $k)
                                            <option value="{{ $k->slug }}" @selected(request('kategori') === $k->slug)>{{ $k->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-ink-600">Tag</label>
                                    <select name="tag" class="mt-1 min-h-9 w-full rounded-md border border-kertas-300 bg-kertas-50 px-3 py-1.5 text-sm">
                                        <option value="">Semua</option>
                                        @foreach ($tags as $t)
                                            <option value="{{ $t->slug }}" @selected(request('tag') === $t->slug)>{{ $t->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn btn-accent min-h-9 flex-1 text-sm">Filter</button>
                                    @if(request()->hasAny(['q','kategori','tag']))
                                        <a href="{{ route('berita.index') }}" class="btn btn-quiet min-h-9 text-sm">Reset</a>
                                    @endif
                                </div>
                            </div>
                        </form>

                        {{-- Kategori list --}}
                        <div class="rounded-xl border border-kertas-300 bg-white p-4">
                            <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Kategori</h3>
                            <ul class="mt-2 space-y-0.5">
                                @foreach($kategoris as $k)
                                    <li>
                                        <a href="{{ route('berita.index', ['kategori' => $k->slug]) }}"
                                           class="flex items-center justify-between rounded-md px-2 py-1 text-sm text-ink-700 hover:bg-primary-50 hover:text-primary-800 {{ request('kategori') === $k->slug ? 'bg-primary-50 font-semibold text-primary-800' : '' }}">
                                            <span>{{ $k->nama }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Tags --}}
                        @if($tags->isNotEmpty())
                            <div class="rounded-xl border border-kertas-300 bg-white p-4">
                                <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Tag</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($tags as $t)
                                        <a href="{{ route('berita.index', ['tag' => $t->slug]) }}"
                                           class="rounded-full border border-kertas-300 px-2.5 py-0.5 text-xs text-ink-700 hover:border-primary-300 hover:text-primary-800 {{ request('tag') === $t->slug ? 'border-primary-300 bg-primary-50 text-primary-800' : '' }}">
                                            #{{ $t->nama }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Populer --}}
                        @if(isset($populer) && $populer->isNotEmpty())
                            <div class="rounded-xl border border-kertas-300 bg-white p-4">
                                <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Terpopuler</h3>
                                <ul class="mt-2 space-y-2">
                                    @foreach($populer as $p)
                                        <li>
                                            <a href="{{ route('berita.show', $p) }}" class="group block">
                                                <span class="text-sm font-medium text-ink-800 group-hover:text-primary-800 leading-snug">{{ \Illuminate\Support\Str::limit($p->judul, 55) }}</span>
                                                <span class="mt-0.5 block text-xs text-ink-400"><i class="fa-regular fa-eye mr-1"></i>{{ number_format($p->views_count) }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </aside>

                    {{-- Main: article list --}}
                    <div class="space-y-4">
                        {{-- Featured (only on first page, no filter) --}}
                        @php $featured = $beritas->first(); @endphp
                        @if($featured && $beritas->currentPage() === 1 && !request()->hasAny(['q','kategori','tag']))
                            <article class="reveal group overflow-hidden rounded-xl border border-kertas-300 bg-white transition-shadow hover:shadow-float">
                                <a href="{{ route('berita.show', $featured) }}" class="flex flex-col sm:flex-row">
                                    @if($featured->gambar_utama)
                                        <div class="sm:w-2/5 shrink-0 overflow-hidden bg-kertas-200">
                                            <img src="{{ asset('storage/' . $featured->gambar_utama) }}" alt="{{ $featured->judul }}"
                                                 class="h-48 w-full object-cover transition-transform duration-500 group-hover:scale-105 sm:h-full">
                                        </div>
                                    @endif
                                    <div class="flex flex-1 flex-col p-5">
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
                                            @if($featured->published_at)
                                                <time datetime="{{ $featured->published_at->toDateString() }}" class="font-semibold text-secondary-700">{{ $featured->published_at->translatedFormat('j F Y') }}</time>
                                            @endif
                                            @if($featured->kategori)
                                                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 font-semibold text-primary-800">{{ $featured->kategori->nama }}</span>
                                            @endif
                                        </div>
                                        <h2 class="mt-2 font-display text-lg font-extrabold leading-snug text-ink-900 group-hover:text-primary-800 sm:text-xl">
                                            {{ $featured->judul }}
                                        </h2>
                                        <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-600">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($featured->ringkasan ?: $featured->konten), 160) }}
                                        </p>
                                        <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-800">
                                            Baca <i class="fa-solid fa-arrow-right text-xs"></i>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        @endif

                        {{-- Article list (compact horizontal cards) --}}
                        @foreach ($beritas as $b)
                            @if($featured && $beritas->currentPage() === 1 && !request()->hasAny(['q','kategori','tag']) && $b->id === $featured->id)
                                @continue
                            @endif
                            <article class="reveal group overflow-hidden rounded-xl border border-kertas-300 bg-white transition-shadow hover:shadow-float">
                                <a href="{{ route('berita.show', $b) }}" class="flex gap-4 p-4">
                                    @if($b->gambar_utama)
                                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-lg bg-kertas-200 sm:h-28 sm:w-28">
                                            <img src="{{ asset('storage/' . $b->gambar_utama) }}" alt="{{ $b->judul }}"
                                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                        </div>
                                    @else
                                        <div class="grid h-24 w-24 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-primary-100 to-primary-50 sm:h-28 sm:w-28">
                                            <i class="fa-regular fa-newspaper text-2xl text-primary-300"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
                                            @if($b->published_at)
                                                <time datetime="{{ $b->published_at->toDateString() }}">{{ $b->published_at->translatedFormat('j M Y') }}</time>
                                            @endif
                                            @if($b->kategori)
                                                <span class="rounded-full bg-primary-50 px-2 py-0.5 font-semibold text-primary-800">{{ $b->kategori->nama }}</span>
                                            @endif
                                        </div>
                                        <h3 class="mt-1 font-display text-base font-extrabold leading-snug text-ink-900 group-hover:text-primary-800">
                                            {{ $b->judul }}
                                        </h3>
                                        <p class="mt-1 text-sm leading-relaxed text-ink-600 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($b->ringkasan ?: $b->konten), 120) }}
                                        </p>
                                        @if($b->tags->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($b->tags->take(3) as $t)
                                                    <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="text-xs text-primary-700 hover:underline">#{{ $t->nama }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            </article>
                        @endforeach

                        <div class="mt-6">
                            {{ $beritas->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @include('spmb.partials.footer')
</main>
@endsection