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
        Schema::table('rankings', function (Blueprint $table) {
            $table->dropForeign(['hof_id']);
            $table->dropForeign(['hof_user_id']);
            $table->dropColumn('hof_id');
            $table->dropColumn('hof_user_id');
            $table->foreignUlid('hof_id')->constrained('hall_of_fames')->onDelete('cascade');
            $table->foreignUlid('hof_user_id')->constrained('hof_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
