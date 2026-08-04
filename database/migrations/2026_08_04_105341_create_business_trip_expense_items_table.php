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
        Schema::create('business_trip_expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_trip_settlement_id')->constrained('business_trip_settlements')->cascadeOnDelete();
            $table->string('category'); // tiket, boarding_pass, hotel, bbm, tol, parkir, makan, lainnya
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('expense_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_trip_expense_items');
    }
};
