<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('login', function() {
    return response()->json(['message' => 'Unauthorized.'], 401);
});

Route::post('login', [ 'as' => 'login']);
Route::get('media/{mediaId}/{fileName}', [MediaController::class, 'show'])->name('media.show');