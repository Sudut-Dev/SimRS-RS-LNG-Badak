<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dokter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dokter';

    protected $fillable = [
        'kode_dokter',
        'nama_dokter',
        'spesialisasi',
        'no_sip',
        'no_hp',
        'biaya_konsultasi',
        'status',
    ];

    protected $casts = [
        'biaya_konsultasi' => 'decimal:2',
    ];

    /**
     * Generate kode dokter otomatis.
     */
    public static function generateKode(): string
    {
        $last = self::withTrashed()->latest('id')->first();
        $nextNum = $last ? ($last->id + 1) : 1;
        return 'DKT' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    // Relations
    public function jadwalDokter()
    {
        return $this->hasMany(JadwalDokter::class);
    }

    public function poli()
    {
        return $this->belongsToMany(Poli::class, 'jadwal_dokter');
    }
}
