{{-- TODO: ganti dengan data asli biaya pendidikan --}}

<section id="biaya" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Biaya <span class="text-primary-600">Pendidikan</span>
            </h2>
            <p class="text-lg text-gray-600">
                Investasi terbaik untuk masa depan putra-putri Anda dengan biaya yang transparan
            </p>
        </div>

        {{-- Tabel Biaya --}}
        <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden">
            
            {{-- Table Header --}}
            <div class="bg-gradient-primary px-8 py-6">
                <h3 class="text-xl md:text-2xl font-bold text-white text-center">Rincian Biaya Tahun Ajaran 2026/2027</h3>
            </div>

            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-100 border-b-2 border-primary-600">
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase">Uraian</th>
                            <th class="px-6 py-4 text-right text-sm font-bold text-gray-800 uppercase">Biaya</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        {{-- Biaya Pendaftaran --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">1</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">Biaya Pendaftaran</div>
                                <div class="text-xs text-gray-500">Registration Fee</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 300.000</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                Dibayar saat mendaftar<br/>
                                <span class="text-accent-600 font-semibold">Tidak dapat dikembalikan</span>
                            </td>
                        </tr>

                        {{-- Biaya SPP --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">2</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">SPP Bulanan</div>
                                <div class="text-xs text-gray-500">Monthly Tuition</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 850.000</div>
                                <div class="text-xs text-gray-500">/bulan</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                Dibayar setiap tanggal 1-10<br/>
                                Sudah termasuk kegiatan ekstrakurikuler
                            </td>
                        </tr>

                        {{-- Uang Pangkal --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">3</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">Uang Pangkal</div>
                                <div class="text-xs text-gray-500">Admission Fee</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 5.500.000</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                Dibayar 1x saat diterima<br/>
                                <span class="text-secondary-600 font-semibold">Bisa dicicil 2x</span>
                            </td>
                        </tr>

                        {{-- Seragam --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">4</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">Seragam & Atribut</div>
                                <div class="text-xs text-gray-500">Uniform Package</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 1.200.000</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                3 stel seragam lengkap<br/>
                                (Putih-biru, Pramuka, Olahraga)
                            </td>
                        </tr>

                        {{-- Buku & LKS --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">5</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">Buku & LKS</div>
                                <div class="text-xs text-gray-500">Books & Workbooks</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 900.000</div>
                                <div class="text-xs text-gray-500">/tahun</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                Paket buku pelajaran kelas 7<br/>
                                Dibayar di awal tahun ajaran
                            </td>
                        </tr>

                        {{-- Kegiatan --}}
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 font-semibold">6</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">Kegiatan Tahunan</div>
                                <div class="text-xs text-gray-500">Annual Activities</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-primary-600">Rp 1.500.000</div>
                                <div class="text-xs text-gray-500">/tahun</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                Study tour, pentas seni,<br/>
                                kemah, dan kegiatan lainnya
                            </td>
                        </tr>

                        {{-- Total --}}
                        <tr class="bg-primary-50 border-t-2 border-primary-600">
                            <td colspan="2" class="px-6 py-5">
                                <div class="font-extrabold text-lg text-gray-800 uppercase">Total Biaya Awal</div>
                                <div class="text-xs text-gray-600">Biaya yang harus dibayar saat diterima</div>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="font-extrabold text-2xl text-primary-700">Rp 9.400.000</div>
                            </td>
                            <td class="px-6 py-5 text-sm text-gray-600">
                                <span class="inline-block px-3 py-1 bg-secondary-500 text-white text-xs font-bold rounded-full">
                                    Belum termasuk SPP bulanan
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden p-4 space-y-4">
                
                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">Biaya Pendaftaran</div>
                            <div class="text-xs text-gray-500">Registration Fee</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 300.000</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Dibayar saat mendaftar. <span class="text-accent-600 font-semibold">Tidak dapat dikembalikan</span></p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">SPP Bulanan</div>
                            <div class="text-xs text-gray-500">Monthly Tuition</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 850.000</div>
                            <div class="text-xs text-gray-500">/bulan</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Dibayar setiap tanggal 1-10. Sudah termasuk kegiatan ekstrakurikuler</p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">Uang Pangkal</div>
                            <div class="text-xs text-gray-500">Admission Fee</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 5.500.000</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Dibayar 1x saat diterima. <span class="text-secondary-600 font-semibold">Bisa dicicil 2x</span></p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">Seragam & Atribut</div>
                            <div class="text-xs text-gray-500">Uniform Package</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 1.200.000</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">3 stel seragam lengkap (Putih-biru, Pramuka, Olahraga)</p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">Buku & LKS</div>
                            <div class="text-xs text-gray-500">Books & Workbooks</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 900.000</div>
                            <div class="text-xs text-gray-500">/tahun</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Paket buku pelajaran kelas 7. Dibayar di awal tahun ajaran</p>
                </div>

                <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-gray-800">Kegiatan Tahunan</div>
                            <div class="text-xs text-gray-500">Annual Activities</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-primary-600">Rp 1.500.000</div>
                            <div class="text-xs text-gray-500">/tahun</div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Study tour, pentas seni, kemah, dan kegiatan lainnya</p>
                </div>

                <div class="bg-primary-600 rounded-2xl p-5 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-extrabold text-lg uppercase">Total Biaya Awal</div>
                            <div class="text-xs text-white/80">Biaya saat diterima</div>
                        </div>
                        <div class="text-right">
                            <div class="font-extrabold text-2xl">Rp 9.400.000</div>
                        </div>
                    </div>
                    <p class="text-xs text-white/90 mt-3">Belum termasuk SPP bulanan</p>
                </div>

            </div>

        </div>

        {{-- Info Cards --}}
        <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto mt-12">
            
            <div class="bg-accent-50 rounded-2xl p-6 border-t-4 border-accent-600">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-accent-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-800">Beasiswa Tersedia</h4>
                </div>
                <p class="text-sm text-gray-700">Siswa berprestasi berpeluang mendapat beasiswa 25%-100% biaya pendidikan</p>
            </div>

            <div class="bg-secondary-50 rounded-2xl p-6 border-t-4 border-secondary-600">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-secondary-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-800">Metode Pembayaran</h4>
                </div>
                <p class="text-sm text-gray-700">Transfer bank, virtual account, atau datang langsung ke sekolah</p>
            </div>

            <div class="bg-purple-50 rounded-2xl p-6 border-t-4 border-purple-600">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-800">Butuh Bantuan?</h4>
                </div>
                <p class="text-sm text-gray-700">Hubungi panitia untuk informasi lebih lanjut atau konsultasi biaya</p>
            </div>

        </div>

    </div>
</section>
