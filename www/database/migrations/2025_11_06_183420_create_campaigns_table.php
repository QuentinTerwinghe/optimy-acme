<?php

declare(strict_types=1);

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
        Schema::create('campaigns', function (Blueprint $table) {
            // Primary key as UUID stored as binary(16) for better performance
            $table->binary('id', 16)->primary();

            // Campaign details
            $table->string('title');
            $table->text('description')->nullable();

            // Financial information
            $table->decimal('goal_amount', 10, 2)->unsigned();
            $table->decimal('current_amount', 10, 2)->unsigned()->default(0);
            $table->string('currency', 3); // ISO 4217 currency code

            // Campaign timeline
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // Campaign status
            $table->string('status', 20)->default('draft');

            // Timestamps - custom names for HasTimestamps trait
            $table->dateTime('creation_date')->nullable();
            $table->dateTime('update_date')->nullable();

            // User tracking - for HasUserTracking trait
            $table->foreignUuid('created_by')->nullable();
            $table->foreignUuid('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for common queries
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['status', 'start_date', 'end_date']); // Composite index for filtering active campaigns
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
