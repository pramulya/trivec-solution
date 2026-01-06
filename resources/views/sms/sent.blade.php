@extends('layouts.mail')

@section('title', 'SMS Sent')

@section('content')
<div class="flex flex-col h-full">

    {{-- VUE MESSAGE LIST --}}
    <div id="app" class="flex-1 overflow-y-auto bg-gray-900">
        <sms-inbox :initial-messages='@json($messages)' folder="sent"></sms-inbox>
    </div>

</div>
@endsection
