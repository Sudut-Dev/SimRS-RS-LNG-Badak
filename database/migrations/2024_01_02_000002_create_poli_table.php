<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master poli (poliklinik) di rumah sakit.
     */
    public function up(): void
    {
        Schema::create('poli', function (Blueprint $table) {
            $table->id();
            $table->string('kode_poli', 10)->unique();
            $table->string('nama_poli', 100);
            $table->string('lokasi', 100)->nullable()->comment('Gedung/lantai poli berada');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poli');
    }
};
