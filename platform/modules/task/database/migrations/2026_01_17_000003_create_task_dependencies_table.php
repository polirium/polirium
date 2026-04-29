<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('predecessor_id')->comment('Task that must complete first');
            $table->unsignedBigInteger('successor_id')->comment('Task that depends on predecessor');
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])->default('finish_to_start');
            $table->integer('lag_days')->default(0)->comment('Delay between tasks');
            $table->timestamps();

            // Unique constraint to prevent duplicate dependencies
            $table->unique(['predecessor_id', 'successor_id'], 'unique_dependency');

            // Indexes
            $table->index('predecessor_id');
            $table->index('successor_id');

            // Foreign keys
            $table->foreign('predecessor_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('successor_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
