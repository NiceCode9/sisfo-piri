@extends('layouts.spmb')

@section('title', 'SPMB - Penerimaan Murid Baru')

@section('content')

    {{-- Navbar --}}
    @include('spmb.partials.navbar')

    {{-- Hero Section --}}
    @include('spmb.partials.hero')

    {{-- Keunggulan Sekolah --}}
    @include('spmb.partials.keunggulan')

    {{-- Jalur Seleksi --}}
    @include('spmb.partials.jalur-seleksi')

    {{-- Alur Pendaftaran --}}
    @include('spmb.partials.alur-pendaftaran')

    {{-- Timeline Gelombang --}}
    @include('spmb.partials.gelombang')

    {{-- Biaya Pendidikan --}}
    @include('spmb.partials.biaya')

    {{-- Ekstrakurikuler --}}
    @include('spmb.partials.ekstrakurikuler')

    {{-- Galeri & Prestasi --}}
    @include('spmb.partials.galeri')

    {{-- Kontak --}}
    @include('spmb.partials.kontak2')

    {{-- Footer --}}
    @include('spmb.partials.footer')

@endsection
