<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_siswas', function (Blueprint $table): void {
            $table->string('Nilai_ID', 10)->charset('ascii')->primary();
            $table->string('Narasi_ID', 10)->charset('ascii');
            $table->string('Indikator_ID', 10)->charset('ascii');
            $table->enum('Nilai', ['MB', 'BSH', 'BSB']);
            $table->boolean('Dipilih_Untuk_Narasi')->default(false);
            $table->text('Deskripsi_Capaian')->nullable();
            $table->timestamps();

            $table->foreign('Narasi_ID')->references('Narasi_ID')->on('narasi')->cascadeOnDelete();
            $table->foreign('Indikator_ID')->references('Indikator_ID')->on('indikator_capaian')->restrictOnDelete();

            $table->unique(['Narasi_ID', 'Indikator_ID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_siswas');
    }
};
