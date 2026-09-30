<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use App\Http\Requests\PasienRequest;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        $query = Pasien::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_rm', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_pembayaran')) {
            $query->where('jenis_pembayaran', $request->jenis_pembayaran);
        }

        $pasien = $query->orderBy('nama_pasien')
            ->paginate(10)
            ->withQueryString();

        // Tambahkan atribut computed per item
        $pasien->getCollection()->transform(function ($p) {
            $p->append(['umur', 'jenis_kelamin_label']);
            return $p;
        });

        return response()->json($pasien);
    }

    public function store(PasienRequest $request)
    {
        $data = $request->validated();
        $data['no_rm'] = Pasien::generateNoRM();

        $pasien = Pasien::create($data);
        $pasien->append(['umur', 'jenis_kelamin_label']);

        return response()->json([
            'message' => 'Data pasien berhasil didaftarkan.',
            'data'    => $pasien,
        ], 201);
    }

    public function show(Pasien $pasien)
    {
        $pasien->append(['umur', 'jenis_kelamin_label']);
        $pasien->load([
            'pendaftaran' => fn($q) => $q->with('jadwalDokter.dokter', 'jadwalDokter.poli', 'pemeriksaan', 'pembayaran')
                ->orderByDesc('tanggal_periksa')
                ->take(10),
        ]);

        return response()->json($pasien);
    }

    public function update(PasienRequest $request, Pasien $pasien)
    {
        $pasien->update($request->validated());
        $pasien->append(['umur', 'jenis_kelamin_label']);

        return response()->json([
            'message' => 'Data pasien berhasil diperbarui.',
            'data'    => $pasien->fresh()->append(['umur', 'jenis_kelamin_label']),
        ]);
    }

    public function destroy(Pasien $pasien)
    {
        // Cek apakah pasien masih punya transaksi pending
        $transaksiBerjalan = $pasien->pendaftaran()
            ->whereIn('status', ['menunggu', 'dipanggil'])
            ->exists();

        if ($transaksiBerjalan) {
            return response()->json([
                'message' => 'Pasien tidak dapat dihapus karena masih memiliki antrian aktif.',
            ], 422);
        }

        $pasien->delete();

        return response()->json(['message' => 'Data pasien berhasil dihapus.']);
    }

    /**
     * Cari pasien berdasarkan NIK atau nama (untuk form pendaftaran).
     */
    public function cari(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3']);

        $pasien = Pasien::where('nik', 'like', "%{$request->q}%")
            ->orWhere('nama_pasien', 'like', "%{$request->q}%")
            ->orWhere('no_rm', 'like', "%{$request->q}%")
            ->select('id', 'no_rm', 'nik', 'nama_pasien', 'jenis_kelamin', 'tanggal_lahir', 'jenis_pembayaran')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                $p->append('umur');
                return $p;
            });

        return response()->json($pasien);
    }
}
