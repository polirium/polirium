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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('branch_id')->comment('Chi nhánh tạo phiếu');
            $table->date('balance_date')->nullable()->comment('Ngày cân bằng');
            $table->integer('amount')->default(0)->comment('Tổng số lượng');
            $table->integer('increase_deviation')->default(0)->comment('Tăng độ lệch');
            $table->integer('decrease_deviation')->default(0)->comment('Giảm độ lệch');
            $table->integer('deviation')->default(0)->comment('Độ lệch');
            $table->integer('value')->default(0)->comment('Tổng giá trị');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Người kiểm kho');
            $table->unsignedBigInteger('user_created_id')->nullable()->comment('Người tạo');
            $table->string('status')->default('draft')->comment('Trạng thái');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_stock_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('stock_id')->comment('product_stocks.id');
            $table->unsignedBigInteger('product_id');
            $table->integer('amount')->default(0)->comment('Tồn kho hệ thống');
            $table->integer('actual_stock')->default(1)->comment('Số lượng thực tế');
            $table->integer('quantity_difference')->default(0)->comment('Số lượng lệch (actual_stock - amount)');
            $table->integer('value')->default(0)->comment('Giá trị tồn kho');
            $table->integer('value_difference')->default(0)->comment('Giá trị lệch (quantity_difference * cost)');
            $table->string('note')->nullable()->comment('Ghi chú');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('product_stock_products');
    }
};
