<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string("uuid");
            $table->integer('branch_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('vat');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer("province_id")->nullable()->comment("Thành phố/Tỉnh");
            $table->integer("district_id")->nullable()->comment("Quận/Huyện");
            $table->integer("ward_id")->nullable()->comment("Phường/Xã");
            $table->integer("user_created_id")->nullable();
            $table->string('company')->nullable();
            $table->string('status')->default('active');
            $table->decimal('total', 15, 2)->default(0)->comment('Tổng mua');
            $table->decimal('debt', 15, 2)->default(0)->comment('Công nợ');
            $table->decimal('total_purchase', 15, 2)->default(0)->comment('Tổng mua không tính trả hàng');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('note')->nullable();
            $table->integer("user_created_id")->nullable();
            $table->timestamps();
        });

        Schema::create('vendors_groups_pivot', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_id');
            $table->integer('vendor_group_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_groups');
        Schema::dropIfExists('vendors_groups_pivot');
    }
};
