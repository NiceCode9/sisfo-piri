{{-- TODO: ganti dengan foto asli kegiatan sekolah --}}

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

        {{-- Gallery Grid with CSS Scroll-Snap --}}
        <div class="max-w-6xl mx-auto">
            
            {{-- Desktop Grid --}}
            <div class="hidden md:grid grid-cols-4 gap-4 mb-8">
                
                {{-- Large Image 1 --}}
                <div class="col-span-2 row-span-2 group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    {{-- ganti dengan foto asli sekolah --}}
                    <img src="https://via.placeholder.com/600x600?text=Kegiatan+Belajar" 
                         alt="Kegiatan Belajar" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                            <h3 class="text-xl font-bold mb-1">Suasana Belajar</h3>
                            <p class="text-sm text-white/90">Kelas digital interaktif dengan teknologi modern</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 1 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Laboratorium" 
                         alt="Lab Komputer" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Lab Komputer</h3>
                            <p class="text-xs text-white/90">Fasilitas lengkap</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 2 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Perpustakaan" 
                         alt="Perpustakaan" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Perpustakaan</h3>
                            <p class="text-xs text-white/90">Koleksi buku lengkap</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 3 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Olahraga" 
                         alt="Lapangan Olahraga" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Lapangan Olahraga</h3>
                            <p class="text-xs text-white/90">Futsal & basket</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 4 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Ekstrakurikuler" 
                         alt="Ekstrakurikuler" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Ekstrakurikuler</h3>
                            <p class="text-xs text-white/90">Beragam pilihan</p>
                        </div>
                    </div>
                </div>

                {{-- Large Image 2 --}}
                <div class="col-span-2 row-span-2 group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/600x600?text=Prestasi+Siswa" 
                         alt="Prestasi Siswa" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                            <h3 class="text-xl font-bold mb-1">Prestasi Siswa</h3>
                            <p class="text-sm text-white/90">Juara olimpiade sains tingkat nasional</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 5 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Upacara" 
                         alt="Upacara Bendera" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Upacara Bendera</h3>
                            <p class="text-xs text-white/90">Pembinaan karakter</p>
                        </div>
                    </div>
                </div>

                {{-- Small Image 6 --}}
                <div class="group relative overflow-hidden rounded-3xl shadow-lg card-hover">
                    <img src="https://via.placeholder.com/300x300?text=Study+Tour" 
                         alt="Study Tour" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="font-bold mb-1">Study Tour</h3>
                            <p class="text-xs text-white/90">Belajar di luar kelas</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Mobile Carousel with Scroll-Snap --}}
            <div class="md:hidden overflow-x-auto snap-x snap-mandatory scrollbar-hide mb-8" style="scroll-behavior: smooth;">
                <div class="flex space-x-4 pb-4">
                    
                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Kegiatan+Belajar" 
                             alt="Kegiatan Belajar" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Suasana Belajar</h3>
                            <p class="text-sm text-white/90">Kelas digital interaktif</p>
                        </div>
                    </div>

                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Lab+Komputer" 
                             alt="Lab Komputer" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Lab Komputer</h3>
                            <p class="text-sm text-white/90">Fasilitas lengkap</p>
                        </div>
                    </div>

                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Perpustakaan" 
                             alt="Perpustakaan" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Perpustakaan</h3>
                            <p class="text-sm text-white/90">Koleksi buku lengkap</p>
                        </div>
                    </div>

                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Prestasi+Siswa" 
                             alt="Prestasi Siswa" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Prestasi Siswa</h3>
                            <p class="text-sm text-white/90">Juara olimpiade nasional</p>
                        </div>
                    </div>

                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Ekstrakurikuler" 
                             alt="Ekstrakurikuler" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Ekstrakurikuler</h3>
                            <p class="text-sm text-white/90">Beragam pilihan</p>
                        </div>
                    </div>

                    <div class="snap-center flex-shrink-0 w-80 group relative overflow-hidden rounded-3xl shadow-lg">
                        <img src="https://via.placeholder.com/320x400?text=Study+Tour" 
                             alt="Study Tour" 
                             class="w-full h-96 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white">
                            <h3 class="text-lg font-bold mb-1">Study Tour</h3>
                            <p class="text-sm text-white/90">Belajar di luar kelas</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Prestasi Highlights --}}
        <div class="max-w-5xl mx-auto mt-16">
            <h3 class="text-2xl font-bold text-center text-gray-800 mb-8">Prestasi Terbaru</h3>
            
            <div class="grid md:grid-cols-3 gap-6">
                
                <div class="bg-gradient-to-br from-primary-50 to-primary-100 rounded-2xl p-6 border-l-4 border-primary-600">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Agustus 2026</div>
                            <div class="font-bold text-gray-800">Juara 1</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700">Olimpiade Sains Nasional (OSN) Tingkat Provinsi - Bidang Matematika</p>
                </div>

                <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 rounded-2xl p-6 border-l-4 border-secondary-600">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-secondary-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Juli 2026</div>
                            <div class="font-bold text-gray-800">Juara 2</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700">Kompetisi Futsal Antar SMP Se-Kabupaten</p>
                </div>

                <div class="bg-gradient-to-br from-accent-50 to-accent-100 rounded-2xl p-6 border-l-4 border-accent-600">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-accent-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Juni 2026</div>
                            <div class="font-bold text-gray-800">Best Performance</div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700">Festival Tari Tradisional Tingkat Kota</p>
                </div>

            </div>
        </div>

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
