<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ZoomController;
use Illuminate\Support\Facades\Route;


// Route::get('/zoom/connect', [ZoomController::class, 'connect'])->name('zoom.connect');
// Route::get('/zoom/callback', [ZoomController::class, 'callback'])->name('zoom.callback');
// Route::post('/zoom/disconnect', [ZoomController::class, 'disconnect'])->name('zoom.disconnect');

Route::get('/', [ZoomController::class, 'generateAuthLink'])->name('zoom.authLol');
Route::get('/zoom/auth', [ZoomController::class, 'generateAuthLink'])->name('zoom.auth');
Route::get('/zoom/callback', [ZoomController::class, 'handleCallback'])->name('zoom.callback');
Route::get('/zoom/disconnect', [ZoomController::class, 'disconnectZoomApp'])->name('zoom.disconnect');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
