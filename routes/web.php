<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddonController;

// Ruta principal (opcional, para evitar 404 en la raíz)
Route::get('/', function () {
    return "Servidor Dani Cinema+ Activo";
});

// Rutas de Stremio
Route::get('/manifest.json', [AddonController::class, 'manifest']);
Route::get('/stream/{type}/{id}', [AddonController::class, 'stream']);

// Ruta del Inyector Web
Route::get('/inject', [AddonController::class, 'injectMagnet']);