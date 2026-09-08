@extends('layouts.spmb')

@section('title', $pengumuman->judul . ' — SPMB')

@section('content')
@include('spmb.partials.navbar')

<section class="pt-28 pb-12 bg-white">
    <div class="container mx-auto px-4 max-w-3xl">
        <a href="{{ route('spmb.pengumuman.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-primary-600 mb-6"><i class="fas fa-arrow-left mr-2"></i> Kembali ke pengumuman</a>

        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <div class="text-sm text-gray-500 mb-3">{{ \Carbon\Carbon::parse($pengumuman->tanggal_pengumuman)->format('d M Y') }} • {{ $pengumuman->tahunAjaran->nama_tahun_ajaran ?? '' }}</div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-4">{{ $pengumuman->judul }}</h1>
            <div class="prose max-w-none text-gray-700 leading-relaxed" style="white-space: pre-line;">{{ $pengumuman->isi }}</div>
        </div>
    </div>
</section>

@include('spmb.partials.footer')
@endsection
