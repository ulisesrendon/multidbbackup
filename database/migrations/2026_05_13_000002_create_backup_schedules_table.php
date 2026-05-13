<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_connection_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('frequency_hours'); // 4, 12, or 24
            $table->unsignedSmallInteger('retention_amount');
            $table->enum('retention_unit', ['hours', 'days', 'weeks', 'months', 'years']);
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
