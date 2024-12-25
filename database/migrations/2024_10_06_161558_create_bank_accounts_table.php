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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');;
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('routing_number');
            $table->enum('account_type', ['checking', 'savings'])->default('checking');
            $table->string('currency')->nullable();
            $table->enum('bank_type', ['ach', 'wire','swift'])->nullable();
            $table->boolean('is_default_payment_method')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
