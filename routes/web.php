<?php

use App\Http\Controllers\Companion\CompanionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guest\LandingController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/antrian', [CompanionController::class, 'page'])->name('companion.queue');

Route::get('/petugas', [PetugasController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:petugas,admin'])
    ->name('petugas.dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:petugas,admin'])
    ->name('dashboard');

Route::get('/profile', [ProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->middleware('auth')->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('auth')->name('profile.destroy');

require __DIR__.'/auth.php';
