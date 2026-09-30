<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pemeriksaan extends Model
{
    use HasFactory;

    protected $table = 'pemeriksaan';

    protected $fillable = [
        'pendaftaran_id',
        'tekanan_darah',
        'berat_badan',
        'tinggi_badan',
        'suhu_tubuh',
        'nadi',
        'diagnosa',
        'kode_icd',
        'tindakan',
        'resep',
        'catatan_dokter',
    ];

    protected $casts = [
        'berat_badan' => 'decimal:2',
        'tinggi_badan' => 'decimal:2',
        'suhu_tubuh' => 'decimal:1',
        'nadi' => 'integer',
    ];

    /**
     * Hitung IMT (Indeks Massa Tubuh) pasien.
     */
    public function getImtAttribute(): ?float
    {
        if (!$this->berat_badan || !$this->tinggi_badan) return null;
        $tinggiMeter = $this->tinggi_badan / 100;
        return round($this->berat_badan / ($tinggiMeter * $tinggiMeter), 2);
    }

    /**
     * Kategorisasi IMT.
     */
    public function getKategoriImtAttribute(): string
    {
        $imt = $this->imt;
        if (!$imt) return '-';
        if ($imt < 18.5) return 'Kurus';
        if ($imt < 25.0) return 'Normal';
        if ($imt < 30.0) return 'Gemuk';
        return 'Obesitas';
    }

    // Relations
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
