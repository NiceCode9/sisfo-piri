{{-- TODO: ganti dengan data asli ekstrakurikuler --}}

<section id="ekskul" class="py-20 bg-slate-50">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Ekstrakurikuler & <span class="text-primary-600">Program Unggulan</span>
            </h2>
            <p class="text-lg text-gray-600">
                Kembangkan bakat dan minat dengan berbagai pilihan kegiatan menarik
            </p>
        </div>

        {{-- Grid Cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            
            {{-- Ekskul 1: Pramuka --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Pramuka</h3>
                <p class="text-sm text-gray-600 mb-4">Pembinaan karakter, kepemimpinan, dan kemandirian melalui kegiatan kepramukaan</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Setiap Jumat, 14:00-16:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-primary-100 text-primary-700 text-xs font-bold rounded-full">Wajib</span>
                </div>
            </div>

            {{-- Ekskul 2: Futsal --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-accent-500 to-accent-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Futsal</h3>
                <p class="text-sm text-gray-600 mb-4">Latihan teknik, strategi, dan kompetisi futsal tingkat pelajar regional</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Selasa & Kamis, 15:00-17:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-accent-100 text-accent-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 3: Basket --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-secondary-500 to-secondary-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Basket</h3>
                <p class="text-sm text-gray-600 mb-4">Melatih kerjasama tim, koordinasi, dan sportivitas melalui olahraga basket</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Rabu & Jumat, 15:00-17:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-secondary-100 text-secondary-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 4: English Club --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">English Club</h3>
                <p class="text-sm text-gray-600 mb-4">Praktik conversation, debate, dan persiapan lomba bahasa Inggris</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Senin & Rabu, 14:00-15:30</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 5: Robotika --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Robotika & Coding</h3>
                <p class="text-sm text-gray-600 mb-4">Belajar programming, elektronika, dan desain robot untuk kompetisi</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Kamis, 14:00-16:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-pink-100 text-pink-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 6: Seni Musik --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Seni Musik</h3>
                <p class="text-sm text-gray-600 mb-4">Vokal, band, keyboard, gitar, dan persiapan pentas seni sekolah</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Selasa & Jumat, 14:00-16:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 7: Tari Tradisional --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Tari Tradisional</h3>
                <p class="text-sm text-gray-600 mb-4">Melestarikan budaya nusantara melalui seni tari daerah dan modern</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Rabu, 14:00-16:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-rose-100 text-rose-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 8: KIR --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">KIR (Karya Ilmiah)</h3>
                <p class="text-sm text-gray-600 mb-4">Penelitian, eksperimen sains, dan kompetisi olimpiade sains nasional</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Senin & Kamis, 14:00-16:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-teal-100 text-teal-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

            {{-- Ekskul 9: Jurnalistik --}}
            <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-700 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Jurnalistik</h3>
                <p class="text-sm text-gray-600 mb-4">Menulis berita, fotografi, dan membuat media publikasi sekolah</p>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Rabu, 15:00-17:00</span>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-block px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">Pilihan</span>
                </div>
            </div>

        </div>

        {{-- Info Box --}}
        <div class="max-w-4xl mx-auto mt-16 bg-gradient-to-r from-primary-50 to-accent-50 rounded-3xl p-8 border-l-4 border-primary-600">
            <div class="flex items-start space-x-4">
                <svg class="w-10 h-10 text-primary-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Ketentuan Ekstrakurikuler</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><strong>Pramuka wajib</strong> diikuti semua siswa kelas 7</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Siswa <strong>wajib memilih minimal 1 ekskul pilihan</strong> sesuai minat dan bakat</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Kehadiran ekskul <strong>minimal 75%</strong> untuk mendapat nilai</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Biaya ekskul <strong>sudah termasuk dalam SPP</strong> bulanan</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>
