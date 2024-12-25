<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->nullable()->change();
            $table->string('model_type')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->nullable(false)->change();
            $table->string('model_type')->nullable(false)->change();
        });
    }
};
