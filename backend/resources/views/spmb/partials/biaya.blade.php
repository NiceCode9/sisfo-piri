@php
    $biayas = $biayas ?? collect();
    $biayasWajib = $biayas->where('wajib_bayar', true);
    $totalWajib = $biayasWajib->sum('jumlah');
    $tahunAjaranLabel = $tahunAjaranAktif?->nama_tahun_ajaran;

    $adaYangBisaDicicil = $biayas->contains(fn ($b) => $b->dapat_diangsur && $b->max_cicilan);
@endphp

{{--
    Tiga kartu informasi di bawah tabel versi lama dihapus. Semuanya berisi
    klaim tanpa sumber: "Beasiswa 25%-100%", "Transfer bank, virtual account,
    atau datang langsung". Tidak ada tabel beasiswa, tidak ada kolom metode
    pembayaran di mana pun. Metode pembayaran juga bisa berbeda tiap bank, jadi
    (R-36).

    Yang menggantikan adalah data yang memang ada: komponen mana yang bisa
    dicicil dan berapa kali, karena itulah pertanyaan yang paling sering
    diajukan orang tua setelah menanyakan totalnya.

    Markup tabel desktop dan kartu mobile versi lama juga saling menyalin. Satu
    kali saja di sini, jadi tidak bisa lagi berbeda isi antara dua tampilan.
--}}

<section id="biaya" class="bg-kertas-200 py-20 sm:py-24">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="max-w-2xl">
            <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                Biaya <span class="text-primary-700">Pendidikan</span>
            </h2>
            <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                Investasi terbaik untuk masa depan putra-putri Anda dengan biaya
                yang transparan
                @if ($tahunAjaranLabel)
                    , Tahun Ajaran {{ $tahunAjaranLabel }}
                @endif.
                Semua nominal di bawah berasal dari data sekolah, jadi yang
                berubah akan diperbarui di sini.
            </p>
        </div>

        {{-- Dijaga dengan `@if`, bukan `@forelse`. Versi pertama memakai
             `@forelse` di level ini, sehingga seluruh tabelbiaya ikut
             terulang sekali untuk tiap baris komponen. --}}
        @if ($biayas->isNotEmpty())
            <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-12">

                {{-- Total di kolom kiri, karena itu angka yang orang tua
                     datangi halaman ini. Di layar kecil kolom ini muncul
                     lebih dulu supaya tidak ada yang menggulir cari-cari. --}}
                <div class="lg:col-span-4">
                    <div class="rounded-lg bg-primary-800 p-6 text-white">
                        <p class="text-sm font-semibold text-secondary-200">Total yang wajib dibayar</p>
                        <p class="mt-2 font-display text-3xl font-extrabold sm:text-4xl">
                            Rp {{ number_format($totalWajib, 0, ',', '.') }}
                        </p>
                        <p class="mt-3 text-sm text-secondary-100/85">
                            {{ $biayasWajib->count() }} komponen wajib
                            @if ($biayas->count() > $biayasWajib->count())
                                , ditambah {{ $biayas->count() - $biayasWajib->count() }} komponen opsional
                            @endif
                        </p>
                        <p class="mt-4 border-t border-primary-700 pt-4 text-xs leading-relaxed text-secondary-100/70">
                            Komponen opsional tidak termasuk angka ini. Halaman
                            ini tidak menyebut biaya di luar pendaftaran.
                        </p>
                    </div>

                    @if ($adaYangBisaDicicil)
                        <p class="mt-4 rounded-md border border-kertas-300 bg-kertas-100 p-4 text-sm leading-relaxed text-ink-600">
                            Sebagian komponen bisa dicicil. Keterangannya ada
                            di sebelah kanan pada tiap baris.
                        </p>
                    @endif
                </div>

                <div class="lg:col-span-8">
                    <dl class="border-y border-kertas-300">
                        @foreach ($biayas as $biaya)
                            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-kertas-200 py-5 last:border-b-0">
                                <div class="min-w-0 flex-1">
                                    <dt class="font-display text-base font-extrabold text-ink-900">
                                        {{ $biaya->jenis_biaya }}
                                    </dt>
                                    <dd class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                        <span class="{{ $biaya->wajib_bayar ? 'text-ink-700' : 'text-ink-500' }}">
                                            {{ $biaya->wajib_bayar ? 'Wajib' : 'Opsional' }}
                                        </span>
                                        @if ($biaya->dapat_diangsur && $biaya->max_cicilan)
                                            <span class="text-secondary-700">Bisa dicicil {{ $biaya->max_cicilan }} kali</span>
                                        @elseif ($biaya->dapat_diangsur)
                                            <span class="text-secondary-700">Bisa dicicil</span>
                                        @endif
                                    </dd>
                                    @if ($biaya->keterangan)
                                        <dd class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $biaya->keterangan }}</dd>
                                    @endif
                                </div>
                                <dd class="font-display text-lg font-extrabold text-primary-800">
                                    Rp {{ number_format($biaya->jumlah, 0, ',', '.') }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        @else
            {{-- Empty state (R-27): menyebut sebab dan jalan keluar, bukan
                 hanya "Belum ada rincian biaya". --}}
            <div class="reveal mt-10 max-w-xl rounded-md border border-kertas-300 bg-kertas-100 p-8">
                <h3 class="font-display text-lg font-extrabold text-ink-900">Rincian biaya belum diisi</h3>
                <p class="mt-2 text-base leading-relaxed text-ink-600">
                    Sekolah belum memasukkan rincian biaya untuk
                    @if ($tahunAjaranLabel) tahun ajaran {{ $tahunAjaranLabel }} @else
                        tahun ajaran berjalan
                    @endif.
                    Sampai rincian masuk, halaman ini sengaja tidak menampilkan
                    angka perkiraan, karena perkiraan yang meleset lebih buruk
                    daripada belum ada angka sama sekali.
                </p>
                @if ($profileSekolah?->whatsapp)
                    <a href="https://wa.me/{{ $profileSekolah->whatsapp }}" class="btn btn-quiet mt-6">
                        Tanya Biaya ke Sekolah
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
