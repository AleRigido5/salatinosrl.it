<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_entities')->constrained('entities')->cascadeOnDelete();
            $table->foreignId('id_settings')->constrained('settings')->cascadeOnDelete();
            $table->string('valore');
            $table->boolean('principale')->default(false);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('id_entities');
            $table->index('id_settings');
            $table->index('valore');
            $table->index(['id_entities', 'id_settings']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};