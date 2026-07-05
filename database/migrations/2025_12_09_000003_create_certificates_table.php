<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_id')->unique(); // Unique public ID (e.g., CERT-123456)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->timestamp('issued_at');
            $table->string('download_url')->nullable(); // In case we store it as a file later
            $table->timestamps();

            $table->unique(['user_id', 'course_id']); // One certificate per course per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
