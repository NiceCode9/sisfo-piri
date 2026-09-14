{{-- TODO: ganti dengan data kontak asli sekolah --}}
@php
    // Data kartu kontak — tambah/kurangi item di sini tanpa perlu duplikasi markup
    $kontakCards = [
        [
            'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
            'bg' => 'bg-primary-100',
            'iconColor' => 'text-primary-600',
            'title' => 'Alamat Sekolah',
        ],
        [
            'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
            'bg' => 'bg-accent-100',
            'iconColor' => 'text-accent-600',
            'title' => 'Telepon & WhatsApp',
        ],
        [
            'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'bg' => 'bg-secondary-100',
            'iconColor' => 'text-secondary-600',
            'title' => 'Email',
        ],
        [
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'bg' => 'bg-purple-100',
            'iconColor' => 'text-purple-600',
            'title' => 'Jam Pelayanan',
        ],
    ];

    // Data sosial media — tambah platform baru cukup tambah 1 baris array
    $socialLinks = [
        [
            'name' => 'Facebook',
            'url' => '#',
            'icon' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        ],
        [
            'name' => 'Instagram',
            'url' => '#',
            'icon' => 'M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z',
        ],
        [
            'name' => 'YouTube',
            'url' => '#',
            'icon' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        ],
        [
            'name' => 'Twitter',
            'url' => '#',
            'icon' => 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z',
        ],
    ];
@endphp

<section id="kontak" class="py-20 bg-slate-50">
    <div class="container mx-auto px-4">

        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">
                Hubungi <span class="text-primary-600">Panitia SPMB</span>
            </h2>
            <p class="text-lg text-gray-600">
                Ada pertanyaan? Tim kami siap membantu Anda
            </p>
        </div>

        <div class="max-w-6xl mx-auto">
            {{-- items-start supaya kolom kiri & kanan tidak saling stretch/ketergantungan tinggi --}}
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-start">

                {{-- Left: Contact Info --}}
                <div class="space-y-6">
                    @foreach ($kontakCards as $card)
                        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-lg card-hover">
                            <div class="flex items-start space-x-4">
                                <div class="w-14 h-14 {{ $card['bg'] }} rounded-2xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-7 h-7 {{ $card['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $card['title'] }}</h3>

                                    @switch($card['title'])
                                        @case('Alamat Sekolah')
                                            <p class="text-gray-600 leading-relaxed">
                                                {!! nl2br(e($profileSekolah->alamat ?? "Jl. Pendidikan No. 123\nKelurahan Maju Jaya, Kecamatan Harapan\nKota Bandung, Jawa Barat 40123")) !!}
                                            </p>
                                            @break

                                        @case('Telepon & WhatsApp')
                                            <div class="space-y-2">
                                                <div>
                                                    <div class="text-sm text-gray-500">Telepon Sekolah</div>
                                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $profileSekolah->telp ?? '+622212345678') }}" class="text-gray-800 font-semibold hover:text-primary-600 transition">
                                                        {{ $profileSekolah->telp ?? '(022) 1234-5678' }}
                                                    </a>
                                                </div>
                                                <div>
                                                    <div class="text-sm text-gray-500">WhatsApp Panitia</div>
                                                    <a href="https://wa.me/6281234567890"
                                                       target="_blank" rel="noopener"
                                                       class="inline-flex items-center text-accent-600 font-semibold hover:text-accent-700 transition">
                                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                        </svg>
                                                        0812-3456-7890
                                                    </a>
                                                </div>
                                            </div>
                                            @break

                                        @case('Email')
                                            <div class="space-y-1">
                                                <div>
                                                    <div class="text-sm text-gray-500">Email Umum</div>
                                                    <a href="mailto:{{ $profileSekolah->email ?? 'info@smpharapanbangsa.sch.id' }}" class="text-gray-800 font-semibold hover:text-primary-600 transition break-all">
                                                        {{ $profileSekolah->email ?? 'info@smpharapanbangsa.sch.id' }}
                                                    </a>
                                                </div>
                                                <div>
                                                    <div class="text-sm text-gray-500">Email SPMB</div>
                                                    <a href="mailto:{{ $profileSekolah->email ?? 'spmb@smpharapanbangsa.sch.id' }}" class="text-gray-800 font-semibold hover:text-primary-600 transition break-all">
                                                        {{ $profileSekolah->email ?? 'spmb@smpharapanbangsa.sch.id' }}
                                                    </a>
                                                </div>
                                            </div>
                                            @break

                                        @case('Jam Pelayanan')
                                            <div class="space-y-2 text-sm">
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-600">Senin - Jumat</span>
                                                    <span class="font-semibold text-gray-800">07:30 - 15:00</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-600">Sabtu</span>
                                                    <span class="font-semibold text-gray-800">08:00 - 12:00</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-gray-600">Minggu & Libur</span>
                                                    <span class="font-semibold text-red-600">Tutup</span>
                                                </div>
                                            </div>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Right: Map & Social --}}
                <div class="space-y-6">
                    {{-- Map: tinggi tetap per breakpoint, bukan h-full (hindari circular height dependency) --}}
                    <div class="bg-white rounded-3xl p-4 shadow-lg">
                        <div class="w-full h-[300px] md:h-[400px] lg:h-[500px] rounded-2xl overflow-hidden">
                            <iframe
                                src="{{ $profileSekolah->maps_embed_url ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798694104159!2d107.6191228!3d-6.914744!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwNTQnNTMuMSJTIDEwN8KwMzcnMDguOCJF!5e0!3m2!1sen!2sid!4v1234567890123!5m2!1sen!2sid' }}"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Lokasi Sekolah"
                                class="rounded-2xl">
                            </iframe>
                        </div>
                    </div>

                    {{-- Social Media --}}
                    <div class="bg-gradient-to-br from-primary-600 to-accent-600 rounded-3xl p-6 md:p-8 text-white shadow-lg">
                        <h3 class="text-xl font-bold mb-4">Ikuti Media Sosial Kami</h3>
                        <p class="text-white/90 mb-6 text-sm">Dapatkan update terbaru seputar SPMB dan kegiatan sekolah</p>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                   aria-label="{{ $social['name'] }} sekolah"
                                   class="flex items-center space-x-3 bg-white/10 hover:bg-white/20 rounded-xl p-4 transition">
                                    <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="{{ $social['icon'] }}"/>
                                    </svg>
                                    <span class="font-semibold">{{ $social['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>
