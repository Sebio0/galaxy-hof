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
        Schema::create('hof_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('hof_id')->references('id')->on('hall_of_fames')->onDelete('restrict');
            $table->string('nickname');
            $table->string('coordinates');
            $table->string('alliance_tag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hof_users');
    }
};
