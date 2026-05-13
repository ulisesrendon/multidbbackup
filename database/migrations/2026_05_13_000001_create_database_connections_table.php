<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_connections', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->text('host_encrypted');
            $table->text('port_encrypted');
            $table->text('database_name_encrypted');
            $table->text('username_encrypted');
            $table->text('password_encrypted');
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};
