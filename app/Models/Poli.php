<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Poli extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'poli';

    protected $fillable = [
        'kode_poli',
        'nama_poli',
        'lokasi',
        'status',
    ];

    /**
     * Generate kode poli otomatis.
     */
    public static function generateKode(): string
    {
        $last = self::withTrashed()->latest('id')->first();
        $nextNum = $last ? ($last->id + 1) : 1;
        return 'PLI' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    // Relations
    public function jadwalDokter()
    {
        return $this->hasMany(JadwalDokter::class);
    }

    public function dokter()
    {
        return $this->belongsToMany(Dokter::class, 'jadwal_dokter');
    }

    /**
     * Hitung total pendaftaran hari ini untuk poli ini.
     */
    public function getTotalPendaftaranHariIniAttribute(): int
    {
        return Pendaftaran::whereHas('jadwalDokter', fn($q) => $q->where('poli_id', $this->id))
            ->whereDate('tanggal_periksa', today())
            ->count();
    }
}
