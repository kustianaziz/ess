<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('renewal_number')->unique();
            $table->foreignId('domain_id')->constrained('domains');
            $table->tinyInteger('period_year')->default(1);
            $table->date('old_expired_date');
            $table->date('new_expired_date')->nullable();
            $table->enum('status', ['pending', 'invoiced_customer', 'paid_customer', 'renewed_vendor', 'paid_vendor', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            // Foreign key to vendor_payments must be defined later or explicitly since order matters
            $table->unsignedBigInteger('vendor_payment_id')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_requests');
    }
};