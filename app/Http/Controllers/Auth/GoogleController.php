<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Http\Request;

class GoogleController extends Controller
{   
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.modify',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();
        $user = auth()->user();

        $user->update([
            'google_id' => $googleUser->getId(),
            'google_token' => $googleUser->token, // ✅ STRING ASLI
            'google_refresh_token' => $googleUser->refreshToken 
                ?? $user->google_refresh_token,
            'gmail_connected_at' => now(),
        ]);

        // Check for phone number
        if (is_null($user->phone_number)) {
            return redirect()->route('phone.setup');
        }

        return redirect('/inbox');
    }
    public function disconnect(Request $request)
    {
        $user = $request->user();

        $user->update([
            'google_id' => null,
            'google_token' => null,
            'google_refresh_token' => null,
            'gmail_connected_at' => null,
        ]);

        return redirect('/dashboard')->with('status', 'Gmail disconnected');
    }
}
