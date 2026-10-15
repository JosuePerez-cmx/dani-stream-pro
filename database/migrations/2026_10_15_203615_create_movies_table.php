<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->index(); 
            $table->string('info_hash', 40)->index(); 
            $table->string('title'); 
            $table->string('filename'); // Ej: [Dani] Twilight 2008 2160p [🌎 Latino].mkv
            $table->bigInteger('size_bytes'); 
            $table->string('resolution'); // '2160p' o '1080p'
            $table->string('provider')->default('realdebrid');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_kept_alive_at')->nullable();
            $table->timestamps();

            // Evita guardar la misma película en la misma resolución dos veces
            $table->unique(['imdb_id', 'resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};