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
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');

            $table->foreignId('inviter_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('User who sent the invitation');

            $table->foreignId('invitee_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('User being invited');

            // Invitation details
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'expired'])
                ->default('pending');

            $table->enum('role', ['member', 'co_leader'])
                ->default('member')
                ->comment('Role to assign when invitation is accepted');

            $table->text('message')
                ->nullable()
                ->comment('Optional message from inviter');

            // Timestamps
            $table->timestamp('responded_at')
                ->nullable()
                ->comment('When invitee accepted/declined');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('When invitation expires (default: 7 days from creation)');

            $table->timestamps();

            // Performance indexes
            $table->index(['team_id', 'status']);
            $table->index(['invitee_id', 'status']);
            $table->index(['inviter_id']);
            $table->index('status');
            $table->index('expires_at');

            // Prevent duplicate pending invitations to same user for same team
            // Using composite unique constraint with status for MySQL compatibility
            // (MySQL does not support partial/conditional indexes)
            $table->unique(['team_id', 'invitee_id', 'status'], 'unique_team_invitation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
