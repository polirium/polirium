<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task detail models extend BaseModel, which always assigns a uuid on create.
 * These tables were created without the column, so no dependency, comment,
 * attachment or time log could ever be saved.
 */
return new class () extends Migration {
    private array $tables = ['task_dependencies', 'task_comments', 'task_attachments', 'task_time_logs'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->uuid('uuid')->nullable()->unique()->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->dropUnique($tableName . '_uuid_unique');
                    $table->dropColumn('uuid');
                });
            }
        }
    }
};
