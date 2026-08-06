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
        Schema::table('services', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('description');
            $table->string('signature_image')->nullable()->after('logo');
            $table->string('signature_name')->nullable()->after('signature_image');
            $table->text('bank_credentials')->nullable()->after('signature_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['logo', 'signature_image', 'signature_name', 'bank_credentials']);
        });
    }
};
