<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table): void {
            $table->string('Siswa_ID', 10)->charset('ascii')->primary();
            $table->char('NISN', 10);
            $table->string('NIS', 20);
            $table->string('Nama', 100);
            $table->enum('Jenis_Kelamin', ['L', 'P']);
            $table->string('Agama', 20);
            $table->timestamps();

            $table->unique('NISN');
            $table->unique('NIS');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
