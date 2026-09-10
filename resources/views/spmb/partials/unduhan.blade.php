@php
    // TODO: taruh file PDF asli di /public/documents/, lalu sesuaikan path di bawah.
    // Kalau dokumen bertambah banyak di kemudian hari, tinggal tambah item ke array ini.
    $dokumenUnduhan = [
        [
            'icon' => 'document',
            'title' => 'Brosur SPMB',
            'desc' => 'Info lengkap program, keunggulan, dan biaya pendidikan',
            'file' => asset('0001.jpg'),
            'size' => '2.4 MB',
        ],
        [
            'icon' => 'route',
            'title' => 'Alur Pendaftaran',
            'desc' => 'Panduan langkah demi langkah proses pendaftaran',
            'file' => asset('0002.jpg'),
            'size' => '1.1 MB',
        ],
    ];

    $unduhanIcons = [
        'document' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'route' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
        'download' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    ];
@endphp

<section id="unduhan" class="py-16 bg-slate-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6 md:p-10">
            <div class="text-center mb-10">
                <span class="inline-block px-4 py-1.5 bg-primary-100 text-primary-700 rounded-full text-sm font-semibold mb-4">Unduh</span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-2">Simpan Info Pendaftaran</h2>
                <p class="text-gray-600 max-w-xl mx-auto">Unduh brosur dan alur pendaftaran untuk dibaca offline atau dibagikan ke keluarga</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                @foreach ($dokumenUnduhan as $dokumen)
                    <a href="{{ $dokumen['file'] }}" target="_blank" rel="noopener" download
                       class="group flex items-center gap-4 p-5 bg-slate-50 hover:bg-primary-50 rounded-2xl border border-gray-100 hover:border-primary-200 transition-all duration-300 card-hover">
                        <div class="w-14 h-14 bg-gradient-primary rounded-2xl flex items-center justify-center text-white flex-shrink-0 shadow-md">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $unduhanIcons[$dokumen['icon']] }}"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-800 group-hover:text-primary-700 transition-colors">{{ $dokumen['title'] }}</h3>
                            <p class="text-sm text-gray-500 mb-1 truncate">{{ $dokumen['desc'] }}</p>
                            <span class="text-xs text-gray-400">PDF • {{ $dokumen['size'] }}</span>
                        </div>

                        <div class="w-9 h-9 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm group-hover:bg-primary-600 transition-colors">
                            <svg class="w-4 h-4 text-primary-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $unduhanIcons['download'] }}"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
