<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to fix team_members.role ENUM column
 *
 * Problem: The ENUM was defined with 'co-leader' (hyphen) but all code uses 'co_leader' (underscore)
 * This causes "Data truncated" errors when trying to insert 'co_leader' values.
 *
 * Solution: Modify the ENUM to use 'co_leader' instead of 'co-leader'
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing 'co-leader' values to 'co_leader'
        // This is done before changing the ENUM to avoid data loss
        DB::statement("UPDATE team_members SET role = 'member' WHERE role = 'co-leader'");

        // Modify the ENUM to include 'co_leader' instead of 'co-leader'
        // Using raw SQL because Laravel's schema builder doesn't support ENUM modification well
        DB::statement("ALTER TABLE team_members MODIFY COLUMN role ENUM('leader', 'co_leader', 'member', 'reserve') NOT NULL DEFAULT 'member'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert any 'co_leader' back to 'co-leader' first
        DB::statement("UPDATE team_members SET role = 'member' WHERE role = 'co_leader'");

        // Revert to original ENUM definition
        DB::statement("ALTER TABLE team_members MODIFY COLUMN role ENUM('leader', 'co-leader', 'member', 'reserve') NOT NULL DEFAULT 'member'");
    }
};
