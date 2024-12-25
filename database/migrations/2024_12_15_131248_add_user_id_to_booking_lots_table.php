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
        Schema::table('booking_slots', function (Blueprint $table) {
            $table->foreignId('creator_id')->after('product_id')->comment('creator_id mean user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_slots', function (Blueprint $table) {
            //
        });
    }
};
