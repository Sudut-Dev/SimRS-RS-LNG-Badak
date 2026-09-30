<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pemeriksaan dokter (hasil konsultasi/diagnosa).
     * Dibuat setelah dokter memeriksa pasien.
     */
    public function up(): void
    {
        Schema::create('pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftaran')->onDelete('restrict');
            $table->string('tekanan_darah', 20)->nullable()->comment('Contoh: 120/80');
            $table->decimal('berat_badan', 5, 2)->nullable();
            $table->decimal('tinggi_badan', 5, 2)->nullable();
            $table->decimal('suhu_tubuh', 4, 1)->nullable();
            $table->integer('nadi')->nullable()->comment('Per menit');
            $table->text('diagnosa')->comment('Diagnosa dokter');
            $table->string('kode_icd', 10)->nullable()->comment('Kode ICD-10');
            $table->text('tindakan')->nullable()->comment('Tindakan medis yang dilakukan');
            $table->text('resep')->nullable()->comment('Daftar obat yang diresepkan');
            $table->text('catatan_dokter')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemeriksaan');
    }
};
