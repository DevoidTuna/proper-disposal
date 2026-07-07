<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela de pontos de coleta de resíduos.
     */
    public function up(): void
    {
        Schema::create('collection_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // nome do local
            $table->string('address');              // endereço textual
            $table->decimal('latitude', 10, 7);     // coordenada
            $table->decimal('longitude', 10, 7);    // coordenada
            $table->json('waste_types');            // lista de tipos de resíduo (array)
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status')->default('pending'); // 'pending' ou 'approved'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_points');
    }
};
