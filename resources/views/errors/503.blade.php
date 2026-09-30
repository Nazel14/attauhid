@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang diperbarui')
@section('message', 'Sistem sedang dalam perbaikan singkat dan akan kembali dalam beberapa menit.')

@section('action')
    <button type="button" onclick="location.reload()" class="tombol utama">Coba lagi</button>
@endsection
