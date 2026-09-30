<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table): void {
            $table->string('Kelas_ID', 10)->charset('ascii')->primary();
            $table->string('TahunAjaran_ID', 10)->charset('ascii');
            $table->string('User_ID', 10)->charset('ascii')->nullable();
            $table->string('Nama_Kelas', 30);
            $table->enum('Jenjang', ['KB-A', 'KB-B', 'TK-A', 'TK-B']);
            $table->string('Fase', 20);
            $table->timestamps();

            $table->foreign('TahunAjaran_ID')->references('TahunAjaran_ID')->on('tahun_ajarans')->restrictOnDelete();
            $table->foreign('User_ID')->references('User_ID')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
