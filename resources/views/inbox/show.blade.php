@extends('layouts.mail')

@section('title', $message->subject)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="bg-gray-800 border border-gray-700 rounded p-4 flex items-start justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-100">
                {{ $message->subject }}
            </h1>
            <p class="text-sm text-gray-400 mt-1">
                {{ $message->from }} • {{ $message->created_at->format('d M Y H:i') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            
            {{-- SPAM BUTTON --}}
            <form action="{{ route('inbox.spam', $message) }}" method="POST" onsubmit="return confirm('Report this message as spam?');">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500 transition" title="Report Spam">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </button>
            </form>

            {{-- DELETE BUTTON --}}
            <form action="{{ route('inbox.destroy', $message) }}" method="POST" onsubmit="return confirm('Move to Trash?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-gray-400 hover:text-red-500 transition" title="Delete">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </form>

            {{-- STAR BUTTON --}}
            <form action="{{ route('inbox.star', $message) }}" method="POST">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-yellow-400 transition" title="{{ $message->is_starred ? 'Unstar' : 'Star' }}">
                    <svg class="w-6 h-6 {{ $message->is_starred ? 'text-yellow-400 fill-current' : '' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- AI PHISHING PANEL --}}
    @if(auth()->user()->ai_enabled && $message->is_analyzed)
        <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">

            @php
                $badge = match($message->phishing_label) {
                    'phishing'   => 'bg-red-600',
                    'suspicious' => 'bg-yellow-500 text-black',
                    'safe'       => 'bg-green-600',
                    default      => 'bg-gray-600',
                };

                $rules = json_decode($message->phishing_rules, true) ?? [];
            @endphp

            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded text-sm font-semibold {{ $badge }}">
                    {{ strtoupper($message->phishing_label ?? 'UNKNOWN') }}
                </span>

                <span class="text-sm text-gray-400">
                    Risk Score: {{ $message->phishing_score ?? 0 }}/100
                </span>
            </div>

            {{-- RULES --}}
            @if(count($rules))
                <ul class="list-disc list-inside text-sm text-gray-300">
                    @foreach($rules as $rule)
                        <li>{{ ucfirst(str_replace('_',' ', $rule)) }}</li>
                    @endforeach
                </ul>
            @endif

        </div>
    @endif

    {{-- EMAIL BODY --}}
    <div class="bg-gray-800 border border-gray-700 rounded p-6 overflow-hidden">
        
        <div class="email-content text-sm text-gray-200 leading-relaxed break-words overflow-x-auto" 
             style="color: #e5e7eb; max-width: 100%; word-wrap: break-word; overflow-wrap: break-word;">
            <style>
                /* Wrapper to prevent styles leaking out */
                .email-content { 
                    max-width: 100%; 
                    overflow-x: auto; 
                    font-family: sans-serif; 
                    background-color: #ffffff; /* Force white paper look */
                    padding: 1.5rem; /* Add padding inside the paper */
                    border-radius: 0.5rem; /* Rounded corners */
                }
                
                /* Reset heavily opinionated styles that break email layouts */
                .email-content table { 
                    max-width: 100%;
                    border-collapse: collapse; 
                    /* Remove display: block which breaks table layout */
                    display: table; 
                }
                
                /* Ensure images don't overflow but maintain aspect ratio */
                .email-content img { 
                    max-width: 100% !important; 
                    height: auto !important; 
                    display: inline-block; /* Allows images to sit side-by-side */
                    vertical-align: middle; /* Aligns correctly with text/other images */
                }
                
                /* Hide spacer/blank images */
                .email-content img[src*="_blank.gif"],
                .email-content img[src*="spacer"],
                .email-content img[width="1"],
                .email-content img[height="1"] {
                    display: none !important;
                }

                /* Styling for blockquotes */
                .email-content blockquote { 
                    border-left: 4px solid #d1d5db; 
                    padding-left: 1em; 
                    margin: 1em 0; 
                    color: #4b5563; 
                }

                /* Basic element resets */
                .email-content p { margin: 1em 0; }
                .email-content ul, .email-content ol { padding-left: 2em; margin: 1em 0; }
                .email-content li { margin: 0.5em 0; }
                
                /* Headers */
                .email-content h1, .email-content h2, .email-content h3 { 
                    font-weight: bold; 
                    margin: 1em 0 0.5em;
                }

                /* Links */
                .email-content a { color: #2563eb; text-decoration: underline; }

                /* Force black text on the white background to ensure contrast */
                .email-content, 
                .email-content p, 
                .email-content td, 
                .email-content div, 
                .email-content span,
                .email-content h1, .email-content h2, .email-content h3 {
                    color: #1f2937 !important; /* Dark gray/black */
                }
            </style>
            
            {!! $message->sanitized_body !!}
            
        </div>
    </div>

</div>
@endsection
