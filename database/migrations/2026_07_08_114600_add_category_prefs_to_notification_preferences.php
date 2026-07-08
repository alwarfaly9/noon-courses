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
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->boolean('course_alerts')->default(true)->after('recommended_content');
            $table->boolean('achievement_alerts_category')->default(true)->after('course_alerts');
            $table->boolean('community_alerts')->default(true)->after('achievement_alerts_category');
            $table->boolean('payment_alerts')->default(true)->after('community_alerts');
            $table->boolean('marketing_alerts')->default(true)->after('payment_alerts');
            $table->boolean('security_alerts')->default(true)->after('marketing_alerts');
            $table->boolean('system_alerts')->default(true)->after('security_alerts');
        });
    }

    public function down(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'course_alerts',
                'achievement_alerts_category',
                'community_alerts',
                'payment_alerts',
                'marketing_alerts',
                'security_alerts',
                'system_alerts',
            ]);
        });
    }
};
