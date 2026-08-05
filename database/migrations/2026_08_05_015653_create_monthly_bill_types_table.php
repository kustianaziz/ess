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
        Schema::create('monthly_bill_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor_name')->nullable();
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->tinyInteger('billing_day')->nullable(); // Tanggal jatuh tempo rutin (misal 5, 10, 20)
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_bill_types');
    }
};
