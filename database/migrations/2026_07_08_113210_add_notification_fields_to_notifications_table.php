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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->string('priority')->default('normal')->after('category');
            $table->string('action_url')->nullable()->after('data');
            $table->string('image')->nullable()->after('action_url');
            $table->json('metadata')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['category', 'priority', 'action_url', 'image', 'metadata']);
        });
    }
};
