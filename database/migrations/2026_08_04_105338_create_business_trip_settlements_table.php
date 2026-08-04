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
        Schema::create('business_trip_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique();
            $table->foreignId('business_trip_request_id')->constrained('business_trip_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_actual_cost', 15, 2)->default(0);
            $table->decimal('advance_amount', 15, 2)->default(0);
            $table->decimal('difference_amount', 15, 2)->default(0);
            $table->text('trip_report')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_trip_settlements');
    }
};
