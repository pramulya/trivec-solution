@extends('layouts.mail')

@section('title', 'SMS Inbox')

@section('content')
<div class="divide-y divide-gray-800">

@foreach ([
    ['from' => '+628123456789', 'text' => 'OTP Anda adalah 123456', 'time' => '10:21'],
    ['from' => 'Bank XYZ', 'text' => 'Transaksi Rp1.200.000 berhasil', 'time' => '09:10'],
    ['from' => 'Promo', 'text' => 'Diskon besar! Klik link sekarang', 'time' => 'Kemarin'],
] as $sms)

<a href="/sms/show"
   class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition">

    <span class="w-3 h-3 rounded-full bg-blue-500"></span>

    <div class="flex-1 min-w-0">
        <div class="text-sm text-gray-300 truncate">
            {{ $sms['from'] }}
        </div>
        <div class="text-sm text-gray-400 truncate">
            {{ $sms['text'] }}
        </div>
    </div>

    <div class="text-xs text-gray-500">
        {{ $sms['time'] }}
    </div>
</a>

@endforeach

</div>
@endsection
