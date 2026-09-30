<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->string('Role_ID', 10)->charset('ascii')->primary();
            $table->string('Nama_Role', 25);
            $table->string('Deskripsi', 100)->nullable();
            $table->timestamps();

            $table->unique('Nama_Role');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->string('User_ID', 10)->charset('ascii')->primary();
            $table->string('Role_ID', 10)->charset('ascii');
            $table->string('NIP', 20)->nullable();
            $table->string('Nama', 100);
            $table->string('Email', 100);
            $table->string('Password', 255);
            $table->string('No_Telepon', 15)->nullable();
            $table->boolean('Is_Active')->default(true);
            $table->timestamps();

            $table->foreign('Role_ID')->references('Role_ID')->on('roles')->restrictOnDelete();
            $table->unique('Email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('Email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
