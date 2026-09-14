@php
    $biayas = $biayas ?? collect();
    $biayasWajib = $biayas->where('wajib_bayar', true);
    $totalWajib = $biayasWajib->sum('jumlah');
@endphp

<section id="biaya" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Biaya <span class="text-primary-600">Pendidikan</span>
            </h2>
            <p class="text-lg text-gray-600">
                Investasi terbaik untuk masa depan putra-putri Anda dengan biaya yang transparan — Tahun Ajaran {{ $tahunAjaranAktif->nama_tahun_ajaran ?? '2026/2027' }}
            </p>
        </div>

        {{-- Tabel Biaya --}}
        <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden">
            
            {{-- Table Header --}}
            <div class="bg-gradient-primary px-8 py-6">
                <h3 class="text-xl md:text-2xl font-bold text-white text-center">Rincian Biaya Tahun Ajaran {{ $tahunAjaranAktif->nama_tahun_ajaran ?? '2026/2027' }}</h3>
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
                        @forelse ($biayas as $idx => $biaya)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-700 font-semibold">{{ $idx + 1 }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800">{{ $biaya->jenis_biaya }}</div>
                                    <div class="text-xs text-gray-500">{{ $biaya->wajib_bayar ? 'Wajib' : 'Opsional' }} @if($biaya->dapat_diangsur) • Bisa dicicil @endif</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="font-bold text-lg text-primary-600">Rp {{ number_format($biaya->jumlah,0,',','.') }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $biaya->keterangan ?? '-' }}
                                    @if($biaya->dapat_diangsur && $biaya->max_cicilan)
                                        <br/><span class="text-secondary-600 font-semibold">Bisa dicicil {{ $biaya->max_cicilan }}x</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada rincian biaya untuk tahun ajaran ini.</td></tr>
                        @endforelse

                        {{-- Total --}}
                        <tr class="bg-primary-50 border-t-2 border-primary-600">
                            <td colspan="2" class="px-6 py-5">
                                <div class="font-extrabold text-lg text-gray-800 uppercase">Total Biaya Wajib</div>
                                <div class="text-xs text-gray-600">Hanya yang wajib dibayar</div>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="font-extrabold text-2xl text-primary-700">Rp {{ number_format($totalWajib,0,',','.') }}</div>
                            </td>
                            <td class="px-6 py-5 text-sm text-gray-600">
                                <span class="inline-block px-3 py-1 bg-secondary-500 text-white text-xs font-bold rounded-full">
                                    {{ $biayasWajib->count() }} komponen wajib
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden p-4 space-y-4">
                @forelse ($biayas as $biaya)
                    <div class="bg-slate-50 rounded-2xl p-5 border-l-4 border-primary-600">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="font-bold text-gray-800">{{ $biaya->jenis_biaya }}</div>
                                <div class="text-xs text-gray-500">{{ $biaya->wajib_bayar ? 'Wajib' : 'Opsional' }} @if($biaya->dapat_diangsur) • Cicil {{ $biaya->max_cicilan ?? '' }}x @endif</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-lg text-primary-600">Rp {{ number_format($biaya->jumlah,0,',','.') }}</div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600">{{ $biaya->keterangan ?? '-' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-6">Belum ada rincian biaya.</p>
                @endforelse

                <div class="bg-primary-600 rounded-2xl p-5 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-extrabold text-lg uppercase">Total Biaya Wajib</div>
                            <div class="text-xs text-white/80">Hanya yang wajib</div>
                        </div>
                        <div class="text-right">
                            <div class="font-extrabold text-2xl">Rp {{ number_format($totalWajib,0,',','.') }}</div>
                        </div>
                    </div>
                    <p class="text-xs text-white/90 mt-3">{{ $biayasWajib->count() }} komponen wajib</p>
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
