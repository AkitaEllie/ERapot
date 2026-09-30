<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapors', function (Blueprint $table): void {
            $table->string('Rapor_ID', 10)->charset('ascii')->primary();
            $table->string('Siswa_ID', 10)->charset('ascii');
            $table->string('Kelas_ID', 10)->charset('ascii');
            $table->string('Disetujui_Oleh', 10)->charset('ascii')->nullable();
            $table->enum('Semester', ['ganjil', 'genap']);
            $table->enum('Status', ['belum_diisi', 'draft', 'menunggu', 'perlu_revisi', 'disetujui'])->default('belum_diisi');
            $table->decimal('Tinggi_Badan', 5, 1)->nullable();
            $table->decimal('Berat_Badan', 5, 1)->nullable();
            $table->decimal('Lingkar_Kepala', 5, 1)->nullable();
            $table->string('Status_Pertumbuhan', 20)->nullable();
            $table->smallInteger('Sakit')->default(0);
            $table->smallInteger('Izin')->default(0);
            $table->smallInteger('Tanpa_Keterangan')->default(0);
            $table->text('Catatan_Revisi')->nullable();
            $table->dateTime('Tanggal_Persetujuan')->nullable();
            $table->dateTime('Tanggal_Cetak')->nullable();
            $table->string('File_PDF', 255)->nullable();
            $table->timestamps();

            $table->foreign('Siswa_ID')->references('Siswa_ID')->on('siswas')->cascadeOnDelete();
            $table->foreign('Kelas_ID')->references('Kelas_ID')->on('kelas')->restrictOnDelete();
            $table->foreign('Disetujui_Oleh')->references('User_ID')->on('users')->nullOnDelete();

            $table->unique(['Siswa_ID', 'Kelas_ID', 'Semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapors');
    }
};
