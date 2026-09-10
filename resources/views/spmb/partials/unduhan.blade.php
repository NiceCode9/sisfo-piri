@php
    // TODO: taruh gambar brosur/alur pendaftaran asli di /public/images/,
    // lalu sesuaikan path di bawah. Format JPG/PNG (atau WebP), bukan PDF,
    // karena harus bisa ditampilkan langsung sebagai gambar di halaman.
    $dokumenUnduhan = [
        [
            'title' => 'Brosur SPMB',
            'desc' => 'Info lengkap program, keunggulan, dan biaya pendidikan',
            'image' => asset('0001.jpg'),
        ],
        [
            'title' => 'Alur Pendaftaran',
            'desc' => 'Panduan langkah demi langkah proses pendaftaran',
            'image' => asset('0002.jpg'),
        ],
    ];
@endphp

<section id="unduhan" class="py-20 bg-slate-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1.5 bg-primary-100 text-primary-700 rounded-full text-sm font-semibold mb-4">Unduh</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Brosur &amp; Alur Pendaftaran</h2>
            <p class="text-lg text-gray-600 max-w-xl mx-auto">Lihat langsung isinya di sini, atau unduh untuk disimpan dan dibagikan</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-8">
            @foreach ($dokumenUnduhan as $dokumen)
                <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden card-hover">
                    {{-- Preview gambar — klik untuk perbesar --}}
                    <button type="button"
                            class="js-open-preview relative block w-full aspect-[3/4] bg-gray-100 group overflow-hidden"
                            data-image="{{ $dokumen['image'] }}"
                            data-title="{{ $dokumen['title'] }}"
                            aria-label="Perbesar {{ $dokumen['title'] }}">
                        <img src="{{ $dokumen['image'] }}" alt="{{ $dokumen['title'] }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors duration-300 flex items-center justify-center">
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 px-4 py-2 bg-white/90 backdrop-blur-sm rounded-full text-sm font-semibold text-gray-800">
                                🔍 Lihat Penuh
                            </span>
                        </div>
                    </button>

                    <div class="p-5 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-800">{{ $dokumen['title'] }}</h3>
                            <p class="text-sm text-gray-500 truncate">{{ $dokumen['desc'] }}</p>
                        </div>
                        <a href="{{ $dokumen['image'] }}" download
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition shadow-md hover:shadow-lg flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Unduh
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Lightbox --}}
    <div id="preview-lightbox" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm items-center justify-center p-4">
        <button type="button" id="preview-close" aria-label="Tutup"
                class="absolute top-5 right-5 w-11 h-11 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="max-w-3xl w-full max-h-[85vh] flex flex-col items-center">
            <img id="preview-image" src="" alt="" class="max-h-[75vh] w-auto rounded-xl shadow-2xl object-contain">
            <div class="flex items-center gap-4 mt-4">
                <span id="preview-title" class="text-white font-semibold"></span>
                <a id="preview-download" href="" download
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-800 text-sm font-semibold rounded-xl hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Unduh
                </a>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const lightbox = document.getElementById('preview-lightbox');
        const previewImage = document.getElementById('preview-image');
        const previewTitle = document.getElementById('preview-title');
        const previewDownload = document.getElementById('preview-download');
        const closeBtn = document.getElementById('preview-close');

        document.querySelectorAll('.js-open-preview').forEach((btn) => {
            btn.addEventListener('click', () => {
                previewImage.src = btn.dataset.image;
                previewImage.alt = btn.dataset.title;
                previewTitle.textContent = btn.dataset.title;
                previewDownload.href = btn.dataset.image;
                lightbox.classList.remove('hidden');
                lightbox.classList.add('flex');
                document.body.style.overflow = 'hidden';
            });
        });

        function closePreview() {
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
            document.body.style.overflow = '';
        }

        closeBtn.addEventListener('click', closePreview);
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closePreview();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePreview();
        });
    })();
</script>
