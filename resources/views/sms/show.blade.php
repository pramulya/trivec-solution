@extends('layouts.mail')

@section('title', 'SMS Detail')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <div class="bg-gray-800 border border-gray-700 rounded p-4">
        <div class="text-sm text-gray-400">From</div>
        <div class="text-lg text-gray-100">+628123456789</div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded p-6 text-gray-200 whitespace-pre-wrap">
        OTP Anda adalah 123456.
        Jangan berikan kode ini kepada siapa pun.
    </div>

    <div class="text-xs text-gray-500">
        Diterima: 1 Jan 2026 10:21
    </div>

</div>
@endsection
