@extends('layouts.mail')

@section('title', 'SMS Spam')

@section('content')
<div class="flex flex-col h-full">

    {{-- VUE MESSAGE LIST --}}
    <div id="app" class="flex-1 overflow-y-auto bg-gray-900">
        <sms-inbox :initial-messages='@json($messages)' folder="spam"></sms-inbox>
    </div>

</div>
@endsection
