<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('overtime_request_id')->constrained('overtime_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->time('actual_start_time');
            $table->time('actual_end_time');
            $table->decimal('amount', 15, 2)->default(0);
            $table->foreignId('level2_approver_id')->nullable()->constrained('users'); // User chosen manually for Level 2
            $table->string('status')->default('pending'); // RequestStatus Enum
            $table->tinyInteger('current_approval_level')->default(1);
            $table->text('rejected_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            
            // Payment info
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_claims');
    }
};
