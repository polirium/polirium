<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('print_forms') && ! Schema::hasColumn('print_forms', 'paper_size')) {
            Schema::table('print_forms', function (Blueprint $table) {
                $table->string('paper_size', 20)->default('a4')->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('print_forms') && Schema::hasColumn('print_forms', 'paper_size')) {
            Schema::table('print_forms', function (Blueprint $table) {
                $table->dropColumn('paper_size');
            });
        }
    }
};
