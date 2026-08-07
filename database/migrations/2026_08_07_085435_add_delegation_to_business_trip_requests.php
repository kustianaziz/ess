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
        Schema::table('business_trip_requests', function (Blueprint $table) {
            $table->boolean('is_delegated')->default(false)->after('user_id');
            $table->foreignId('assigned_to')->nullable()->after('is_delegated')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_trip_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['is_delegated', 'assigned_to']);
        });
    }
};
