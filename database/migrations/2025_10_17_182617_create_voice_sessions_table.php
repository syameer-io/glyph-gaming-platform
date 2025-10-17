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
        Schema::create('voice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('agora_channel_name')->unique();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->integer('session_duration')->nullable()->comment('Duration in seconds');
            $table->timestamps();

            // Composite indexes for performance optimization
            $table->index(['channel_id', 'left_at'], 'idx_channel_active');
            $table->index(['user_id', 'left_at'], 'idx_user_active');
            $table->index('server_id', 'idx_server');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_sessions');
    }
};
