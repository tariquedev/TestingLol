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
        Schema::create('default_withdraw_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->morphs('payable');
            $table->enum('type',['bank', 'stripe', 'wise'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_withdraw_methods');
    }
};
