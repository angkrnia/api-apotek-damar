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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number');
            $table->string('patient_name')->nullable()->default('Tamu');
            $table->enum('payment_method', ['CASH', 'TRANSFER', 'CREDIT', 'DEBIT', 'QRIS'])->default('CASH');
            $table->enum('status', ['PROCESSING', 'SUCCESS', 'CANCELED'])->default('PROCESSING');
            $table->decimal('grand_total', 18, 2);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('change', 18, 2)->default(0);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('cashier_id')->on('users')->references('id')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
