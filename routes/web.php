<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WelcomeController;
use Inertia\Inertia;

Route::get('/', WelcomeController::class)->name('inicio');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

require __DIR__.'/panel.php';
