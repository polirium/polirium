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
        Schema::create('product_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('product_payment_id');
            $table->string('code');
            $table->bigInteger('discount_value')->default(0)->comment('Giá trị giảm giá');
            $table->enum('discount_type', ['percent', 'number'])->default('number')->comment('Giá trị giảm giá');
            $table->bigInteger('refund_fee')->default(0)->comment('Phí trả hàng');
            $table->enum('refund_fee_type', ['percent', 'number'])->default('number')->comment('Phí trả hàng %/vnđ');
            $table->integer('amount')->default(0)->comment('Số lượng hàng trả');
            $table->bigInteger('original_total_amount')->default(0)->comment('Tổng tiền gốc hàng mua');
            $table->bigInteger('total_payable_amount')->default(0)->comment('Tổng tiền phải trả');
            $table->bigInteger('value')->default(0)->comment('Tổng tiền trả');
            $table->integer('user_id');
            $table->integer('user_created_id');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('product_refund_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('product_payment_id');
            $table->integer('product_refund_id');
            $table->integer('product_id');
            $table->integer('amount')->comment('Số lượng sản phẩm refund');
            $table->bigInteger('price')->default(0)->comment('Đơn giá');
            $table->bigInteger('discount_value')->default(0)->comment('Giá trị giảm giá');
            $table->enum('discount_type', ['percent', 'number'])->default('number')->comment('Giá trị giảm giá');
            $table->bigInteger('value')->default(0)->comment('Thành tiền');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_refund_products');
        Schema::dropIfExists('product_refunds');
    }
};
