<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('tabella_riferimento', 50);
            $table->string('valore', 100);
            $table->string('descrizione')->nullable();
            $table->string('icona', 50)->nullable();
            $table->integer('ordinamento')->default(0);
            $table->boolean('valid')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('tabella_riferimento');
            $table->index('valid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};