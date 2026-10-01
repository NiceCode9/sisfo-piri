{{-- TODO: ganti dengan data kontak asli sekolah --}}

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
            <div class="grid lg:grid-cols-2 gap-12">

                {{-- Left: Contact Info --}}
                <div class="space-y-8">

                    {{-- Alamat --}}
                    <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                        <div class="flex items-start space-x-4">
                            <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Alamat Sekolah</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    {!! nl2br(e($profileSekolah->alamat ?? "Jl. Pendidikan No. 123\nKelurahan Maju Jaya, Kecamatan Harapan\nKota Bandung, Jawa Barat 40123")) !!}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Telepon & WhatsApp --}}
                    <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                        <div class="flex items-start space-x-4">
                            <div class="w-14 h-14 bg-accent-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-3">Telepon & WhatsApp</h3>
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
                                           target="_blank"
                                           class="inline-flex items-center text-accent-600 font-semibold hover:text-accent-700 transition">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            0812-3456-7890
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                        <div class="flex items-start space-x-4">
                            <div class="w-14 h-14 bg-secondary-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Email</h3>
                                <div class="space-y-1">
                                    <div>
                                        <div class="text-sm text-gray-500">Email Umum</div>
                                        <a href="mailto:{{ $profileSekolah->email ?? 'info@smpharapanbangsa.sch.id' }}" class="text-gray-800 font-semibold hover:text-primary-600 transition">
                                            {{ $profileSekolah->email ?? 'info@smpharapanbangsa.sch.id' }}
                                        </a>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Email SPMB</div>
                                        <a href="mailto:{{ $profileSekolah->email ?? 'spmb@smpharapanbangsa.sch.id' }}" class="text-gray-800 font-semibold hover:text-primary-600 transition">
                                            {{ $profileSekolah->email ?? 'spmb@smpharapanbangsa.sch.id' }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Jam Operasional --}}
                    <div class="bg-white rounded-3xl p-8 shadow-lg card-hover">
                        <div class="flex items-start space-x-4">
                            <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-3">Jam Pelayanan</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Senin - Jumat</span>
                                        <span class="font-semibold text-gray-800">07:30 - 15:00</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sabtu</span>
                                        <span class="font-semibold text-gray-800">08:00 - 12:00</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Minggu & Libur</span>
                                        <span class="font-semibold text-red-600">Tutup</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right: Map --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl p-4 shadow-lg">
                        <div class="w-full h-full min-h-[500px] rounded-2xl overflow-hidden">
                            <iframe
                                src="{{ $profileSekolah->maps_embed_url ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798694104159!2d107.6191228!3d-6.914744!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwNTQnNTMuMSJTIDEwN8KwMzcnMDguOCJF!5e0!3m2!1sen!2sid!4v1234567890123!5m2!1sen!2sid' }}"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                class="rounded-2xl">
                            </iframe>
                        </div>
                    </div>

                    {{-- Kartu media sosial (Facebook/Instagram/YouTube/Twitter) disembunyikan:
                         belum ada URL resmi sekolah sehingga keempat tautan tidak punya
                         tujuan dan tidak bisa diklik. --}}
                </div>

            </div>
        </div>

    </div>
</section>
