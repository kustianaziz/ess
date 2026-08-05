<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('renewal_request_id')->nullable()->constrained('renewal_requests');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->foreignId('paid_by')->constrained('users');
            $table->timestamps();
        });
        
        // Add foreign key constraint to renewal_requests now that vendor_payments exists
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->foreign('vendor_payment_id')->references('id')->on('vendor_payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->dropForeign(['vendor_payment_id']);
        });
        Schema::dropIfExists('vendor_payments');
    }
};