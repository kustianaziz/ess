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
        Schema::create('business_trip_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_letter_number')->nullable();
            $table->string('destination');
            $table->text('purpose');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('transportation_type')->nullable();
            $table->decimal('estimated_budget', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->tinyInteger('current_approval_level')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_trip_requests');
    }
};
