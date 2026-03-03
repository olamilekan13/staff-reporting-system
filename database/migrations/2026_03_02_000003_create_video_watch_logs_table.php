<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_watch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('watchable_type'); // 'App\Models\Video' or 'livestream'
            $table->unsignedBigInteger('watchable_id')->nullable(); // video.id or null for livestream
            $table->string('session_id', 64)->unique();
            $table->timestamp('started_at');
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->string('source', 20); // vod, livestream
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'watchable_type', 'watchable_id'], 'watch_logs_user_watchable_idx');
            $table->index('source');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_watch_logs');
    }
};
