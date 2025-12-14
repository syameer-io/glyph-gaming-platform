<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes request_type nullable as part of simplifying the matchmaking system
     * to support bidirectional discovery (players find teams AND teams find players).
     */
    public function up(): void
    {
        // Drop the composite index that uses request_type first
        Schema::table('matchmaking_requests', function (Blueprint $table) {
            $table->dropIndex(['request_type', 'status']);
        });

        // Modify the column to be nullable
        // Using raw SQL because Laravel's change() doesn't handle ENUM well
        DB::statement("ALTER TABLE matchmaking_requests MODIFY COLUMN request_type VARCHAR(50) NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set any NULL values back to default before reverting
        DB::table('matchmaking_requests')
            ->whereNull('request_type')
            ->update(['request_type' => 'find_team']);

        // Revert to ENUM with NOT NULL
        DB::statement("ALTER TABLE matchmaking_requests MODIFY COLUMN request_type ENUM('find_team', 'find_teammates', 'substitute') NOT NULL DEFAULT 'find_team'");

        // Recreate the index
        Schema::table('matchmaking_requests', function (Blueprint $table) {
            $table->index(['request_type', 'status']);
        });
    }
};
