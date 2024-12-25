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
        Schema::create('coaching_products', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->nullable();
            $table->decimal('duration')->default(30);
            $table->string('timezone')->nullable();
            $table->string('platform')->nullable();
            $table->integer('max_attendee')->default(1);
            $table->enum('interval', ['min', 'hour', 'day', 'month'])->default('min');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_products');
    }
};
