<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narasi', function (Blueprint $table): void {
            $table->string('Generate_Token', 36)->charset('ascii')->nullable()->after('Tanggal_Edit');
            $table->dateTime('Generate_Mulai')->nullable()->after('Generate_Token');
            $table->string('Generate_Galat')->nullable()->after('Generate_Mulai');
        });
    }

    public function down(): void
    {
        Schema::table('narasi', function (Blueprint $table): void {
            $table->dropColumn(['Generate_Token', 'Generate_Mulai', 'Generate_Galat']);
        });
    }
};
