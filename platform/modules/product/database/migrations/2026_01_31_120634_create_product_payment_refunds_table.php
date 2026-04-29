<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('branch_id')->unsigned()->index();
            $table->integer('customer_id')->unsigned()->index();
            $table->integer('product_payment_id')->unsigned()->index()->nullable();
            $table->string('code')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->string('discount_type')->default('percent');
            $table->decimal('value', 15, 2)->default(0); // Amount to refund
            $table->string('note')->nullable();
            $table->string('status')->default('temp');
            $table->integer('user_created_id')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_payment_refund_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('product_payment_refund_id')->unsigned()->index();
            $table->integer('product_id')->unsigned()->index();
            $table->integer('amount')->default(1);
            $table->decimal('price', 15, 2)->default(0); // Refund price per unit
            $table->decimal('value', 15, 2)->default(0); // Total refund value for line item
            $table->string('note')->nullable();
            $table->integer('product_payment_id')->unsigned()->index()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_payment_refund_products');
        Schema::dropIfExists('product_payment_refunds');
    }
};
