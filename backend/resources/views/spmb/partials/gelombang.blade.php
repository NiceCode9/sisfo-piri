@php
    $gelombangs = $gelombangs ?? collect();
    $gelombangList = $gelombangs->sortBy('nomor_urut')->values();
@endphp

{{--
    Catatan penting di bagian bawah versi lama dihapus. Isinya "pembayaran
    tidak dapat dikembalikan", "jadwal tes seleksi dikonfirmasi via email",
    dan "daftar ulang maksimal 7 hari setelah pengumuman" adalah kebijakan
    sekolah yang tidak ada di mana pun dalam sistem ini, dan tidak satu pun
    bisa diverifikasi calon siswa. Sistem ini juga tidak punya tes seleksi sama
    sekali, jadi menyebut "jadwal tes" ikut mengarang proses yang tidak ada
    (R-36). Kalau sekolah memang punya aturan seperti itu, aturan itu ditulis
    di kolom keterangan tiap gelombang, bukan di sini sebagai ketetapan.

    Warna kartu juga tidak lagi mengikuti kolom `warna_border`. Kolom itu masih
    ada di database, tapi memakainya untuk mewarnai kartu membuat empat warna
    empat warna berkelompok di satu halaman tanpa ada yang bisa membacanya sebagai
    informasi apa pun (R-29). Warna sekarang hanya menyatakan keadaan: hijau
    untuk terbuka, emas untuk akan dibuka, abu-abu untuk lewat atau penuh.
--}}

<section id="gelombang" class="bg-kertas-100 py-20 sm:py-28">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Timeline <span class="text-primary-700">Gelombang Pendaftaran</span>
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Daftar sekarang dan dapatkan keuntungan dari setiap
                    gelombang. Tahap dan tanggal di bawah ini diset langsung oleh
                    sekolah, dan gelombang yang sudah lewat tidak ditampilkan.
                </p>
            </div>
        </div>

        @forelse ($gelombangList as $g)
            @php
                $status = \App\Support\StatusPendaftaran::untuk($g);
                $sisa = $g->sisa_kursi;
                $sisaTerlihat = $sisa !== null && ($g->kuota ?? 0) > 0;

                // Pita status memakai warna dari palet sendiri, bukan
                // green-100/sky-100/amber-100 yang tidak ada dalam sistem.
                $pita = match ($status->state) {
                    \App\Support\StatusPendaftaran::BUKA => 'bg-primary-50 text-primary-800 ring-primary-200',
                    \App\Support\StatusPendaftaran::BELUM => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
                    default => 'bg-kertas-200 text-ink-600 ring-kertas-400',
                };
            @endphp

            <article class="reveal mt-8 border-t border-kertas-300 pt-8">
                <div class="grid gap-6 md:grid-cols-12 md:gap-10">

                    <div class="md:col-span-5">
                        @if ($g->badge)
                            <p class="text-xs font-semibold uppercase tracking-wider text-secondary-600">{{ $g->badge }}</p>
                        @endif

                        <h3 class="mt-1 font-display text-xl font-extrabold text-ink-900">{{ $g->nama_gelombang }}</h3>

                        <p class="mt-3 inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold ring-1 {{ $pita }}">
                            {{ $status->labelGelombang() }}
                        </p>

                        @if ($tahap = $g->tahapPendaftaran())
                            <p class="mt-4 flex items-start gap-2 text-sm text-ink-600">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>
                                    Pendaftaran dibuka
                                    {{ $tahap->tanggal_mulai->translatedFormat('j M Y') }}
                                    sampai {{ $tahap->tanggalAkhir()->translatedFormat('j M Y') }}
                                </span>
                            </p>
                        @endif

                        @if ($g->keterangan)
                            <p class="mt-4 text-sm leading-relaxed text-ink-600">{!! nl2br(e($g->keterangan)) !!}</p>
                        @endif
                    </div>

                    <div class="md:col-span-4">
                        <h4 class="text-sm font-semibold text-ink-700">Tahapan seleksi</h4>

                        @if ($g->tahapan->isNotEmpty())
                            <ol class="mt-3 space-y-0">
                                @foreach ($g->tahapan as $t)
                                    <li class="flex items-baseline justify-between gap-4 border-b border-kertas-300 py-2 last:border-b-0">
                                        <span class="text-sm text-ink-600">{{ $t->nama_tahap }}</span>
                                        <span class="text-sm font-semibold text-ink-900">
                                            @if ($t->punyaJendela())
                                                {{ $t->tanggal_mulai->translatedFormat('j M') }} - {{ $t->tanggalAkhir()->translatedFormat('j M Y') }}
                                            @else
                                                {{ $t->tanggal_mulai->translatedFormat('j M Y') }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="mt-3 text-sm text-ink-500">Tahapan belum ditetapkan sekolah.</p>
                        @endif
                    </div>

                    <div class="md:col-span-3">
                        @if ($sisaTerlihat)
                            <p class="text-sm font-semibold text-ink-700">Sisa kursi</p>
                            <p class="mt-1 font-display text-lg font-extrabold text-ink-900">{{ $sisa }}</p>
                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-kertas-300">
                                <div class="h-2 rounded-full bg-primary-600"
                                     style="width: {{ min(100, $g->persentase) }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-ink-500">
                                {{ $g->terisi }} dari {{ $g->kuota }} kursi terisi
                            </p>
                        @else
                            <p class="text-sm font-semibold text-ink-700">Kuota</p>
                            <p class="mt-1 font-display text-lg font-extrabold text-ink-900">Tanpa batas</p>
                            <p class="mt-2 text-xs text-ink-500">
                                Sekolah tidak membatasi jumlah peserta gelombang ini.
                            </p>
                        @endif

                        @if ($g->diskon_efektif > 0 || ! empty($g->keuntungan))
                            <div class="mt-5 rounded-md bg-kertas-200 p-4">
                                <h4 class="text-sm font-semibold text-ink-900">Yang didapat di gelombang ini</h4>
                                <ul class="mt-2 space-y-1.5">
                                    @if ($g->diskon_efektif > 0)
                                        <li class="text-sm text-ink-700">Diskon biaya pendaftaran {{ $g->diskon_efektif }}%</li>
                                    @endif
                                    @foreach (($g->keuntungan ?? []) as $untung)
                                        <li class="flex gap-2 text-sm text-ink-700">
                                            <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-primary-600" fill="none"
                                                 stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $untung }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            {{-- Empty state (R-27): menyebut sebabnya dan jalan keluarnya. --}}
            <div class="reveal mt-8 rounded-md border border-kertas-300 bg-kertas-200 p-8">
                <h3 class="font-display text-lg font-extrabold text-ink-900">Belum ada gelombang pendaftaran yang dibuka</h3>
                <p class="mt-2 max-w-lg text-base leading-relaxed text-ink-600">
                    Sekolah belum membuka jadwal gelombang pendaftaran untuk tahun
                    ajaran ini, atau semua gelombang yang pernah dibuka sudah lewat.
                    Halaman pengumuman akan memberi tahu begitu jadwal baru keluar.
                </p>
                <a href="{{ route('spmb.pengumuman.index') }}" class="btn btn-quiet mt-6">
                    Lihat Pengumuman
                </a>
            </div>
        @endforelse
    </div>
</section>
