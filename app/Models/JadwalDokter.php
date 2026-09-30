<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JadwalDokter extends Model
{
    use HasFactory;

    protected $table = 'jadwal_dokter';

    protected $fillable = [
        'dokter_id',
        'poli_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'kuota',
        'status',
    ];

    protected $casts = [
        'kuota' => 'integer',
    ];

    /**
     * Hitung sisa kuota untuk tanggal tertentu.
     */
    public function sisaKuota(string $tanggal = null): int
    {
        $tanggal = $tanggal ?? today()->toDateString();
        $terisi = $this->pendaftaran()
            ->whereDate('tanggal_periksa', $tanggal)
            ->whereIn('status', ['menunggu', 'dipanggil', 'selesai'])
            ->count();
        return max(0, $this->kuota - $terisi);
    }

    // Relations
    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class);
    }

    public function pendaftaran()
    {
        return $this->hasMany(Pendaftaran::class);
    }
}
