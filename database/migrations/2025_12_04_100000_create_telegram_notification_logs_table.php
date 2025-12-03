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
        Schema::create('telegram_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('notification_type', 50); // goal_completed, goal_progress, new_goal, user_joined, milestone_reached, team_created, team_member_joined, team_member_left
            $table->string('recipient_chat_id', 50);
            $table->text('message_preview')->nullable(); // Truncated to 200 chars
            $table->enum('delivery_status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('server_id');
            $table->index('notification_type');
            $table->index('delivery_status');
            $table->index('sent_at');
            $table->index(['server_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_notification_logs');
    }
};
