<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\InboxController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect('/inbox'));

    // Gmail OAuth
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('google.callback');
    Route::post('/auth/google/disconnect', [GoogleController::class, 'disconnect'])
        ->name('google.disconnect');

    // Inbox
    Route::get('/inbox', [InboxController::class, 'index'])
        ->name('inbox.index');
    Route::get('/inbox/{message}', [InboxController::class, 'show'])
        ->name('inbox.show');

    // Sync Gmail
    Route::post('/gmail/sync', [InboxController::class, 'sync'])
        ->name('gmail.sync');

    Route::get('/sent', [InboxController::class, 'sent'])->name('sent');
    Route::get('/starred', [InboxController::class, 'starred'])->name('starred');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/drafts', function () {
        return view('folders.drafts');
    })->name('drafts.index');

    Route::get('/spam', function () {
        return view('folders.spam');
    })->name('spam.index');

    Route::get('/trash', function () {
        return view('folders.trash');
    })->name('trash.index');

});

Route::post('/ai-mode/toggle', function () {
    $user = auth()->user();
    $user->update([
        'ai_mode' => ! $user->ai_mode
    ]);

    return back();
})->middleware('auth')->name('ai.toggle');

require __DIR__.'/auth.php';
