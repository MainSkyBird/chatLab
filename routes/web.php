<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ChatParticipantController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('chats/{chat}/messages', [ChatMessageController::class, 'index'])
    ->middleware(['auth'])
    ->name('chats.messages.index');

Route::post('chats/{chat}/messages', [ChatMessageController::class, 'store'])
    ->middleware(['auth'])
    ->name('chats.messages.store');

Route::get('chats', [ChatController::class, 'index'])
    ->middleware(['auth'])
    ->name('chats.index');

Route::post('chats', [ChatController::class, 'store'])
    ->middleware(['auth'])
    ->name('chats.store');

Route::post('chats/{chat}/participants', [ChatParticipantController::class, 'store'])
    ->middleware(['auth'])
    ->name('chats.participants.store');
