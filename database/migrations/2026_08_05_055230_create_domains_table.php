<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('name');
            $table->enum('type', ['domain', 'hosting', 'vps', 'email', 'other']);
            $table->date('purchase_date');
            $table->date('expired_date');
            $table->decimal('price_customer', 15, 2);
            $table->decimal('cost_vendor', 15, 2);
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};