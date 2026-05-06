<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices_received', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ownership')->nullable();
            $table->unsignedBigInteger('id_entities')->nullable();
            $table->json('data_ownership')->nullable();
            $table->json('data_entities')->nullable();
            $table->string('type_invoice', 10)->default('TD01');
            $table->string('n_invoice', 100);
            $table->date('data_invoice');
            $table->decimal('importo_totale', 15, 2)->default(0);
            $table->text('causale')->nullable();
            $table->string('divisa', 3)->default('EUR');
            $table->string('status', 20)->default('draft');
            $table->string('sdi_id', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('id_ownership')->references('id_proprieta')->on('ownership')->onDelete('set null');
            $table->foreign('id_entities')->references('id_cliente')->on('entities')->onDelete('set null');
            
            $table->index('n_invoice');
            $table->index('data_invoice');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices_received');
    }
};