<?php

use App\Http\Controllers\Guest\LandingController;
use App\Http\Controllers\ProfileController;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/antrian', function (SettingService $settings) {
    return view('companion.queue', [
        'appName' => $settings->get('app_name', 'Antrian Pasien'),
    ]);
})->name('companion.queue');

Route::get('/petugas', function (SettingService $settings) {
    return view('petugas.dashboard', [
        'appName' => $settings->get('app_name', 'Antrian Pasien'),
    ]);
})->name('petugas.dashboard');

Route::get('/dashboard', function (Request $request) {
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
