<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\StaticDataController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('login', function() {
    return response()->json(['message' => 'Unauthorized.'], 401);
});
Route::get('/{provider}', [StaticDataController::class, 'authRedirect'])->name('authRedirect');
Route::get('/auth/{provider}/verify', [StaticDataController::class, 'authCallback']);

Route::post('login', [ 'as' => 'login']);
Route::get('media/{mediaId}/{fileName}', [MediaController::class, 'show'])->name('media.show');