<?php

use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuestionImportController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::resource('questions', QuestionController::class)
    ->except(['show'])
    ->whereNumber('question');
Route::post('questions/{question}/publish', [QuestionController::class, 'publish'])->name('questions.publish');
Route::post('questions/{question}/unpublish', [QuestionController::class, 'unpublish'])->name('questions.unpublish');
Route::post('questions/bulk-publish', [QuestionController::class, 'bulkPublish'])->name('questions.bulkPublish');
Route::post('questions/bulk-unpublish', [QuestionController::class, 'bulkUnpublish'])->name('questions.bulkUnpublish');

Route::prefix('questions/import')->name('questions.import.')->group(function () {
    Route::get('/', [QuestionImportController::class, 'create'])->name('create');
    Route::get('template', [QuestionImportController::class, 'template'])->name('template');
    Route::post('preview', [QuestionImportController::class, 'preview'])->name('preview');
    Route::post('/', [QuestionImportController::class, 'store'])->name('store');
    Route::delete('/', [QuestionImportController::class, 'discard'])->name('discard');
});

Route::resource('topics', TopicController::class)
    ->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

require __DIR__.'/settings.php';
