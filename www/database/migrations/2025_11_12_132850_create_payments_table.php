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
        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('donation_id', 36);
            $table->string('payment_method', 50); // 'fake', 'paypal', 'credit_card'
            $table->string('status', 50); // 'pending', 'processing', 'completed', 'failed', 'refunded'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Transaction details
            $table->string('transaction_id')->nullable(); // External payment provider transaction ID
            $table->text('gateway_response')->nullable(); // Raw response from payment gateway

            // Error handling
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Additional flexible data

            // Timestamps for tracking payment lifecycle
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('donation_id')
                  ->references('id')
                  ->on('donations')
                  ->onDelete('cascade');

            // Indexes
            $table->index('donation_id');
            $table->index('payment_method');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
