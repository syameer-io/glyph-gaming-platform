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
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('edited_at');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            $table->foreignId('pinned_by')->nullable()->after('pinned_at')
                  ->constrained('users')->nullOnDelete();

            // Add index for faster pinned message queries
            $table->index(['channel_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['channel_id', 'is_pinned']);
            $table->dropForeign(['pinned_by']);
            $table->dropColumn(['is_pinned', 'pinned_at', 'pinned_by']);
        });
    }
};
