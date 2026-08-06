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
            if (Schema::hasColumn('financial_report_notes', 'period_month')) {
                $table->dropColumn('period_month');
            }
            if (Schema::hasColumn('financial_report_notes', 'period_year')) {
                $table->dropColumn('period_year');
            }
            if (!Schema::hasColumn('financial_report_notes', 'period_date')) {
                $table->date('period_date')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_report_notes', function (Blueprint $table) {
            if (Schema::hasColumn('financial_report_notes', 'period_date')) {
                $table->dropColumn('period_date');
            }
            if (!Schema::hasColumn('financial_report_notes', 'period_month')) {
                $table->integer('period_month')->nullable();
            }
            if (!Schema::hasColumn('financial_report_notes', 'period_year')) {
                $table->integer('period_year')->nullable();
            }
        });
    }
};
