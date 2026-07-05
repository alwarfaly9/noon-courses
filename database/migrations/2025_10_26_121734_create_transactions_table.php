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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['purchase', 'enrollment', 'credit_purchase', 'refund', 'withdraw']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->decimal('platform_commission', 10, 2)->nullable();
            $table->decimal('instructor_earnings', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            // $table->foreignId('credit_card_id')->nullable()->constrained('credit_cards')->onDelete('set null'); // Removed for MySQL compatibility
            $table->unsignedBigInteger('credit_card_id')->nullable(); // Just keep the field for now, no FK
            // $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null'); // Removed for MySQL compatibility
            $table->unsignedBigInteger('coupon_id')->nullable(); // Just keep the field for now, no FK
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
