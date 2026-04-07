<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['cliente', 'fornitore', 'entrambi'])->default('cliente');
            $table->string('ragione_sociale');
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('persona_riferimento')->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('password')->nullable();
            $table->string('stato')->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            $table->integer('id_gruppo')->default(0);
            $table->boolean('valid')->default(true);
            $table->timestamp('data_inserimento')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('email');
            $table->index('valid');
            $table->index('entity_type');
            $table->index('partita_iva');
            $table->index('codice_fiscale');
            $table->index('ragione_sociale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};