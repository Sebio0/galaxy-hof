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
        Schema::create('rankings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('hof_id')->constrained('hall_of_fames')->onDelete('restrict');
            $table->foreignUlid('hof_user_id')->constrained('hof_users')->onDelete('restrict');
            $table->foreignUlid('ranking_type_id')->constrained('ranking_types')->onDelete('restrict');
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
