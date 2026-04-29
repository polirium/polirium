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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->comment('Nhóm hàng');
            $table->integer('parent_id')->nullable();
            $table->integer('user_id');
            $table->timestamps();

            $table->unique(['name']);
        });

        Schema::create('trademarks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->comment('Thương hiệu');
            $table->integer('user_id');
            $table->timestamps();

            $table->unique(['name']);
        });

        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->comment('Vị trí');
            $table->integer('user_id');
            $table->timestamps();

            $table->unique(['name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('code');
            $table->integer('category_id')->nullable()->comment('Nhóm hàng');
            $table->integer('trademark_id')->nullable()->comment('Thương hiệu');
            $table->integer('shelve_id')->nullable()->comment('Vị trí');
            $table->bigInteger('cost')->default(0)->comment('Giá vốn');
            $table->bigInteger('price')->default(0)->comment('Giá bán');
            $table->integer('qty')->nullable()->comment('Số lượng');
            $table->integer('weight')->nullable()->comment('Trọng lượng');
            $table->enum('weight_type', ['gram', 'kg'])->default('gram');
            $table->boolean('allows_sale')->default(1)->comment('Bán trực tiếp');
            $table->string('unit')->nullable()->comment('Đơn vị cơ bản');
            $table->bigInteger('min_quantity')->default(0)->comment('Tồn ít nhất');
            $table->bigInteger('max_quantity')->default(999999999)->comment('Tồn nhiều nhất');
            $table->enum('type', ['product', 'service', 'combo'])->default('product')->comment('Loại hàng hoá, dịch vụ, combo');
            $table->longText('description')->nullable();
            $table->string('note')->nullable();
            $table->integer('user_id');
            $table->timestamps();

            $table->index(['name', 'code']);
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('product_id')->comment('products');
            $table->string('name');
            $table->string('code');
            $table->integer('conversion_value')->comment('Giá trị quy đổi');
            $table->bigInteger('price')->comment('Giá bán');
            $table->boolean('allows_sale')->default(1)->comment('Bán trực tiếp');
            $table->timestamps();

            $table->index(['product_id', 'name', 'code']);
            $table->unique(['product_id', 'name', 'code']);
        });

        Schema::create('product_elements', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('product_id')->comment('Hàng hoá');
            $table->integer('element_id')->comment('Hàng hoá con');
            $table->integer('qty')->default(1)->comment('Số lượng');
            $table->bigInteger('price')->nullable()->comment('Giá vốn hiện tại');
            $table->timestamps();

            $table->unique(['product_id', 'element_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('trademarks');
        Schema::dropIfExists('shelves');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('product_elements');
    }
};
