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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre o alias del cliente
            $table->text('rd_api_token'); // Token privado de Real-Debrid del cliente
            $table->boolean('is_active')->default(true); // Por si algún cliente no paga, lo desactivas aquí
            $table->timestamp('last_synced_at')->nullable(); // Para saber cuándo fue la última vez que se le inyectaron películas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
