<?php

use App\Http\Controllers\ZoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "You are hacked!";
});

// Route::get('/zoom/connect', [ZoomController::class, 'connect'])->name('zoom.connect');
// Route::get('/zoom/callback', [ZoomController::class, 'callback'])->name('zoom.callback');
// Route::post('/zoom/disconnect', [ZoomController::class, 'disconnect'])->name('zoom.disconnect');

Route::get('/', [ZoomController::class, 'generateAuthLink'])->name('zoom.authLol');
Route::get('/zoom/auth', [ZoomController::class, 'generateAuthLink'])->name('zoom.auth');
Route::get('/oauth/callback', [ZoomController::class, 'handleCallback'])->name('zoom.callback');
