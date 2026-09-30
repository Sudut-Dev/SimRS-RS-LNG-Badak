<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\JadwalDokter;
use App\Http\Requests\PendaftaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendaftaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pendaftaran::with(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli', 'user', 'pembayaran']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_periksa', $request->tanggal);
        } else {
            $query->whereDate('tanggal_periksa', today());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('poli_id')) {
            $query->whereHas('jadwalDokter', fn($q) => $q->where('poli_id', $request->poli_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pasien', fn($p) => $p->where('nama_pasien', 'like', "%{$search}%")
                    ->orWhere('no_rm', 'like', "%{$search}%"))
                  ->orWhere('no_antrian', 'like', "%{$search}%");
            });
        }

        $pendaftaran = $query->orderBy('no_urut')
            ->paginate(15)
            ->withQueryString();

        return response()->json($pendaftaran);
    }

    public function store(PendaftaranRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $jadwal = JadwalDokter::with('poli')->findOrFail($data['jadwal_dokter_id']);

            // Validasi kuota jadwal
            $sisaKuota = $jadwal->sisaKuota($data['tanggal_periksa']);
            if ($sisaKuota <= 0) {
                return response()->json([
                    'message' => 'Maaf, kuota untuk jadwal ini sudah penuh.',
                ], 422);
            }

            // Validasi: pasien tidak boleh daftar 2x ke poli yang sama di hari yang sama
            $sudahDaftar = Pendaftaran::where('pasien_id', $data['pasien_id'])
                ->where('jadwal_dokter_id', $data['jadwal_dokter_id'])
                ->whereDate('tanggal_periksa', $data['tanggal_periksa'])
                ->whereIn('status', ['menunggu', 'dipanggil', 'selesai'])
                ->exists();

            if ($sudahDaftar) {
                return response()->json([
                    'message' => 'Pasien sudah terdaftar di jadwal ini pada tanggal tersebut.',
                ], 422);
            }

            // Generate nomor antrian & no urut
            $data['no_antrian'] = Pendaftaran::generateNoAntrian(
                $jadwal->poli->kode_poli,
                $data['tanggal_periksa']
            );
            $data['no_urut'] = Pendaftaran::generateNoUrut($data['jadwal_dokter_id'], $data['tanggal_periksa']);
            $data['user_id'] = auth()->id();

            $pendaftaran = Pendaftaran::create($data);
            $pendaftaran->load(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli']);

            return response()->json([
                'message'    => 'Pendaftaran berhasil. Nomor antrian: ' . $data['no_antrian'],
                'data'       => $pendaftaran,
                'no_antrian' => $data['no_antrian'],
                'no_urut'    => $data['no_urut'],
            ], 201);
        });
    }

    public function show(Pendaftaran $pendaftaran)
    {
        $pendaftaran->load(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli', 'user', 'pemeriksaan', 'pembayaran.kasir']);
        return response()->json($pendaftaran);
    }

    /**
     * Update status antrian (dipanggil/selesai/batal).
     */
    public function updateStatus(Request $request, Pendaftaran $pendaftaran)
    {
        $request->validate([
            'status' => 'required|in:menunggu,dipanggil,selesai,batal',
        ]);

        // Logic: status hanya bisa maju ke depan
        $allowedTransitions = [
            'menunggu'  => ['dipanggil', 'batal'],
            'dipanggil' => ['selesai', 'batal'],
            'selesai'   => [],
            'batal'     => [],
        ];

        $allowed = $allowedTransitions[$pendaftaran->status] ?? [];
        if (!in_array($request->status, $allowed)) {
            return response()->json([
                'message' => "Status tidak dapat diubah dari '{$pendaftaran->label_status}' ke '{$request->status}'.",
            ], 422);
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'dipanggil') {
            $updateData['waktu_dipanggil'] = now();
        } elseif ($request->status === 'selesai') {
            $updateData['waktu_selesai'] = now();
        }

        $pendaftaran->update($updateData);

        return response()->json([
            'message' => 'Status antrian berhasil diperbarui.',
            'data'    => $pendaftaran->fresh()->load(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli']),
        ]);
    }

    public function destroy(Pendaftaran $pendaftaran)
    {
        if (!in_array($pendaftaran->status, ['menunggu'])) {
            return response()->json([
                'message' => 'Hanya pendaftaran dengan status "menunggu" yang dapat dibatalkan/dihapus.',
            ], 422);
        }

        $pendaftaran->update(['status' => 'batal']);

        return response()->json(['message' => 'Pendaftaran berhasil dibatalkan.']);
    }
}
