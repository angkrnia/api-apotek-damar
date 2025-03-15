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
        Schema::create('stock_opname_product_line', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opname_header_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('qty_system');
            $table->integer('qty_real');
            $table->integer('qty_diff');
            $table->string('note')->nullable();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('opname_header_id')->references('id')->on('stock_opname')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_product_line');
    }
};
