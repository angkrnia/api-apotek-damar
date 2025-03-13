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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('movement_type', ['IN', 'OUT'])->default('IN');
            $table->integer('qty_in');
            $table->integer('qty_out');
            $table->integer('remaining');
            $table->string('reference_type');
            $table->string('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->string('created_by');
            $table->string('update_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->on('products')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
