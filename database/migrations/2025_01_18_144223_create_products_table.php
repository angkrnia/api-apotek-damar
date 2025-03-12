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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->string('image')->nullable();
            $table->string('type')->default('Bebas');
            $table->string('side_effect')->nullable();
            $table->string('rack_location')->nullable();
            $table->text('description')->nullable();
            $table->string('dosage')->nullable();
            $table->decimal('purchase_price', 18, 2)->default(0);
            $table->string('indication')->nullable();
            $table->boolean('is_need_receipt')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->string('created_by');
            $table->string('update_by')->nullable();

            $table->foreign('category_id')->on('categories')->references('id')->onDelete('cascade');
            $table->foreign('group_id')->on('groups')->references('id')->onDelete('cascade');
            $table->foreign('base_unit_id')->on('units')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
