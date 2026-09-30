<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master pasien.
     * NIK wajib diisi dan terdiri dari 16 digit (validasi di controller & model).
     */
    public function up(): void
    {
        Schema::create('pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 20)->unique()->comment('Nomor Rekam Medis');
            $table->string('nik', 16)->unique()->comment('Nomor Induk Kependudukan - 16 digit');
            $table->string('nama_pasien', 100);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('no_hp', 15)->nullable();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', '-'])->default('-');
            $table->enum('jenis_pembayaran', ['umum', 'bpjs', 'asuransi'])->default('umum');
            $table->string('no_bpjs', 13)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien');
    }
};
