<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PhoneSetupController extends Controller
{
    public function create()
    {
        return view('auth.setup-phone');
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20', 
        ]);

        $user = $request->user();
        $user->update([
            'phone_number' => $request->phone_number
        ]);

        return redirect()->route('inbox.index')->with('success', 'Phone number saved!');
    }
}
