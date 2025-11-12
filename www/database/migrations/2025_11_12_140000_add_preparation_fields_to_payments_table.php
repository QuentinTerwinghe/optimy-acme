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
        Schema::table('payments', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('payment_method');
            $table->string('redirect_url')->nullable()->after('payload');
            $table->timestamp('prepared_at')->nullable()->after('redirect_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payload', 'redirect_url', 'prepared_at']);
        });
    }
};
