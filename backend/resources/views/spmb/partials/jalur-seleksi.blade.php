{{-- TODO: ganti dengan data asli jalur seleksi --}}

<section id="jalur" class="py-20 bg-slate-50">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Jalur <span class="text-primary-600">Pendaftaran</span>
            </h2>
            <p class="text-lg text-gray-600">
                Pilih jalur pendaftaran yang sesuai dengan kemampuan dan prestasi Anda
            </p>
        </div>

        {{-- Tab Navigation --}}
        <div class="flex justify-center mb-12">
            <div class="inline-flex bg-white rounded-2xl p-2 shadow-lg">
                <button onclick="switchTab('reguler')" id="tab-reguler" class="tab-button active px-8 py-3 rounded-xl font-semibold text-sm transition">
                    Jalur Reguler
                </button>
                <button onclick="switchTab('prestasi')" id="tab-prestasi" class="tab-button px-8 py-3 rounded-xl font-semibold text-sm transition">
                    Jalur Prestasi/Beasiswa
                </button>
            </div>
        </div>

        {{-- Tab Content: Reguler --}}
        <div id="content-reguler" class="tab-content">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl p-8 md:p-12">
                <div class="flex items-start space-x-4 mb-8">
                    <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Jalur Reguler</h3>
                        <p class="text-gray-600">Jalur pendaftaran umum untuk semua calon siswa</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-secondary-500 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                            Persyaratan Umum
                        </h4>
                        <ul class="space-y-3 ml-11">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Siswa kelas 6 SD/MI atau sederajat</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Usia maksimal 15 tahun pada 1 Juli 2026</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Mengikuti tes seleksi akademik dan wawancara</span>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-secondary-500 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                            Dokumen yang Diperlukan
                        </h4>
                        <ul class="space-y-3 ml-11">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Fotocopy Akta Kelahiran (2 lembar)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Fotocopy Kartu Keluarga (2 lembar)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Rapor semester 1-5 SD/MI (upload PDF)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Pas foto berwarna 3x4 (3 lembar)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Surat Keterangan Sehat dari dokter</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-primary-50 rounded-2xl p-6 border-l-4 border-primary-600">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-primary-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h5 class="font-bold text-gray-800 mb-1">Catatan Penting</h5>
                                <p class="text-sm text-gray-700">Semua dokumen harus asli dan masih berlaku. Pastikan data yang diisi sesuai dengan dokumen resmi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Content: Prestasi --}}
        <div id="content-prestasi" class="tab-content hidden">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl p-8 md:p-12">
                <div class="flex items-start space-x-4 mb-8">
                    <div class="w-16 h-16 bg-secondary-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Jalur Prestasi/Beasiswa</h3>
                        <p class="text-gray-600">Untuk siswa berprestasi akademik atau non-akademik</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-secondary-500 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                            Persyaratan Khusus
                        </h4>
                        <ul class="space-y-3 ml-11">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Rata-rata rapor semester 1-5 minimal <strong>85</strong> (prestasi akademik)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Memiliki prestasi juara 1-3 tingkat Kabupaten/Kota atau lebih tinggi</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Prestasi diraih maksimal 2 tahun terakhir</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Bebas tes tulis, hanya wawancara dan verifikasi dokumen</span>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-secondary-500 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                            Dokumen Tambahan
                        </h4>
                        <ul class="space-y-3 ml-11">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Semua dokumen persyaratan jalur reguler</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Fotocopy piagam/sertifikat prestasi (dilegalisir)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Surat rekomendasi dari kepala sekolah asal</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Portofolio prestasi (jika ada)</span>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-secondary-500 text-white rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                            Kategori Prestasi yang Diterima
                        </h4>
                        <div class="ml-11 grid md:grid-cols-2 gap-4">
                            <div class="bg-accent-50 rounded-xl p-4">
                                <h5 class="font-bold text-gray-800 mb-2">Akademik</h5>
                                <ul class="text-sm text-gray-700 space-y-1">
                                    <li>• Olimpiade Sains/Matematika</li>
                                    <li>• Olimpiade Bahasa</li>
                                    <li>• Lomba Karya Ilmiah</li>
                                </ul>
                            </div>
                            <div class="bg-accent-50 rounded-xl p-4">
                                <h5 class="font-bold text-gray-800 mb-2">Non-Akademik</h5>
                                <ul class="text-sm text-gray-700 space-y-1">
                                    <li>• Olahraga (minimal Kabupaten)</li>
                                    <li>• Seni & Budaya</li>
                                    <li>• Teknologi & Robotik</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-secondary-50 rounded-2xl p-6 border-l-4 border-secondary-600">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-secondary-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h5 class="font-bold text-gray-800 mb-1">Keuntungan Jalur Prestasi</h5>
                                <p class="text-sm text-gray-700">Siswa yang lolos jalur prestasi berpeluang mendapatkan <strong>beasiswa 25%-100%</strong> biaya pendidikan sesuai tingkat prestasi yang diraih.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
    function switchTab(tab) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'bg-primary-600', 'text-white');
            button.classList.add('text-gray-600');
        });
        
        // Show selected tab content
        document.getElementById('content-' + tab).classList.remove('hidden');
        
        // Add active class to selected button
        const activeButton = document.getElementById('tab-' + tab);
        activeButton.classList.add('active', 'bg-primary-600', 'text-white');
        activeButton.classList.remove('text-gray-600');
    }
    
    // Initialize first tab as active
    document.addEventListener('DOMContentLoaded', function() {
        switchTab('reguler');
    });
</script>
