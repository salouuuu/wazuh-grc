<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IsoMappingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [IncidentController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/incidents', [IncidentController::class, 'index'])
    ->middleware(['auth'])
    ->name('incidents.index');


Route::post('/incidents/bulk', [IncidentController::class, 'bulk'])
    ->middleware(['auth'])
    ->name('incidents.bulk');

Route::get('/correspondance-iso', [IsoMappingController::class, 'index'])
    ->middleware(['auth'])
    ->name('iso.mapping');

Route::get('/incidents/{incident}', [IncidentController::class, 'show'])
    ->middleware(['auth'])
    ->name('incidents.show');

Route::put('/incidents/{incident}', [IncidentController::class, 'update'])
    ->middleware(['auth'])
    ->name('incidents.update');
require __DIR__.'/auth.php';
