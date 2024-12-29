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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id'); // Reference to customers table
            $table->foreignId('product_id');
            $table->foreignId('booking_slot_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('charge_id')->nullable(); // Stripe's payment intent ID
            $table->decimal('amount', 10, 2); // Payment amount
            $table->string('currency')->nullable(); // USD or AUD or EUR etc
            $table->string('currency_symbol')->nullable(); // $ or €
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
