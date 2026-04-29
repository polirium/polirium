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
        Schema::create('vendor_purchase_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('branch_id');
            $table->integer('vendor_id');
            $table->integer('purchase_id')->comment('vendor_purchases');
            $table->string('code');
            $table->bigInteger('total')->default(0)->comment('Tống tiền hàng');
            $table->bigInteger("discount_value")->default(0)->comment("Giá trị giảm giá");
            $table->enum("discount_type", ["percent", "number"])->default("number")->comment("Loại giảm giá (trừ số hoặc %)");
            $table->bigInteger('value')->default(0);
            $table->string('note')->nullable();
            $table->enum('status', ['temp', 'success', 'cancel'])->default('temp');
            $table->integer('user_created_id');
            $table->timestamps();
        });

        Schema::create('vendor_purchase_refund_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('vendor_purchase_refund_id');
            $table->integer('product_id');
            $table->integer('amount')->default(1);
            $table->bigInteger('price')->default(0);
            $table->bigInteger('value')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_refunds');
        Schema::dropIfExists('vendor_purchase_refund_products');
    }
};
