<?php

namespace App\Http\Controllers;

use App\Models\JadwalDokter;
use App\Http\Requests\JadwalDokterRequest;
use Illuminate\Http\Request;

class JadwalDokterController extends Controller
{
    public function index(Request $request)
    {
        $query = JadwalDokter::with(['dokter', 'poli']);

        if ($request->filled('poli_id')) {
            $query->where('poli_id', $request->poli_id);
        }

        if ($request->filled('dokter_id')) {
            $query->where('dokter_id', $request->dokter_id);
        }

        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }

        $jadwal = $query->orderByRaw("FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')")
            ->paginate(15)
            ->withQueryString();

        return response()->json($jadwal);
    }

    public function store(JadwalDokterRequest $request)
    {
        // Validasi logis: tidak boleh ada jadwal yang sama (dokter + poli + hari)
        $exists = JadwalDokter::where('dokter_id', $request->dokter_id)
            ->where('hari', $request->hari)
            ->where('status', 'aktif')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Dokter ini sudah memiliki jadwal pada hari yang sama.',
            ], 422);
        }

        $jadwal = JadwalDokter::create($request->validated());
        $jadwal->load(['dokter', 'poli']);

        return response()->json([
            'message' => 'Jadwal dokter berhasil ditambahkan.',
            'data'    => $jadwal,
        ], 201);
    }

    public function show(JadwalDokter $jadwalDokter)
    {
        return response()->json($jadwalDokter->load(['dokter', 'poli']));
    }

    public function update(JadwalDokterRequest $request, JadwalDokter $jadwalDokter)
    {
        $jadwalDokter->update($request->validated());

        return response()->json([
            'message' => 'Jadwal dokter berhasil diperbarui.',
            'data'    => $jadwalDokter->fresh()->load(['dokter', 'poli']),
        ]);
    }

    public function destroy(JadwalDokter $jadwalDokter)
    {
        $jadwalDokter->delete();

        return response()->json(['message' => 'Jadwal dokter berhasil dihapus.']);
    }

    /**
     * Jadwal tersedia untuk tanggal tertentu (digunakan saat pendaftaran).
     */
    public function tersedia(Request $request)
    {
        $request->validate(['tanggal' => 'required|date|after_or_equal:today']);

        $tanggal = $request->tanggal;
        $hariIndo = \Carbon\Carbon::parse($tanggal)->locale('id')->dayName;

        // Map hari Indonesia ke enum
        $hariMap = [
            'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu',
            'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu', 'Minggu' => 'Minggu',
        ];
        $hari = $hariMap[$hariIndo] ?? null;

        $jadwal = JadwalDokter::with(['dokter', 'poli'])
            ->where('hari', $hari)
            ->where('status', 'aktif')
            ->whereHas('dokter', fn($q) => $q->where('status', 'aktif'))
            ->whereHas('poli', fn($q) => $q->where('status', 'aktif'))
            ->get()
            ->map(function ($j) use ($tanggal) {
                $j->sisa_kuota = $j->sisaKuota($tanggal);
                $j->kuota_penuh = $j->sisa_kuota === 0;
                return $j;
            });

        return response()->json($jadwal);
    }
}
