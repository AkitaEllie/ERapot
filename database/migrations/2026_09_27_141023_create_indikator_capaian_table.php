<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_capaian', function (Blueprint $table): void {
            $table->string('Indikator_ID', 10)->charset('ascii')->primary();
            $table->string('Program_ID', 10)->charset('ascii');
            $table->string('TahunAjaran_ID', 10)->charset('ascii');
            $table->enum('Jenjang', ['KB-A', 'KB-B', 'TK-A', 'TK-B']);
            $table->enum('Semester', ['ganjil', 'genap']);
            $table->enum('Tipe', ['capaian', 'perilaku', 'dimensi']);
            $table->string('Kode_KD', 20);
            $table->text('Deskripsi');
            $table->timestamps();

            $table->foreign('Program_ID')->references('Program_ID')->on('program_pengembangans')->cascadeOnDelete();
            $table->foreign('TahunAjaran_ID')->references('TahunAjaran_ID')->on('tahun_ajarans')->cascadeOnDelete();

            $table->unique(['Program_ID', 'Kode_KD']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_capaian');
    }
};
