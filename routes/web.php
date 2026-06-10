<?php

declare(strict_types=1);

use App\Http\Controllers\FullscreenController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::name('phases.')->group(function () {
    Route::livewire('/', 'phase::generator')->name('generator');
    Route::get('/game/print', PrintController::class)->name('print');
    Route::get('/game/fullscreen', FullscreenController::class)->name('fullscreen');
});
