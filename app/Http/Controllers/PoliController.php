<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use App\Http\Requests\PoliRequest;
use Illuminate\Http\Request;

class PoliController extends Controller
{
    public function index(Request $request)
    {
        $query = Poli::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_poli', 'like', "%{$search}%")
                  ->orWhere('kode_poli', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $poli = $query->orderBy('nama_poli')->paginate(10)->withQueryString();

        return response()->json($poli);
    }

    public function store(PoliRequest $request)
    {
        $data = $request->validated();
        $data['kode_poli'] = Poli::generateKode();

        $poli = Poli::create($data);

        return response()->json([
            'message' => 'Data poli berhasil ditambahkan.',
            'data'    => $poli,
        ], 201);
    }

    public function show(Poli $poli)
    {
        return response()->json($poli->load('jadwalDokter.dokter'));
    }

    public function update(PoliRequest $request, Poli $poli)
    {
        $poli->update($request->validated());

        return response()->json([
            'message' => 'Data poli berhasil diperbarui.',
            'data'    => $poli->fresh(),
        ]);
    }

    public function destroy(Poli $poli)
    {
        $jadwalAktif = $poli->jadwalDokter()->where('status', 'aktif')->exists();
        if ($jadwalAktif) {
            return response()->json([
                'message' => 'Poli tidak dapat dihapus karena masih memiliki jadwal dokter aktif.',
            ], 422);
        }

        $poli->delete();

        return response()->json(['message' => 'Data poli berhasil dihapus.']);
    }

    public function dropdown()
    {
        $poli = Poli::where('status', 'aktif')
            ->select('id', 'kode_poli', 'nama_poli', 'lokasi')
            ->orderBy('nama_poli')
            ->get();

        return response()->json($poli);
    }
}
