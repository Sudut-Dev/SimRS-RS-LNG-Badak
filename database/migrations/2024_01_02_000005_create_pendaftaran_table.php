<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pendaftaran rawat jalan (antrian harian).
     * Ini adalah transaksi utama SIMRS.
     */
    public function up(): void
    {
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->string('no_antrian', 20)->unique()->comment('Format: POL-YYYYMMDD-XXX');
            $table->foreignId('pasien_id')->constrained('pasien')->onDelete('restrict');
            $table->foreignId('jadwal_dokter_id')->constrained('jadwal_dokter')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')
                ->comment('Petugas yang mendaftarkan');
            $table->date('tanggal_periksa');
            $table->integer('no_urut')->comment('Nomor urut antrian hari ini untuk poli ini');
            $table->text('keluhan')->nullable()->comment('Keluhan utama pasien');
            $table->enum('status', ['menunggu', 'dipanggil', 'selesai', 'batal'])
                ->default('menunggu');
            $table->timestamp('waktu_dipanggil')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran');
    }
};
