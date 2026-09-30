<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajarans', function (Blueprint $table): void {
            $table->string('TahunAjaran_ID', 10)->charset('ascii')->primary();
            $table->string('Tahun_Ajaran', 9);
            $table->enum('Semester_Aktif', ['ganjil', 'genap']);
            $table->date('Tanggal_Mulai');
            $table->date('Tanggal_Selesai');
            $table->boolean('Is_Active')->default(true);
            $table->timestamps();

            $table->unique(['Tahun_Ajaran', 'Semester_Aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajarans');
    }
};
