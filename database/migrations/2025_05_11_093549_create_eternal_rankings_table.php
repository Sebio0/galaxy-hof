<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eternal_rankings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('round_number')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eternal_rankings');
    }
};
