<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eternal_ranking_players', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('email_hash', 32)->unique();
            $table->string('nickname', 50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eternal_ranking_players');
    }
};
