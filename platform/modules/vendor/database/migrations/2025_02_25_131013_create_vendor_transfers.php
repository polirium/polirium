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
        Schema::create('vendor_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('code');
            $table->enum('status', ['temp', 'delivery', 'success', 'fail'])->default('temp');
            $table->integer('branch_id')->comment('Chi nhánh tạo phiếu');
            $table->integer('form_branch_id')->comment('Từ chi nhánh này');
            $table->integer('to_branch_id')->comment('Đến chi nhánh này');
            $table->integer('user_created_id');
            $table->date('date_send')->nullable();
            $table->date('date_take')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_transfer_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('vendor_transfer_id');
            $table->integer('product_id');
            $table->integer('amount')->default(0);
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
        Schema::dropIfExists('vendor_transfers');
        Schema::dropIfExists('vendor_transfer_products');
    }
};
