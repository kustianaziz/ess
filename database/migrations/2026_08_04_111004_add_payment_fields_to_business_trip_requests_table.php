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
            $table->decimal('disbursed_budget', 15, 2)->nullable()->after('estimated_budget');
            $table->text('allowance_breakdown')->nullable()->after('disbursed_budget');
            $table->string('payment_reference')->nullable()->after('allowance_breakdown');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_trip_requests', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['disbursed_budget', 'allowance_breakdown', 'payment_reference', 'paid_at', 'paid_by']);
        });
    }
};
