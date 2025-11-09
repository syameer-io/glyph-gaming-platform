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
        Schema::table('teams', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['server_id']);

            // Make server_id nullable
            $table->foreignId('server_id')->nullable()->change();

            // Re-add the foreign key constraint with nullable support
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop the nullable foreign key constraint
            $table->dropForeign(['server_id']);

            // Make server_id required again
            $table->foreignId('server_id')->nullable(false)->change();

            // Re-add the foreign key constraint with cascade
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }
};
