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
     * Converts game_role from enum to VARCHAR(50) to allow
     * flexible game-specific roles without migration changes.
     */
    public function up(): void
    {
        // Convert enum to varchar(50) using raw SQL
        // This preserves existing data while allowing new role values
        DB::statement("ALTER TABLE team_members MODIFY COLUMN game_role VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * Note: This will fail if there are role values not in the original enum.
     * In that case, update those rows first before rolling back.
     */
    public function down(): void
    {
        // Convert back to enum (original values)
        DB::statement("ALTER TABLE team_members MODIFY COLUMN game_role ENUM('tank', 'dps', 'support', 'flex', 'igl', 'entry', 'anchor', 'awper', 'rifler') NULL");
    }
};
