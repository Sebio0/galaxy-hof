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
        Schema::create('server_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('server_name');
            $table->string('database_host');
            $table->string('database_name');
            $table->string('database_user');
            $table->string('database_password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_instances');
    }
};
