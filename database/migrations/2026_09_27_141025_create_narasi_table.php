<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('narasi', function (Blueprint $table): void {
            $table->string('Narasi_ID', 10)->charset('ascii')->primary();
            $table->string('Rapor_ID', 10)->charset('ascii');
            $table->string('Mapel_ID', 10)->charset('ascii');
            $table->text('Catatan_Guru')->nullable();
            $table->text('Draft_Narasi')->nullable();
            $table->text('Narasi_Final')->nullable();
            $table->enum('Status_Narasi', ['draft', 'diedit', 'final'])->default('draft');
            $table->dateTime('Tanggal_Generate')->nullable();
            $table->dateTime('Tanggal_Edit')->nullable();
            $table->timestamps();

            $table->foreign('Rapor_ID')->references('Rapor_ID')->on('rapors')->cascadeOnDelete();
            $table->foreign('Mapel_ID')->references('Mapel_ID')->on('mata_pelajarans')->restrictOnDelete();

            $table->unique(['Rapor_ID', 'Mapel_ID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('narasi');
    }
};
