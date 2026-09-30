<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Http\Requests\DokterRequest;
use Illuminate\Http\Request;

class DokterController extends Controller
{
    /**
     * Tampilkan semua data dokter (untuk Vue component).
     */
    public function index(Request $request)
    {
        $query = Dokter::query();

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_dokter', 'like', "%{$search}%")
                  ->orWhere('spesialisasi', 'like', "%{$search}%")
                  ->orWhere('kode_dokter', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dokter = $query->orderBy('nama_dokter')->paginate(10)->withQueryString();

        return response()->json($dokter);
    }

    public function store(DokterRequest $request)
    {
        $data = $request->validated();
        $data['kode_dokter'] = Dokter::generateKode();

        $dokter = Dokter::create($data);

        return response()->json([
            'message' => 'Data dokter berhasil ditambahkan.',
            'data'    => $dokter,
        ], 201);
    }

    public function show(Dokter $dokter)
    {
        return response()->json($dokter->load('jadwalDokter.poli'));
    }

    public function update(DokterRequest $request, Dokter $dokter)
    {
        $dokter->update($request->validated());

        return response()->json([
            'message' => 'Data dokter berhasil diperbarui.',
            'data'    => $dokter->fresh(),
        ]);
    }

    public function destroy(Dokter $dokter)
    {
        // Cek apakah dokter masih punya jadwal aktif
        $jadwalAktif = $dokter->jadwalDokter()->where('status', 'aktif')->exists();
        if ($jadwalAktif) {
            return response()->json([
                'message' => 'Dokter tidak dapat dihapus karena masih memiliki jadwal aktif.',
            ], 422);
        }

        $dokter->delete();

        return response()->json(['message' => 'Data dokter berhasil dihapus.']);
    }

    /**
     * Dropdown list dokter aktif.
     */
    public function dropdown()
    {
        $dokter = Dokter::where('status', 'aktif')
            ->select('id', 'nama_dokter', 'spesialisasi', 'biaya_konsultasi')
            ->orderBy('nama_dokter')
            ->get();

        return response()->json($dokter);
    }
}
