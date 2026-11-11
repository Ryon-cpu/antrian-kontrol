<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Guest\LandingController;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::view('/antrian', 'companion.queue')->name('companion.queue');

Route::get('/petugas', function (SettingService $settings) {
    return view('petugas.dashboard', [
        'appName' => $settings->get('app_name', 'Antrian Pasien'),
    ]);
})->name('petugas.dashboard');

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    abort_unless(
        $request->user()->is_active
            && in_array($request->user()->role->value, ['petugas', 'admin'], true),
        403
    );

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
