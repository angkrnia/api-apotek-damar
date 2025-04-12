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
        Schema::create('sales_product_line', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('cart_id');
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('product_unit');
            $table->decimal('product_unit_price', 18, 2);
            $table->decimal('product_unit_cost', 18, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 18, 2);
            $table->string('note')->nullable();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->on('sales')->references('id')->onDelete('cascade');
            $table->foreign('cart_id')->on('carts')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_product_line');
    }
};
