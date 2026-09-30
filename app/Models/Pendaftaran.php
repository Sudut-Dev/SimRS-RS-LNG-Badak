<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pendaftaran extends Model
{
    use HasFactory;

    protected $table = 'pendaftaran';

    protected $fillable = [
        'no_antrian',
        'pasien_id',
        'jadwal_dokter_id',
        'user_id',
        'tanggal_periksa',
        'no_urut',
        'keluhan',
        'status',
        'waktu_dipanggil',
        'waktu_selesai',
    ];

    protected $casts = [
        'tanggal_periksa' => 'date',
        'waktu_dipanggil' => 'datetime',
        'waktu_selesai'   => 'datetime',
        'no_urut'         => 'integer',
    ];

    /**
     * Generate nomor antrian otomatis.
     * Format: {KODE_POLI}-YYYYMMDD-XXX
     */
    public static function generateNoAntrian(string $kodePoli, string $tanggal = null): string
    {
        $tanggal = $tanggal ?? today()->toDateString();
        $tanggalStr = str_replace('-', '', $tanggal);

        $count = self::whereHas('jadwalDokter.poli', function ($q) use ($kodePoli) {
            $q->where('kode_poli', $kodePoli);
        })->whereDate('tanggal_periksa', $tanggal)->count();

        return strtoupper($kodePoli) . '-' . $tanggalStr . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate nomor urut antrian per poli per hari.
     */
    public static function generateNoUrut(int $jadwalId, string $tanggal = null): int
    {
        $tanggal = $tanggal ?? today()->toDateString();
        $lastUrut = self::where('jadwal_dokter_id', $jadwalId)
            ->whereDate('tanggal_periksa', $tanggal)
            ->whereIn('status', ['menunggu', 'dipanggil', 'selesai'])
            ->max('no_urut');
        return ($lastUrut ?? 0) + 1;
    }

    public function getLabelStatusAttribute(): string
    {
        return match($this->status) {
            'menunggu'  => 'Menunggu',
            'dipanggil' => 'Dipanggil',
            'selesai'   => 'Selesai',
            'batal'     => 'Dibatalkan',
            default     => $this->status,
        };
    }

    // Relations
    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }

    public function jadwalDokter()
    {
        return $this->belongsTo(JadwalDokter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pemeriksaan()
    {
        return $this->hasOne(Pemeriksaan::class);
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class);
    }
}
