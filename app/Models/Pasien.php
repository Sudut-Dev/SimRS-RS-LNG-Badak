<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pasien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pasien';

    protected $fillable = [
        'no_rm',
        'nik',
        'nama_pasien',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'golongan_darah',
        'jenis_pembayaran',
        'no_bpjs',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    /**
     * Generate nomor rekam medis otomatis.
     * Format: RM-YYYYMMDD-XXXX
     */
    public static function generateNoRM(): string
    {
        $tanggal = now()->format('Ymd');
        $lastToday = self::withTrashed()
            ->whereDate('created_at', today())
            ->count();
        $nextNum = $lastToday + 1;
        return 'RM-' . $tanggal . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung usia pasien berdasarkan tanggal lahir.
     */
    public function getUmurAttribute(): string
    {
        if (!$this->tanggal_lahir) return '-';
        $diff = now()->diff($this->tanggal_lahir);
        if ($diff->y > 0) return $diff->y . ' tahun';
        if ($diff->m > 0) return $diff->m . ' bulan';
        return $diff->d . ' hari';
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    // Relations
    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function riwayatPemeriksaan()
    {
        return $this->hasManyThrough(Pemeriksaan::class, Pendaftaran::class);
    }
}
