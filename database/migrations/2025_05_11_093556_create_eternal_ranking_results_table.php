<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eternal_ranking_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('player_id');
            $table->ulid('ranking_id');
            $table->unsignedBigInteger('score')->default(0);
            $table->decimal('pct', 5, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('eternal_ranking_players');
            $table->foreign('ranking_id')->references('id')->on('eternal_rankings');
            $table->unique(['player_id', 'ranking_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('eternal_ranking_results');
    }
};
