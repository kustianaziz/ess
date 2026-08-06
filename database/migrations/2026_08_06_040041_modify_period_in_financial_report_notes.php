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
        Schema::table('financial_report_notes', function (Blueprint $table) {
            $table->dropColumn(['period_month', 'period_year']);
            $table->date('period_date')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_report_notes', function (Blueprint $table) {
            $table->dropColumn('period_date');
            $table->integer('period_month')->nullable();
            $table->integer('period_year')->nullable();
        });
    }
};
