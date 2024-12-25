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
        Schema::create('group_call_products', function (Blueprint $table) {
            $table->id();
            $table->decimal('quantity')->default(1);
            $table->decimal('duration');
            $table->enum('interval', ['min', 'hour', 'day'])->default('min');
            $table->string('timezone');
            $table->string('platform')->nullable();
            $table->decimal('max_attendee')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_call_products');
    }
};
