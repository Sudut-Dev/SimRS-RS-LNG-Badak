<?php

namespace App\Http\Controllers;

use App\Models\Pemeriksaan;
use App\Models\Pendaftaran;
use App\Http\Requests\PemeriksaanRequest;
use Illuminate\Http\Request;

class PemeriksaanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pemeriksaan::with(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter']);

        if ($request->filled('tanggal')) {
            $query->whereHas('pendaftaran', fn($q) => $q->whereDate('tanggal_periksa', $request->tanggal));
        }

        $pemeriksaan = $query->latest()->paginate(15)->withQueryString();

        return response()->json($pemeriksaan);
    }

    public function store(PemeriksaanRequest $request)
    {
        $data = $request->validated();

        // Cek apakah pendaftaran sudah memiliki pemeriksaan
        $existing = Pemeriksaan::where('pendaftaran_id', $data['pendaftaran_id'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'Pendaftaran ini sudah memiliki data pemeriksaan.',
            ], 422);
        }

        // Validasi: status pendaftaran harus 'dipanggil' atau 'selesai'
        $pendaftaran = Pendaftaran::findOrFail($data['pendaftaran_id']);
        if (!in_array($pendaftaran->status, ['dipanggil', 'selesai'])) {
            return response()->json([
                'message' => 'Pemeriksaan hanya dapat diisi untuk pasien yang sudah dipanggil.',
            ], 422);
        }

        $pemeriksaan = Pemeriksaan::create($data);

        // Otomatis update status pendaftaran menjadi selesai
        $pendaftaran->update([
            'status'       => 'selesai',
            'waktu_selesai' => now(),
        ]);

        $pemeriksaan->load(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter']);

        return response()->json([
            'message' => 'Data pemeriksaan berhasil disimpan.',
            'data'    => $pemeriksaan,
        ], 201);
    }

    public function show(Pemeriksaan $pemeriksaan)
    {
        return response()->json(
            $pemeriksaan->load(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter', 'pendaftaran.jadwalDokter.poli'])
                ->append(['imt', 'kategori_imt'])
        );
    }

    public function update(PemeriksaanRequest $request, Pemeriksaan $pemeriksaan)
    {
        $pemeriksaan->update($request->validated());

        return response()->json([
            'message' => 'Data pemeriksaan berhasil diperbarui.',
            'data'    => $pemeriksaan->fresh()->append(['imt', 'kategori_imt']),
        ]);
    }
}
