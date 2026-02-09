<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->json('notification_types')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('email_enabled');
        });

        // Seed default preferences for all existing users
        DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            $preferences = [];
            foreach ($users as $user) {
                $preferences[] = [
                    'user_id' => $user->id,
                    'email_enabled' => true,
                    'notification_types' => json_encode([
                        'comment' => true,
                        'report_status' => true,
                        'proposal_status' => true,
                        'announcement' => true,
                        'system' => true,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($preferences)) {
                DB::table('user_notification_preferences')->insert($preferences);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
