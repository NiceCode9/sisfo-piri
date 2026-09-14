{{-- TODO: ganti dengan data asli alur pendaftaran --}}

<section id="alur" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Alur <span class="text-primary-600">Pendaftaran</span>
            </h2>
            <p class="text-lg text-gray-600">
                Ikuti 6 langkah mudah untuk menjadi bagian dari keluarga besar kami
            </p>
        </div>

        {{-- Timeline Desktop (Horizontal) --}}
        <div class="hidden lg:block">
            <div class="relative">
                {{-- Connection Line --}}
                <div class="absolute top-16 left-0 right-0 h-1 bg-gradient-to-r from-primary-200 via-secondary-200 to-accent-200"></div>
                
                <div class="grid grid-cols-6 gap-4 relative">
                    
                    {{-- Step 1 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-primary-600 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">1</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Buat Akun</h3>
                        <p class="text-sm text-gray-600">Registrasi akun calon murid di website</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-secondary-500 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">2</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Isi Formulir</h3>
                        <p class="text-sm text-gray-600">Lengkapi data diri dan pilih jalur</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-accent-500 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">3</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Upload Dokumen</h3>
                        <p class="text-sm text-gray-600">Upload semua berkas persyaratan</p>
                    </div>

                    {{-- Step 4 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-purple-600 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">4</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Bayar Biaya</h3>
                        <p class="text-sm text-gray-600">Transfer biaya pendaftaran</p>
                    </div>

                    {{-- Step 5 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-pink-600 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">5</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Tes Seleksi</h3>
                        <p class="text-sm text-gray-600">Ikuti ujian dan wawancara</p>
                    </div>

                    {{-- Step 6 --}}
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-primary-600 to-accent-600 rounded-3xl shadow-xl mb-4 transform hover:scale-110 transition">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-white mb-1">6</div>
                                <svg class="w-10 h-10 text-white mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Pengumuman</h3>
                        <p class="text-sm text-gray-600">Cek hasil dan daftar ulang</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Timeline Mobile (Vertical) --}}
        <div class="lg:hidden space-y-6">
            
            {{-- Step 1 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">1</div>
                    </div>
                </div>
                <div class="flex-1 bg-primary-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Buat Akun
                    </h3>
                    <p class="text-sm text-gray-700">Registrasi akun calon murid di website dengan email dan password</p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-secondary-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">2</div>
                    </div>
                </div>
                <div class="flex-1 bg-secondary-50 rounded-2xl p-5 border-l-4 border-secondary-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-secondary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Isi Formulir
                    </h3>
                    <p class="text-sm text-gray-700">Lengkapi data diri, data orang tua, dan pilih jalur pendaftaran</p>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-accent-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">3</div>
                    </div>
                </div>
                <div class="flex-1 bg-accent-50 rounded-2xl p-5 border-l-4 border-accent-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-accent-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Upload Dokumen
                    </h3>
                    <p class="text-sm text-gray-700">Upload semua berkas persyaratan dalam format PDF/JPG (max 2MB)</p>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">4</div>
                    </div>
                </div>
                <div class="flex-1 bg-purple-50 rounded-2xl p-5 border-l-4 border-purple-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Bayar Biaya Pendaftaran
                    </h3>
                    <p class="text-sm text-gray-700">Transfer biaya pendaftaran dan upload bukti pembayaran</p>
                </div>
            </div>

            {{-- Step 5 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-pink-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">5</div>
                    </div>
                </div>
                <div class="flex-1 bg-pink-50 rounded-2xl p-5 border-l-4 border-pink-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-pink-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Tes Seleksi
                    </h3>
                    <p class="text-sm text-gray-700">Ikuti ujian tulis, tes psikologi, dan wawancara sesuai jadwal</p>
                </div>
            </div>

            {{-- Step 6 --}}
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-primary-600 to-accent-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">6</div>
                    </div>
                </div>
                <div class="flex-1 bg-gradient-to-br from-primary-50 to-accent-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center">
                        <svg class="w-5 h-5 text-primary-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pengumuman
                    </h3>
                    <p class="text-sm text-gray-700">Cek hasil seleksi online dan lakukan daftar ulang jika diterima</p>
                </div>
            </div>

        </div>

        {{-- CTA --}}
        <div class="text-center mt-16">
            <div class="inline-block bg-gradient-to-r from-primary-100 to-secondary-100 rounded-3xl p-8 shadow-lg">
                <h3 class="text-xl md:text-2xl font-bold text-gray-800 mb-3">Siap Memulai?</h3>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">Klik tombol di bawah untuk membuat akun dan mulai proses pendaftaran sekarang!</p>
                <a href="#" class="inline-flex items-center px-8 py-4 text-lg font-bold text-white bg-primary-600 rounded-2xl hover:bg-primary-700 transition shadow-xl hover:shadow-2xl hover:scale-105 transform">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    Mulai Daftar Sekarang
                </a>
            </div>
        </div>

    </div>
</section>
