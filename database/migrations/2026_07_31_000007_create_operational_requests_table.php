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
        Schema::create('operational_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('activity_types')->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_name');
            $table->text('purpose');
            $table->integer('participant_count');
            $table->decimal('estimated_cost', 15, 2);
            $table->string('location');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'paid', 'completed'])->default('draft');
            $table->unsignedTinyInteger('current_approval_level')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_requests');
    }
};
