<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_pengembangans', function (Blueprint $table): void {
            $table->string('Program_ID', 10)->charset('ascii')->primary();
            $table->string('Mapel_ID', 10)->charset('ascii');
            $table->string('Nama_Program', 100);
            $table->timestamps();

            $table->foreign('Mapel_ID')->references('Mapel_ID')->on('mata_pelajarans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_pengembangans');
    }
};
