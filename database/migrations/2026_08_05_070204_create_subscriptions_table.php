<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('marketing_id')->nullable();
            $table->string('client_name');
            $table->unsignedTinyInteger('category_id')->nullable();
            $table->unsignedTinyInteger('type_id')->nullable();
            $table->unsignedTinyInteger('province_id')->nullable();
            $table->unsignedSmallInteger('city_id')->nullable();
            $table->unsignedSmallInteger('district_id')->nullable();
            $table->text('address')->nullable();
            $table->unsignedTinyInteger('source_id')->nullable();
            $table->string('website_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('project_id')->nullable();
            $table->string('product_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->enum('billing_cycle', ['Monthly', 'Yearly'])->default('Yearly');
            $table->string('contract_no')->nullable();
            $table->string('contract_file')->nullable();
            $table->enum('status', ['Active', 'Non Aktif'])->default('Active');
            $table->text('notes')->nullable();
            $table->string('pic_teknis_name')->nullable();
            $table->string('pic_teknis_phone')->nullable();
            $table->string('pic_finance_name')->nullable();
            $table->string('pic_finance_phone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
