<?php

use App\Http\Controllers\Chat\MessageAttachmentController;
use App\Livewire\Chat\ChatPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', ChatPage::class)->name('dashboard');
    Route::get('messages/{message}/attachment', MessageAttachmentController::class)
        ->name('messages.attachment.download');
});

require __DIR__.'/settings.php';
