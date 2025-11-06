<?php

declare(strict_types=1);

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
        Schema::table('campaigns', function (Blueprint $table) {
            // Add timestamp tracking fields
            $table->dateTime('creation_date')->nullable()->after('status');
            $table->dateTime('update_date')->nullable()->after('creation_date');

            // Add user tracking fields (using unsignedBigInteger to match users table ID format)
            $table->unsignedBigInteger('created_by')->nullable()->after('update_date');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // Add foreign key constraints
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Add indexes for better query performance
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('creation_date');
            $table->index('update_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Drop columns
            $table->dropColumn([
                'creation_date',
                'update_date',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
