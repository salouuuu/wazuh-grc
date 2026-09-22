<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentController;

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

Route::get('/incidents', [IncidentController::class, 'index'])
    ->middleware(['auth'])
    ->name('incidents.index');

Route::get('/dashboard', [IncidentController::class, 'dashboard'])
    ->name('dashboard');

Route::get('/incidents/{incident}', [IncidentController::class, 'show'])
    ->middleware(['auth'])
    ->name('incidents.show');

Route::put('/incidents/{incident}', [IncidentController::class, 'update'])
    ->middleware(['auth'])
    ->name('incidents.update');
require __DIR__.'/auth.php';
