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
        Schema::create('campaign_tag', function (Blueprint $table) {
            // Foreign keys to campaigns and tags
            $table->uuid('campaign_id'); // UUID as string to match Campaign model behavior
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');

            // Timestamp for when tag was attached
            $table->timestamp('created_at')->nullable();

            // Composite primary key
            $table->primary(['campaign_id', 'tag_id']);

            // Foreign key constraint for campaign (disabled due to UUID type mismatch with binary in campaigns table)
            // The campaigns table stores UUID as binary(16), but we use char(36) here for Laravel compatibility
            // $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');

            // Indexes for performance
            $table->index('campaign_id');
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_tag');
    }
};
