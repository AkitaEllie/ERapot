<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelajarans', function (Blueprint $table): void {
            $table->string('Pembelajaran_ID', 10)->charset('ascii')->primary();
            $table->string('Kelas_ID', 10)->charset('ascii');
            $table->string('Mapel_ID', 10)->charset('ascii');
            $table->string('User_ID', 10)->charset('ascii')->nullable();
            $table->timestamps();

            $table->foreign('Kelas_ID')->references('Kelas_ID')->on('kelas')->cascadeOnDelete();
            $table->foreign('Mapel_ID')->references('Mapel_ID')->on('mata_pelajarans')->restrictOnDelete();
            $table->foreign('User_ID')->references('User_ID')->on('users')->restrictOnDelete();

            $table->unique(['Kelas_ID', 'Mapel_ID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelajarans');
    }
};
