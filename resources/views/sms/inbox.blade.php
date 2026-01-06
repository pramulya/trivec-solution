@extends('layouts.mail')

@section('title', 'SMS Inbox')

@section('content')
<div class="flex flex-col h-full">

    {{-- HEADER / TOOLBAR --}}


    {{-- VUE MESSAGE LIST --}}
    <div id="app" class="flex-1 overflow-y-auto bg-gray-900">
        <sms-inbox :initial-messages='@json($messages)'></sms-inbox>
    </div>

</div>
@endsection
