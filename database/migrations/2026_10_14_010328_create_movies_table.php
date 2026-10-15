<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Ej: [Dani] Crepusculo.2008.2160p.4K.BluRay.x265.Latino.mkv
            $table->string('imdb_id')->nullable(); // Ej: tt1099212 (opcional para Stremio)
            $table->text('magnet_link'); // El enlace Magnet o Hash maestro de tu subida
            $table->boolean('is_active')->default(true); // Para el bot de supervivencia y distribución
            $table->timestamp('last_kept_alive_at')->nullable(); // Última vez que el bot refrescó la caché en RD
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
