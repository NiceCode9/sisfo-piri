@php $gelombangs = $gelombangs ?? collect(); @endphp

<section id="gelombang" class="py-20 bg-slate-50">
    <div class="container mx-auto px-4">
        
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Timeline <span class="text-primary-600">Gelombang Pendaftaran</span>
            </h2>
            <p class="text-lg text-gray-600">
                Daftar sekarang dan dapatkan keuntungan dari setiap gelombang
            </p>
        </div>

        {{-- Gelombang Cards --}}
        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @forelse ($gelombangs as $g)
                @php
                    $border = $g->warna_border ?? 'primary-600';
                    $gradient = match($border) {
                        'secondary-600' => 'from-secondary-500 to-secondary-700',
                        'accent-600' => 'from-accent-500 to-accent-700',
                        default => 'from-primary-600 to-primary-800',
                    };
                    $borderClass = 'border-'.$border;
                    $persen = $g->kuota > 0 ? round($g->terisi / $g->kuota * 100) : 0;
                    $barColor = $border === 'accent-600' ? 'bg-amber-500' : 'bg-accent-500';
                    $badgeBg = $border === 'accent-600' ? 'bg-amber-100 text-amber-700' : 'bg-accent-100 text-accent-700';
                @endphp
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden card-hover border-t-8 {{ $borderClass }}">
                    <div class="bg-gradient-to-br {{ $gradient }} px-6 py-8 text-white text-center">
                        @if($g->badge)
                            <div class="inline-block px-4 py-1 bg-white/20 rounded-full text-xs font-bold mb-3">
                                {{ $g->badge }}
                            </div>
                        @endif
                        <h3 class="text-2xl font-extrabold mb-2">{{ $g->nama_gelombang }}</h3>
                        <div class="flex items-center justify-center space-x-2 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-semibold">{{ \Carbon\Carbon::parse($g->tanggal_buka)->format('M Y') }} - {{ \Carbon\Carbon::parse($g->tanggal_tutup)->format('M Y') }}</span>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b">
                            <span class="text-sm font-medium text-gray-600">Pendaftaran Dibuka</span>
                            <span class="text-sm font-bold text-gray-800">{{ \Carbon\Carbon::parse($g->tanggal_buka)->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between pb-3 border-b">
                            <span class="text-sm font-medium text-gray-600">Pendaftaran Ditutup</span>
                            <span class="text-sm font-bold text-gray-800">{{ \Carbon\Carbon::parse($g->tanggal_tutup)->format('d M Y') }}</span>
                        </div>
                        @if($g->tanggal_tes)
                            <div class="flex items-center justify-between pb-3 border-b">
                                <span class="text-sm font-medium text-gray-600">Tes Seleksi</span>
                                <span class="text-sm font-bold text-gray-800">{{ \Carbon\Carbon::parse($g->tanggal_tes)->format('d M Y') }}</span>
                            </div>
                        @endif
                        @if($g->tanggal_pengumuman)
                            <div class="flex items-center justify-between pb-3">
                                <span class="text-sm font-medium text-gray-600">Pengumuman</span>
                                <span class="text-sm font-bold text-gray-800">{{ \Carbon\Carbon::parse($g->tanggal_pengumuman)->format('d M Y') }}</span>
                            </div>
                        @endif

                        <div class="bg-accent-50 rounded-2xl p-4 mt-6">
                            <h4 class="font-bold text-gray-800 mb-2 text-sm flex items-center">
                                <svg class="w-5 h-5 text-accent-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                                Keuntungan
                            </h4>
                            <ul class="space-y-2 text-xs text-gray-700">
                                @if($g->diskon_persen)
                                    <li class="flex items-start">
                                        <svg class="w-4 h-4 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>Diskon <strong>{{ $g->diskon_persen }}% biaya pendaftaran</strong></span>
                                    </li>
                                @endif
                                @forelse (($g->keuntungan ?? []) as $untung)
                                    <li class="flex items-start">
                                        <svg class="w-4 h-4 text-accent-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>{{ $untung }}</span>
                                    </li>
                                @empty
                                    @if(!$g->diskon_persen)
                                        <li class="text-gray-500">Tidak ada keuntungan khusus.</li>
                                    @endif
                                @endforelse
                            </ul>
                        </div>

                        <div class="pt-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-600">Kuota Tersedia</span>
                                <span class="px-3 py-1 {{ $badgeBg }} text-xs font-bold rounded-full">{{ $g->kuota }} kursi</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min(100, $persen) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $persen }}% terisi ({{ $g->terisi }}/{{ $g->kuota }})</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-sm text-gray-500">Belum ada gelombang aktif.</div>
            @endforelse
        </div>

        {{-- Info Box --}}
        <div class="max-w-4xl mx-auto mt-12">
            <div class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-3xl p-8 text-white shadow-2xl">
                <div class="flex items-start space-x-4">
                    <svg class="w-12 h-12 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Catatan Penting</h3>
                        <ul class="space-y-2 text-sm text-white/90">
                            <li>• Kuota terbatas dan bisa penuh sebelum tanggal penutupan</li>
                            <li>• Pembayaran biaya pendaftaran tidak dapat dikembalikan</li>
                            <li>• Jadwal tes seleksi akan dikonfirmasi via email setelah pembayaran</li>
                            <li>• Daftar ulang maksimal 7 hari setelah pengumuman</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
