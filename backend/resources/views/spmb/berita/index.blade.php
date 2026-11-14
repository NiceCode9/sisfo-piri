@extends('layouts.spmb')

@section('title', 'Berita - ' . ($namaSekolah ?? 'Sekolah'))
@section('meta_description', 'Berita dan artikel jurnalistik terbaru dari ' . ($namaSekolah ?? 'Sekolah') . '.')

@section('content')
<main id="konten-utama">
    @include('spmb.partials.navbar')

    <section class="bg-kertas-200 pt-28 pb-12 sm:pt-32">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <h1 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                        Berita <span class="text-primary-700">&amp; Artikel</span>
                    </h1>
                    <p class="reveal mt-4 text-base leading-relaxed text-ink-600">
                        Kumpulan berita, pengumuman, dan artikel jurnalistik dari {{ $namaSekolah ?? 'Sekolah' }}.
                    </p>
                </div>
            </div>

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('berita.index') }}" class="reveal mt-8 flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[220px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-ink-400 text-sm"></i>
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Cari judul atau ringkasan..."
                           class="min-h-11 w-full rounded-md border border-kertas-300 bg-white pl-10 pr-4 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <select name="kategori" class="min-h-11 rounded-md border border-kertas-300 bg-white px-4 py-2 text-sm">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoris as $k)
                        <option value="{{ $k->slug }}" @selected(request('kategori') === $k->slug)>{{ $k->nama }}</option>
                    @endforeach
                </select>
                <select name="tag" class="min-h-11 rounded-md border border-kertas-300 bg-white px-4 py-2 text-sm">
                    <option value="">Semua tag</option>
                    @foreach ($tags as $t)
                        <option value="{{ $t->slug }}" @selected(request('tag') === $t->slug)>{{ $t->nama }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-accent min-h-11">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['q','kategori','tag']))
                    <a href="{{ route('berita.index') }}" class="btn btn-quiet min-h-11">
                        <i class="fa-solid fa-xmark mr-1"></i> Reset
                    </a>
                @endif
            </form>
        </div>
    </section>

    <section class="bg-kertas-100 py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            @if ($beritas->isEmpty())
                <div class="reveal rounded-md border border-kertas-300 bg-kertas-200 p-10 text-center">
                    <i class="fa-regular fa-newspaper text-4xl text-ink-400"></i>
                    <h2 class="mt-4 font-display text-xl font-extrabold text-ink-900">Belum ada berita</h2>
                    <p class="mt-2 text-base leading-relaxed text-ink-600">
                        Belum ada berita yang diterbitkan. Silakan kembali lagi nanti.
                    </p>
                </div>
            @else
                <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
                    {{-- Main content --}}
                    <div>
                        {{-- Featured (first article) --}}
                        @php $featured = $beritas->first(); @endphp
                        @if($featured && $beritas->currentPage() === 1 && !request()->hasAny(['q','kategori','tag']))
                            <article class="reveal group mb-10 overflow-hidden rounded-2xl border border-kertas-300 bg-white shadow-float transition-shadow hover:shadow-lg">
                                <a href="{{ route('berita.show', $featured) }}" class="block">
                                    @if($featured->gambar_utama)
                                        <div class="aspect-[16/9] w-full overflow-hidden bg-kertas-200">
                                            <img src="{{ asset('storage/' . $featured->gambar_utama) }}" alt="{{ $featured->judul }}"
                                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                        </div>
                                    @endif
                                    <div class="p-6 sm:p-8">
                                        <div class="flex flex-wrap items-center gap-3 text-sm text-ink-500">
                                            @if($featured->published_at)
                                                <time datetime="{{ $featured->published_at->toDateString() }}" class="font-semibold text-secondary-700">
                                                    {{ $featured->published_at->translatedFormat('j F Y') }}
                                                </time>
                                            @endif
                                            @if($featured->kategori)
                                                <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800">{{ $featured->kategori->nama }}</span>
                                            @endif
                                            <span class="text-ink-400"><i class="fa-regular fa-eye mr-1"></i>{{ number_format($featured->views_count) }}</span>
                                        </div>
                                        <h2 class="mt-3 font-display text-2xl font-extrabold leading-snug text-ink-900 sm:text-3xl group-hover:text-primary-800">
                                            {{ $featured->judul }}
                                        </h2>
                                        <p class="mt-3 text-base leading-relaxed text-ink-600">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($featured->ringkasan ?: $featured->konten), 220) }}
                                        </p>
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary-800">
                                            Baca selengkapnya <i class="fa-solid fa-arrow-right"></i>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        @endif

                        {{-- Grid --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            @foreach ($beritas as $b)
                                @if($featured && $beritas->currentPage() === 1 && !request()->hasAny(['q','kategori','tag']) && $b->id === $featured->id)
                                    @continue
                                @endif
                                <article class="reveal group flex flex-col overflow-hidden rounded-xl border border-kertas-300 bg-white transition-shadow hover:shadow-float">
                                    <a href="{{ route('berita.show', $b) }}" class="block">
                                        @if($b->gambar_utama)
                                            <div class="aspect-[16/10] w-full overflow-hidden bg-kertas-200">
                                                <img src="{{ asset('storage/' . $b->gambar_utama) }}" alt="{{ $b->judul }}"
                                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                            </div>
                                        @else
                                            <div class="aspect-[16/10] w-full bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center">
                                                <i class="fa-regular fa-newspaper text-4xl text-primary-300"></i>
                                            </div>
                                        @endif
                                        <div class="flex flex-1 flex-col p-5">
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
                                                @if($b->published_at)
                                                    <time datetime="{{ $b->published_at->toDateString() }}">{{ $b->published_at->translatedFormat('j M Y') }}</time>
                                                @endif
                                                @if($b->kategori)
                                                    <span class="rounded-full bg-primary-50 px-2 py-0.5 font-semibold text-primary-800">{{ $b->kategori->nama }}</span>
                                                @endif
                                            </div>
                                            <h3 class="mt-2 font-display text-base font-extrabold leading-snug text-ink-900 group-hover:text-primary-800">
                                                {{ $b->judul }}
                                            </h3>
                                            <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-600">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($b->ringkasan ?: $b->konten), 110) }}
                                            </p>
                                            @if($b->tags->isNotEmpty())
                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    @foreach($b->tags->take(3) as $t)
                                                        <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="text-xs text-primary-700 hover:underline">#{{ $t->nama }}</a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-10">
                            {{ $beritas->links() }}
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                        {{-- Kategori --}}
                        <div class="rounded-xl border border-kertas-300 bg-white p-5">
                            <h3 class="font-display text-base font-extrabold text-ink-900">Kategori</h3>
                            <ul class="mt-3 space-y-1">
                                @foreach($kategoris as $k)
                                    <li>
                                        <a href="{{ route('berita.index', ['kategori' => $k->slug]) }}"
                                           class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm text-ink-700 hover:bg-primary-50 hover:text-primary-800 {{ request('kategori') === $k->slug ? 'bg-primary-50 text-primary-800 font-semibold' : '' }}">
                                            <span>{{ $k->nama }}</span>
                                            <span class="text-xs text-ink-400">{{ $k->beritas_count ?? '' }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Tags --}}
                        @if($tags->isNotEmpty())
                            <div class="rounded-xl border border-kertas-300 bg-white p-5">
                                <h3 class="font-display text-base font-extrabold text-ink-900">Tag</h3>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($tags as $t)
                                        <a href="{{ route('berita.index', ['tag' => $t->slug]) }}"
                                           class="rounded-full border border-kertas-300 px-3 py-1 text-xs text-ink-700 hover:border-primary-300 hover:text-primary-800 {{ request('tag') === $t->slug ? 'border-primary-300 bg-primary-50 text-primary-800' : '' }}">
                                            #{{ $t->nama }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Populer --}}
                        @if(isset($populer) && $populer->isNotEmpty())
                            <div class="rounded-xl border border-kertas-300 bg-white p-5">
                                <h3 class="font-display text-base font-extrabold text-ink-900">Terpopuler</h3>
                                <ul class="mt-3 space-y-3">
                                    @foreach($populer as $p)
                                        <li>
                                            <a href="{{ route('berita.show', $p) }}" class="group block">
                                                <span class="text-sm font-semibold text-ink-800 group-hover:text-primary-800">{{ \Illuminate\Support\Str::limit($p->judul, 60) }}</span>
                                                <span class="mt-0.5 block text-xs text-ink-400"><i class="fa-regular fa-eye mr-1"></i>{{ number_format($p->views_count) }} views</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </aside>
                </div>
            @endif
        </div>
    </section>

    @include('spmb.partials.footer')
</main>
@endsection