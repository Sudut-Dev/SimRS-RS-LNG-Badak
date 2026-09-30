<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pendaftaran;
use App\Http\Requests\PembayaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pembayaran::with(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter', 'kasir']);

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        } else {
            $query->whereDate('created_at', today());
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_kwitansi', 'like', "%{$search}%")
                  ->orWhereHas('pendaftaran.pasien', fn($p) => $p->where('nama_pasien', 'like', "%{$search}%"));
            });
        }

        $pembayaran = $query->latest()->paginate(15)->withQueryString();

        // Summary harian
        $summary = [
            'total_pendapatan' => Pembayaran::whereDate('created_at', $request->tanggal ?? today())->sum('total_tagihan'),
            'total_transaksi'  => Pembayaran::whereDate('created_at', $request->tanggal ?? today())->count(),
        ];

        return response()->json([
            'data'    => $pembayaran,
            'summary' => $summary,
        ]);
    }

    public function store(PembayaranRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Cek apakah pendaftaran sudah dibayar
            $existing = Pembayaran::where('pendaftaran_id', $data['pendaftaran_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Pendaftaran ini sudah memiliki data pembayaran (No. Kwitansi: ' . $existing->no_kwitansi . ').',
                ], 422);
            }

            // Validasi: pendaftaran harus selesai diperiksa
            $pendaftaran = Pendaftaran::findOrFail($data['pendaftaran_id']);
            if ($pendaftaran->status !== 'selesai') {
                return response()->json([
                    'message' => 'Pembayaran hanya dapat diproses untuk pasien yang sudah selesai diperiksa.',
                ], 422);
            }

            // Hitung total tagihan untuk validasi
            $totalTagihan = $data['biaya_konsultasi']
                + ($data['biaya_tindakan'] ?? 0)
                + ($data['biaya_obat'] ?? 0)
                + ($data['biaya_admin'] ?? 0)
                - ($data['diskon'] ?? 0);

            // Validasi: jumlah bayar tidak boleh kurang dari total tagihan
            if ($data['jumlah_bayar'] < $totalTagihan) {
                return response()->json([
                    'message' => 'Jumlah pembayaran (Rp ' . number_format($data['jumlah_bayar'], 0, ',', '.') . ') tidak boleh kurang dari total tagihan (Rp ' . number_format($totalTagihan, 0, ',', '.') . ').',
                    'errors'  => ['jumlah_bayar' => ['Jumlah bayar tidak boleh kurang dari total tagihan.']],
                ], 422);
            }

            $data['no_kwitansi'] = Pembayaran::generateNoKwitansi();
            $data['user_id'] = auth()->id();

            $pembayaran = Pembayaran::create($data);
            $pembayaran->load(['pendaftaran.pasien', 'kasir']);

            return response()->json([
                'message'      => 'Pembayaran berhasil dicatat. No. Kwitansi: ' . $data['no_kwitansi'],
                'data'         => $pembayaran,
                'kembalian'    => $pembayaran->kembalian,
                'total_tagihan' => $totalTagihan,
            ], 201);
        });
    }

    public function show(Pembayaran $pembayaran)
    {
        return response()->json(
            $pembayaran->load(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter', 'pendaftaran.jadwalDokter.poli', 'pendaftaran.pemeriksaan', 'kasir'])
        );
    }

    /**
     * Laporan pendapatan per bulan untuk grafik.
     */
    public function laporan(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);

        $pendapatanBulanan = Pembayaran::selectRaw('MONTH(created_at) as bulan, SUM(total_tagihan) as total, COUNT(*) as jumlah')
            ->whereYear('created_at', $tahun)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $bulanLabel = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $result = array_fill(1, 12, ['bulan' => '', 'total' => 0, 'jumlah' => 0]);

        foreach ($pendapatanBulanan as $row) {
            $result[$row->bulan] = [
                'bulan'  => $bulanLabel[$row->bulan - 1],
                'total'  => (float) $row->total,
                'jumlah' => $row->jumlah,
            ];
        }

        // Isi nama bulan yang kosong
        foreach ($result as $idx => &$val) {
            if (empty($val['bulan'])) {
                $val['bulan'] = $bulanLabel[$idx - 1];
            }
        }

        return response()->json([
            'tahun' => $tahun,
            'data'  => array_values($result),
        ]);
    }
}
