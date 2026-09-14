@php
    // Data dari database (dikelola via halaman admin Galeri).
    $fotos = ($galeriFotos ?? collect())->values();
    $daftarPrestasi = ($prestasis ?? collect())->values();
    $gradients = [
        ['from-primary-50 to-primary-100', 'border-primary-600', 'bg-primary-600'],
        ['from-secondary-50 to-secondary-100', 'border-secondary-600', 'bg-secondary-600'],
        ['from-accent-50 to-accent-100', 'border-accent-600', 'bg-accent-600'],
    ];
@endphp

@if ($fotos->isNotEmpty() || $daftarPrestasi->isNotEmpty())
<section id="galeri" class="py-20 bg-white">
    <div class="container mx-auto px-4">

        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Galeri & <span class="text-primary-600">Prestasi</span>
            </h2>
            <p class="text-lg text-gray-600">
                Lihat momen-momen berharga dan prestasi membanggakan siswa kami
            </p>
        </div>

        @if ($fotos->isNotEmpty())
        {{-- Gallery Grid with CSS Scroll-Snap --}}
        <div class="max-w-6xl mx-auto">

            {{-- Desktop Grid: item ke-1 & ke-6 tampil besar --}}
            <div class="hidden md:grid grid-cols-4 gap-4 mb-8">
                @foreach ($fotos as $foto)
                    @php
                        $besar = in_array($loop->index, [0, 5]);
                        $imgUrl = Storage::disk('public')->url($foto->image_path);
                    @endphp
                    <div class="{{ $besar ? 'col-span-2 row-span-2' : '' }} group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                        <img src="{{ $imgUrl }}"
                             alt="{{ $foto->title }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                            <div class="absolute bottom-0 left-0 right-0 {{ $besar ? 'p-6' : 'p-4' }} text-white">
                                <h3 class="{{ $besar ? 'text-xl' : '' }} font-bold mb-1">{{ $foto->title }}</h3>
                                @if ($foto->desc)
                                    <p class="{{ $besar ? 'text-sm' : 'text-xs' }} text-white/90">{{ $foto->desc }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mobile Carousel with Scroll-Snap --}}
            <div class="md:hidden overflow-x-auto snap-x snap-mandatory scrollbar-hide mb-8" style="scroll-behavior: smooth;">
                <div class="flex space-x-4 pb-4">
                    @foreach ($fotos as $foto)
                        <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                            <img src="{{ Storage::disk('public')->url($foto->image_path) }}"
                                 alt="{{ $foto->title }}"
                                 class="w-full h-96 object-cover">
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                                <h3 class="text-lg font-bold mb-1">{{ $foto->title }}</h3>
                                @if ($foto->desc)
                                    <p class="text-sm text-white/90">{{ $foto->desc }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
        @endif

        @if ($daftarPrestasi->isNotEmpty())
        {{-- Prestasi Highlights --}}
        <div class="max-w-5xl mx-auto mt-16">
            <h3 class="text-2xl font-bold text-center text-gray-800 mb-8">Prestasi Terbaru</h3>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($daftarPrestasi as $prestasi)
                    @php $grad = $gradients[$loop->index % count($gradients)]; @endphp
                    <div class="bg-gradient-to-br {{ $grad[0] }} rounded-2xl p-6 border-l-4 {{ $grad[1] }}">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-12 h-12 {{ $grad[2] }} rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">{{ $prestasi->tanggal ? \Carbon\Carbon::parse($prestasi->tanggal)->translatedFormat('F Y') : '-' }}</div>
                                <div class="font-bold text-gray-800">{{ $prestasi->title }}</div>
                            </div>
                        </div>
                        @if ($prestasi->desc)
                            <p class="text-sm text-gray-700">{{ $prestasi->desc }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</section>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endif
