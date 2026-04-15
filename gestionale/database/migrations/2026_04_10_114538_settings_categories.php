<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings_categories', function (Blueprint $table) {
            $table->id();
            $table->string('titolo');
            $table->string('slug')->unique();
            $table->text('descrizione')->nullable();
            $table->string('tabella_riferimento')->nullable(); // quale tabella usa (contacts, etc)
            $table->integer('ordinamento')->default(0);
            $table->boolean('valid')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('tabella_riferimento');
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings_categories');
    }
};