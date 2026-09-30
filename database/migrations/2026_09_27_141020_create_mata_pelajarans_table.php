<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajarans', function (Blueprint $table): void {
            $table->string('Mapel_ID', 10)->charset('ascii')->primary();
            $table->string('Nama_Mapel', 80);
            $table->enum('Jenis_Mapel', ['intrakurikuler', 'kokurikuler', 'muatan_lokal']);
            $table->boolean('Tampilkan_Indikator')->default(true);
            $table->timestamps();

            $table->unique('Nama_Mapel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};
