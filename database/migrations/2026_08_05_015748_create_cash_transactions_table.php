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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->string('category'); // perjalanan_dinas, reimburse, pembayaran_bulanan, operasional_lain, setoran_kas, lainnya
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description');
            $table->date('transaction_date');
            $table->nullableMorphs('source'); // Polymorphic relation to ReimbursementRequest, OperationalRequest, BusinessTripRequest, MonthlyBillPayment
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('posted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
