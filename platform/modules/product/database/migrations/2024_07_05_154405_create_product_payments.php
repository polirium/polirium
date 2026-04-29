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
        Schema::create('product_payment_sale_channels', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->enum('type', ['person', 'company'])->default('person');
            $table->string('name');
            $table->string('code');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer('province_id')->nullable()->comment('Thành phố/Tỉnh');
            $table->integer('district_id')->nullable()->comment('Quận/Huyện');
            $table->integer('ward_id')->nullable()->comment('Phường/Xã');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('province_id');
            $table->index('district_id');
            $table->index('ward_id');
        });

        Schema::create('product_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('branch_id');
            $table->integer('customer_id')->nullable();
            $table->string('code');
            $table->integer('amount_products')->default(0)->comment('Số lượng sản phẩm đơn hàng');
            $table->bigInteger('total_cost')->default(0)->comment('Tổng tiền hàng');
            $table->bigInteger('discount_value')->default(0)->comment('Giá trị giảm giá');
            $table->enum('discount_type', ['percent', 'number'])->default('number')->comment('Giá trị giảm giá');
            $table->bigInteger('value')->default(0)->comment('Số tiền khách cần trả');
            $table->bigInteger('value_payment')->default(0)->comment('Số tiền khách thanh toán');
            $table->json('type_payment')->nullable()->comment('Hình thức thanh toán (tm, thẻ, ngân hàng)');
            $table->boolean('is_delivery')->default(0)->comment('Bán trực tiếp hay giao hàng');
            $table->integer('sale_channel_id')->nullable()->comment('Kênh bán');
            $table->boolean('is_cod')->default(0)->comment('Giao hàng COD');
            $table->integer('user_id')->comment('Người bán');
            $table->integer('author_id')->comment('Người tạo đơn');
            $table->string('status')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('user_id');
            $table->index('author_id');
            $table->index('customer_id');
        });

        Schema::create('product_payment_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('product_payment_id');
            $table->string('code');
            $table->integer('partner_delivery_id')->nullable()->comment('Đối tác giao hàng');
            $table->enum('type', ['normal', 'fast', 'day'])->nullable()->comment('Loại dịch vụ giao hàng');
            $table->bigInteger('value')->nullable()->comment('Phí giao hàng');
            $table->dateTime('date')->nullable()->comment('Thời gian giao hàng');
            $table->enum('status', ['wait', 'delivery', 'success', 'cancel'])->default('wait');
            $table->timestamps();

            $table->index('product_payment_id');
            $table->index('partner_delivery_id');
        });

        Schema::create('product_payment_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('product_payment_id');
            $table->integer('product_id');
            $table->integer('amount')->default(0);
            $table->bigInteger('value')->default(0)->comment('Đơn giá');
            $table->bigInteger('discount_value')->default(0)->comment('Giá trị giảm giá');
            $table->enum('discount_type', ['percent', 'number'])->default('number')->comment('Giá trị giảm giá');
            $table->bigInteger('total')->default(0)->comment('Tổng tiền thanh toán');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('product_payment_id');
            $table->index('product_id');
        });

        Schema::create('product_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('product_id');
            $table->morphs('productable');
            $table->integer('amount')->default(0)->comment('Số lượng giao dịch');
            $table->bigInteger('value_before')->default(0)->comment('Đơn giá sản phẩm tại thời điểm');
            $table->bigInteger('value_after')->default(0)->comment('Đơn giá sản phẩm thanh toán');
            $table->integer('amount_before')->default(0)->comment('Số lượng trước giao dịch');
            $table->integer('amount_after')->default(0)->comment('Số lượng sau giao dịch');
            $table->timestamps();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_payment_sale_channels');
        Schema::dropIfExists('product_payment_partner_deliveries');
        Schema::dropIfExists('product_payments');
        Schema::dropIfExists('product_payment_deliveries');
        Schema::dropIfExists('product_payment_products');
        Schema::dropIfExists('product_logs');
    }
};
