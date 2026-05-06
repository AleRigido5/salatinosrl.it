<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_row', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('document_type', 20); // 'invoice_received' o 'invoice_sent'
            $table->unsignedBigInteger('id_cost_center')->nullable();
            $table->text('description');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->unsignedBigInteger('vat_rate_id')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('id_cost_center')->references('id')->on('cost_centers')->onDelete('set null');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates')->onDelete('set null');
            $table->index(['document_id', 'document_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_row');
    }
};