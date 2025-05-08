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
        Schema::create('ranking_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key_name')->unique();
            $table->string('display_name');
            $table->enum('type', ['resource', 'fleet', 'defense', 'misc'])->default('resource')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_types');
    }
};
