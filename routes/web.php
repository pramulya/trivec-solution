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
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('google.callback');
});

Route::post('/auth/google/disconnect', [
    \App\Http\Controllers\Auth\GoogleController::class,
    'disconnect'
])->name('google.disconnect');

Route::middleware(['auth'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index'])
        ->name('inbox');
});

Route::middleware('auth')->post('/gmail/sync', [InboxController::class, 'sync'])
    ->name('gmail.sync');

Route::middleware(['auth'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/', function () {
        return redirect('/inbox');
    });

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/sent', [InboxController::class, 'sent'])->name('sent');
    Route::get('/starred', [InboxController::class, 'starred'])->name('starred');

    Route::post('/gmail/sync', [InboxController::class, 'sync'])->name('gmail.sync');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
    Route::post('/auth/google/disconnect', [GoogleController::class, 'disconnect'])->name('google.disconnect');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{message}', [InboxController::class, 'show'])->name('inbox.show');
});

require __DIR__.'/auth.php';
