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
        Schema::table('servers', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('invite_code');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_chat_id');
            $table->json('telegram_settings')->nullable()->after('telegram_linked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_linked_at', 'telegram_settings']);
        });
    }
};
