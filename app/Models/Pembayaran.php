<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'no_kwitansi',
        'pendaftaran_id',
        'user_id',
        'biaya_konsultasi',
        'biaya_tindakan',
        'biaya_obat',
        'biaya_admin',
        'diskon',
        'jumlah_bayar',
        'metode_bayar',
        'status',
        'catatan',
    ];

    protected $casts = [
        'biaya_konsultasi' => 'decimal:2',
        'biaya_tindakan'   => 'decimal:2',
        'biaya_obat'       => 'decimal:2',
        'biaya_admin'      => 'decimal:2',
        'diskon'           => 'decimal:2',
        'total_tagihan'    => 'decimal:2',
        'jumlah_bayar'     => 'decimal:2',
        'kembalian'        => 'decimal:2',
    ];

    /**
     * Generate nomor kwitansi otomatis.
     * Format: KWT-YYYYMMDD-XXX
     */
    public static function generateNoKwitansi(): string
    {
        $tanggal = now()->format('Ymd');
        $countToday = self::whereDate('created_at', today())->count();
        return 'KWT-' . $tanggal . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
    }

    // Relations
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
