<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number');
            $table->string('bank_code');
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('store_name')->nullable();
            $table->string('template')->default('compact');
            $table->boolean('show_info')->default(true);
            $table->boolean('full_account')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('target_payment_status')
                ->constrained('bank_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
        });

        Schema::dropIfExists('bank_accounts');
    }
};
