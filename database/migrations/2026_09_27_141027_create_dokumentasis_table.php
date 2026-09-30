<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasis', function (Blueprint $table): void {
            $table->string('Dokumentasi_ID', 10)->charset('ascii')->primary();
            $table->string('Narasi_ID', 10)->charset('ascii');
            $table->string('Foto', 255);
            $table->string('Keterangan', 150)->nullable();
            $table->timestamps();

            $table->foreign('Narasi_ID')->references('Narasi_ID')->on('narasi')->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasis');
    }
};
