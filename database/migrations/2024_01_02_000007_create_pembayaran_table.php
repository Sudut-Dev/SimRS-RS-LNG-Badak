<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel tagihan/pembayaran per pendaftaran.
     * Validasi: jumlah_bayar tidak boleh kurang dari total_tagihan.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->string('no_kwitansi', 20)->unique()->comment('Format: KWT-YYYYMMDD-XXX');
            $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftaran')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')
                ->comment('Kasir yang memproses pembayaran');
            $table->decimal('biaya_konsultasi', 12, 2)->default(0);
            $table->decimal('biaya_tindakan', 12, 2)->default(0);
            $table->decimal('biaya_obat', 12, 2)->default(0);
            $table->decimal('biaya_admin', 12, 2)->default(0);
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('total_tagihan', 12, 2)->storedAs(
                'biaya_konsultasi + biaya_tindakan + biaya_obat + biaya_admin - diskon'
            )->comment('Dikalkulasi otomatis oleh database');
            $table->decimal('jumlah_bayar', 12, 2)->comment('Wajib >= total_tagihan');
            $table->decimal('kembalian', 12, 2)->storedAs(
                'jumlah_bayar - (biaya_konsultasi + biaya_tindakan + biaya_obat + biaya_admin - diskon)'
            );
            $table->enum('metode_bayar', ['tunai', 'transfer', 'bpjs', 'asuransi'])->default('tunai');
            $table->enum('status', ['lunas', 'belum_lunas'])->default('lunas');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
