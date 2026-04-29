<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('vendor_purchase_refunds', function (Blueprint $table) {
            $table->integer('vendor_id')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_purchase_refunds', function (Blueprint $table) {
            $table->integer('vendor_id')->nullable(false)->change();
        });
    }
};
