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
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->string('type')->default('cash')->after('code'); // 'cash' or 'bank'
            $table->string('bank_name')->nullable()->after('type'); // e.g., 'Bank BCA', 'Bank Mandiri'
            $table->string('account_number')->nullable()->after('bank_name'); // e.g., '1234567890'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'bank_name', 'account_number']);
        });
    }
};
