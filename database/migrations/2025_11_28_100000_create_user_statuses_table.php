<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 2: Member List Enhancement
     * Creates user_statuses table for Discord-style status management.
     *
     * Status types:
     * - online: User is active and available
     * - idle: User is away/inactive (auto-set after inactivity)
     * - dnd: Do Not Disturb (user-set, suppresses notifications)
     * - offline: User is disconnected/invisible
     */
    public function up(): void
    {
        Schema::create('user_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['online', 'idle', 'dnd', 'offline'])->default('online');
            $table->string('custom_text', 128)->nullable();
            $table->string('custom_emoji', 32)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Ensure one status per user
            $table->unique('user_id');

            // Index for querying by status (useful for presence lists)
            $table->index('status');

            // Index for cleanup of expired statuses
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statuses');
    }
};
