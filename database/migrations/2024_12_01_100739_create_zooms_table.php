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
        Schema::create('zooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('client_id');
            $table->string('client_secret');
            $table->boolean('is_connected')->default(false);
            $table->text('refresh_token');
            $table->text('access_token');
            $table->string('token_uri');
            $table->json('scopes'); // Store as JSON
            $table->string('zoom_user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zooms');
    }
};
