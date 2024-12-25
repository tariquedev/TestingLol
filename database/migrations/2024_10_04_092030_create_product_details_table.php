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
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('button_text')->nullable();
            $table->text('thumbnail_description')->nullable();
            $table->string('header_image')->nullable();
            $table->string('bottom_title')->nullable();
            $table->string('bottom_button_text')->nullable();
            $table->string('promo_video')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
