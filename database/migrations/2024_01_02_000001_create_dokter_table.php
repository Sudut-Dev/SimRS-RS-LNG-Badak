<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master dokter yang bertugas di rumah sakit.
     */
    public function up(): void
    {
        Schema::create('dokter', function (Blueprint $table) {
            $table->id();
            $table->string('kode_dokter', 10)->unique();
            $table->string('nama_dokter', 100);
            $table->string('spesialisasi', 100);
            $table->string('no_sip', 30)->unique()->comment('Nomor Surat Izin Praktik');
            $table->string('no_hp', 15)->nullable();
            $table->decimal('biaya_konsultasi', 12, 2)->default(0);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokter');
    }
};
