@extends('layouts.spmb')

@section('title', 'Pengumuman — SPMB')

@section('content')
@include('spmb.partials.navbar')

<section class="pt-28 pb-12 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-3">Pengumuman</h1>
            <p class="text-gray-600">Semua informasi resmi dengan status aktif</p>
        </div>

        @if ($pengumumans->isEmpty())
            <p class="text-center text-sm text-gray-500 py-12">Belum ada pengumuman.</p>
        @else
            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                @foreach ($pengumumans as $p)
                    <a href="{{ route('spmb.pengumuman.show', $p) }}" class="bg-white rounded-2xl shadow-lg p-6 border-t-4 border-primary-600 hover:shadow-xl transition block">
                        <div class="text-xs text-gray-500 mb-2">{{ \Carbon\Carbon::parse($p->tanggal_pengumuman)->format('d M Y') }} • {{ $p->tahunAjaran->nama_tahun_ajaran ?? '' }}</div>
                        <h3 class="font-bold text-gray-800 mb-2">{{ $p->judul }}</h3>
                        <p class="text-sm text-gray-600 line-clamp-3">{{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 140) }}</p>
                    </a>
                @endforeach
            </div>
            <div class="mt-8 d-flex justify-content-center">
                {{ $pengumumans->links() }}
            </div>
        @endif
    </div>
</section>

@include('spmb.partials.footer')
@endsection
