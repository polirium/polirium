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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('sex')->default(0);
            $table->mediumText('address')->nullable();
            $table->integer('province_id')->nullable()->comment('Thành phố/Tỉnh');
            $table->integer('district_id')->nullable()->comment('Quận/Huyện');
            $table->integer('ward_id')->nullable()->comment("'Phường/Xã");
            $table->boolean('type')->default(0)->comment('Loại khách hàng');
            $table->string('company')->nullable()->comment('Công ty');
            $table->string('vat')->nullable()->comment('MST');
            $table->string('email')->nullable();
            $table->string('facebook')->nullable();
            $table->string('note')->nullable();
            $table->boolean('status')->default(1)->comment('Trạng thái');
            $table->integer('user_id');
            $table->integer('branch_id')->nullable();
            $table->timestamps();

            $table->unique('phone');
            $table->unique('email');
            $table->unique('code');
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->enum('type', [1, 2, 3])->default(1)->comment('nhóm kh thuộc loại điều kiện');
            $table->string('note')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('customers_pivot_groups', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('customer_id');
            $table->integer('customer_group_id');
            $table->timestamps();

            $table->unique(['customer_id', 'customer_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('customers_pivot_groups');
    }
};
