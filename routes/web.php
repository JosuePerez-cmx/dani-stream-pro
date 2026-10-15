<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StremioController;

Route::get('/manifest.json', [StremioController::class, 'manifest']);
Route::get('/stream/{type}/{id}', [StremioController::class, 'stream']); // <-- ESTA ES LA QUE FALTABA
Route::get('/play/{id}', [StreamController::class, 'play']);

Route::get('/', function () {
    return view('welcome');
});