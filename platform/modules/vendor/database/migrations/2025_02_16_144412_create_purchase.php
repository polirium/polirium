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
        Schema::create('vendor_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
            $table->integer('branch_id');
            $table->integer('vendor_id');
            $table->bigInteger("discount_value")->default(0)->comment("Giá trị giảm giá");
            $table->enum("discount_type", ["percent", "number"])->default("number")->comment("Loại giảm giá (trừ số hoặc %)");
            $table->integer('user_created_id');
            $table->enum('status', ['temp', 'success', 'refund', 'cancel'])->default('temp');
            $table->bigInteger('total')->default(0)->comment('Tống tiền hàng');
            $table->bigInteger('need_pay')->default(0)->comment('Tống tiền cần trả');
            $table->bigInteger('value')->default(0)->comment('Tiền trả NCC');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_purchase_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('branch_id');
            $table->integer('product_id');
            $table->integer('vendor_purchase_id');
            $table->integer('amount')->default(0);
            $table->bigInteger('price');
            $table->bigInteger("discount_value")->default(0)->comment("Giá trị giảm giá");
            $table->enum("discount_type", ["percent", "number"])->default("number")->comment("Loại giảm giá (trừ số hoặc %)");
            $table->bigInteger('value');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_purchases');
        Schema::dropIfExists('vendor_purchase_products');
    }
};
