@extends('layouts.spmb')

@section('title', 'Berita - ' . ($namaSekolah ?? 'Sekolah'))

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar')

        <section class="bg-kertas-200 pt-28 pb-20 sm:pt-32 sm:pb-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">
                <h1 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Berita
                </h1>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Berita dan artikel jurnalistik terbaru dari sekolah.
                </p>

                <form method="GET" action="{{ route('berita.index') }}" class="reveal mt-8 flex flex-wrap gap-3">
                    <input type="search" name="q" value="{{ request('q') }}"
                           placeholder="Cari judul atau ringkasan"
                           class="min-h-11 flex-1 rounded-md border border-kertas-300 bg-white px-4 py-2 text-sm">
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
                    <button type="submit" class="btn btn-quiet">Cari</button>
                </form>

                @if ($beritas->isEmpty())
                    <div class="reveal mt-10 rounded-md border border-kertas-300 bg-kertas-100 p-8">
                        <h2 class="font-display text-lg font-extrabold text-ink-900">Belum ada berita</h2>
                        <p class="mt-2 text-base leading-relaxed text-ink-600">
                            Belum ada berita yang diterbitkan. Silakan kembali
                            lagi nanti.
                        </p>
                    </div>
                @else
                    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($beritas as $b)
                            <article class="reveal rounded-md border border-kertas-300 bg-kertas-100 p-5">
                                <div class="text-sm text-ink-500">
                                    @if ($b->published_at)
                                        <time datetime="{{ $b->published_at->toDateString() }}">{{ $b->published_at->translatedFormat('j F Y') }}</time>
                                    @endif
                                    @if ($b->kategori)
                                        <span>· {{ $b->kategori->nama }}</span>
                                    @endif
                                </div>
                                <h2 class="mt-2 font-display text-lg font-extrabold leading-snug text-ink-900">
                                    <a href="{{ route('berita.show', $b) }}" class="hover:text-primary-800">{{ $b->judul }}</a>
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($b->ringkasan ?: $b->konten), 140) }}
                                </p>
                                @if ($b->tags->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($b->tags as $t)
                                            <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="text-xs text-primary-800 underline">{{ $t->nama }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $beritas->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection