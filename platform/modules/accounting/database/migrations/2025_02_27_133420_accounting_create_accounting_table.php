<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('accounting_types', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('type')->default('receipt');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('branch_id');
            $table->string('code');
            $table->date('date')->nullable();
            $table->integer('type_id')->nullable()->comment('accounting_types');
            $table->bigInteger('value')->nullable();
            $table->integer('user_id');
            $table->integer('user_created_id');
            $table->string('finance_type')->nullable();
            $table->integer('finance_id')->nullable();
            $table->boolean('business_result')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('accouting_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('branch_id');
            $table->string('code');
            $table->date('date')->nullable();
            $table->integer('type_id')->nullable()->comment('accounting_types');
            $table->bigInteger('value')->nullable();
            $table->integer('user_id');
            $table->integer('user_created_id');
            $table->string('finance_type')->nullable();
            $table->integer('finance_id')->nullable();
            $table->boolean('business_result')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_pay_persons', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->integer('province_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('ward_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_types');
        Schema::dropIfExists('accounting_receipts');
        Schema::dropIfExists('accouting_payments');
        Schema::dropIfExists('accounting_pay_persons');
    }
};
