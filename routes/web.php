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

    Route::post('/inbox/{message}/star', [InboxController::class, 'toggleStar'])
        ->name('inbox.star');
    
    // Attachments
    Route::get('/attachments/{id}', [InboxController::class, 'downloadAttachment'])
        ->name('attachments.download');

    // Folders
    Route::post('/gmail/sync', [InboxController::class, 'sync'])
        ->name('gmail.sync');

    Route::get('/sent', [InboxController::class, 'sent'])->name('sent');
    Route::get('/starred', [InboxController::class, 'starred'])->name('starred');
    Route::get('/drafts', [InboxController::class, 'drafts'])->name('drafts.index');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/drafts', [InboxController::class, 'drafts'])->name('drafts.index');

    Route::get('/spam', [\App\Http\Controllers\InboxController::class, 'spam'])->name('spam.index');
    Route::post('/inbox/{message}/spam', [\App\Http\Controllers\InboxController::class, 'markAsSpam'])->name('inbox.spam');
    Route::delete('/inbox/{message}', [\App\Http\Controllers\InboxController::class, 'destroy'])->name('inbox.destroy');

    Route::get('/compose', [\App\Http\Controllers\ComposeController::class, 'index'])->name('compose.index');
    Route::post('/send-email', [\App\Http\Controllers\ComposeController::class, 'send'])->name('compose.send');

    Route::get('/trash', [\App\Http\Controllers\InboxController::class, 'trash'])->name('trash.index');

});

Route::post('/ai-mode/toggle', function () {
    $user = auth()->user();
    $user->update([
        'ai_enabled' => ! $user->ai_enabled
    ]);

    return back();
})->middleware('auth')->name('ai.toggle');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {

    Route::get('/sms/inbox', [\App\Http\Controllers\SmsController::class, 'inbox'])->name('sms.inbox');
    Route::post('/sms/send', [\App\Http\Controllers\SmsController::class, 'send'])->name('sms.send');
    Route::post('/sms/store', [\App\Http\Controllers\SmsController::class, 'store'])->name('sms.store');
    Route::post('/sms/sync-termii', [\App\Http\Controllers\SmsController::class, 'sync'])->name('sms.sync.termii');
    Route::get('/sms/sent', [\App\Http\Controllers\SmsController::class, 'sent'])->name('sms.sent');
    Route::get('/sms/spam', [\App\Http\Controllers\SmsController::class, 'spam'])->name('sms.spam');
    Route::get('/sms/show', fn () => view('sms.show'));
    Route::delete('/sms/{sms}', [\App\Http\Controllers\SmsController::class, 'destroy'])->name('sms.destroy');
    Route::post('/sms/{sms}/spam', [\App\Http\Controllers\SmsController::class, 'markAsSpam'])->name('sms.spam.mark');

});