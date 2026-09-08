@php $pengumumans = $pengumumans ?? collect(); @endphp

<section id="pengumuman" class="py-20 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Pengumuman <span class="text-primary-600">Terbaru</span></h2>
            <p class="text-lg text-gray-600">Informasi resmi seputar PPDB — hanya yang aktif ditampilkan</p>
        </div>

        @if ($pengumumans->isEmpty())
            <p class="text-center text-sm text-gray-500 py-8">Belum ada pengumuman.</p>
        @else
            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach ($pengumumans as $p)
                    <div class="bg-white rounded-2xl shadow-lg p-6 border-t-4 border-primary-600 flex flex-col">
                        <div class="text-xs text-gray-500 mb-2"><i class="far fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($p->tanggal_pengumuman)->format('d M Y') }} • {{ $p->tahunAjaran->nama_tahun_ajaran ?? '' }}</div>
                        <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $p->judul }}</h3>
                        <p class="text-sm text-gray-600 line-clamp-3 mb-4">{{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 120) }}</p>
                        <a href="{{ route('spmb.pengumuman.show', $p) }}" class="mt-auto inline-flex items-center text-sm font-semibold text-primary-600 hover:text-primary-700">Baca selengkapnya <i class="fas fa-arrow-right ml-2 text-xs"></i></a>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-8">
                <a href="{{ route('spmb.pengumuman.index') }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-primary-600 text-primary-600 rounded-xl font-semibold hover:bg-primary-50 transition">Lihat semua pengumuman <i class="fas fa-bullhorn ml-2"></i></a>
            </div>
        @endif
    </div>
</section>
