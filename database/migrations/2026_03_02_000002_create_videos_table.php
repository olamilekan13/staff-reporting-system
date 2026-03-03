<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('video_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type', 50); // upload, youtube, vimeo, m3u8, embed
            $table->text('source_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->string('target_type', 20)->default('all'); // all, departments, users
            $table->timestamp('publish_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('target_type');
            $table->index('publish_at');
        });

        Schema::create('video_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['video_id', 'department_id']);
        });

        Schema::create('video_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['video_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_user');
        Schema::dropIfExists('video_department');
        Schema::dropIfExists('videos');
    }
};
