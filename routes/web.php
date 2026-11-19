<?php

use App\Http\Controllers\QuestionController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::resource('topics', TopicController::class)
    ->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

Route::resource('questions', QuestionController::class)
    ->except(['show']);
Route::post('questions/{question}/publish', [QuestionController::class, 'publish'])->name('questions.publish');
Route::post('questions/{question}/unpublish', [QuestionController::class, 'unpublish'])->name('questions.unpublish');

require __DIR__.'/settings.php';
