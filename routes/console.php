<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dani:sync-movies')->hourly();
Schedule::command('dani:keep-alive')->weekly();
// Importa lo nuevo de tu cuenta cada hora
Schedule::command('dani:import-master')->hourly();

// Distribuye a tus clientes cada hora
Schedule::command('dani:sync-movies')->hourly();

// Mantiene viva la caché semanalmente
Schedule::command('dani:keep-alive')->weekly();