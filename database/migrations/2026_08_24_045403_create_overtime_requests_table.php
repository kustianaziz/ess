<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('task_description');
            $table->string('status')->default('pending'); // RequestStatus Enum
            $table->tinyInteger('current_approval_level')->default(1);
            $table->text('rejected_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
