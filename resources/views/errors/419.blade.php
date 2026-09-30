@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi berakhir')
@section('message', 'Halaman ini terlalu lama dibiarkan terbuka, sehingga demi keamanan formulirnya kedaluwarsa. Muat ulang halaman, lalu coba lagi.')

{{-- Tombol utamanya memuat ulang, bukan kembali ke beranda: yang
     dibutuhkan pengguna adalah token baru untuk halaman yang sama. --}}
@section('action')
    <button type="button" onclick="location.reload()" class="tombol utama">Muat ulang</button>
    <a href="{{ url('/') }}" class="tombol kedua">Ke halaman utama</a>
@endsection
