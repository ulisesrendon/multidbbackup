<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('backup_schedule_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->string('local_path')->nullable();
            $table->string('s3_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
