<?php
// database/migrations/2026_04_16_000005_create_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('titolo')->nullable();
            $table->text('note')->nullable();
            $table->string('path_doc')->nullable();
            $table->string('file_name')->unique();
            $table->string('table_ref')->notNull(); // expiration-staff, expiration-vehicle, etc.
            $table->unsignedBigInteger('id_ref')->notNull();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['table_ref', 'id_ref']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};