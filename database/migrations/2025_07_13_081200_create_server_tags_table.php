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
        Schema::create('server_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('tag_type', ['game', 'skill_level', 'region', 'language', 'activity_time']);
            $table->string('tag_value', 100);
            $table->integer('weight')->default(1);
            $table->timestamps();
            
            $table->index(['server_id', 'tag_type'], 'server_tags_server_type_idx');
            $table->index(['tag_type', 'tag_value'], 'server_tags_type_value_idx');
            $table->unique(['server_id', 'tag_type', 'tag_value'], 'server_tags_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_tags');
    }
};
