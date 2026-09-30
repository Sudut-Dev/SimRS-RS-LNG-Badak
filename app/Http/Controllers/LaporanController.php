<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pendaftaran;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Laporan ringkasan harian.
     */
    public function harian(Request $request)
    {
        $tanggal = $request->get('tanggal', today()->toDateString());

        $pembayaran = Pembayaran::with(['pendaftaran.pasien', 'pendaftaran.jadwalDokter.dokter',
                                        'pendaftaran.jadwalDokter.poli', 'kasir'])
            ->whereDate('created_at', $tanggal)
            ->latest()
            ->get();

        $pendaftaran = Pendaftaran::with(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli'])
            ->whereDate('tanggal_periksa', $tanggal)
            ->orderBy('no_urut')
            ->get();

        $perMetode = Pembayaran::selectRaw('metode_bayar, COUNT(*) as jumlah, SUM(total_tagihan) as total')
            ->whereDate('created_at', $tanggal)
            ->groupBy('metode_bayar')
            ->get();

        $perPoli = Poli::withCount(['jadwalDokter as pendaftaran_count' => function ($q) use ($tanggal) {
            $q->join('pendaftaran', 'pendaftaran.jadwal_dokter_id', '=', 'jadwal_dokter.id')
              ->whereDate('pendaftaran.tanggal_periksa', $tanggal);
        }])
        ->having('pendaftaran_count', '>', 0)
        ->get(['id', 'nama_poli']);

        return response()->json([
            'tanggal'     => $tanggal,
            'ringkasan'   => [
                'total_pendaftaran' => $pendaftaran->count(),
                'pasien_selesai'    => $pendaftaran->where('status', 'selesai')->count(),
                'pasien_menunggu'   => $pendaftaran->whereIn('status', ['menunggu', 'dipanggil'])->count(),
                'total_transaksi'   => $pembayaran->count(),
                'total_pendapatan'  => $pembayaran->sum('total_tagihan'),
                'rata_tagihan'      => $pembayaran->count() > 0 ? $pembayaran->avg('total_tagihan') : 0,
            ],
            'pembayaran'  => $pembayaran,
            'pendaftaran' => $pendaftaran,
            'per_metode'  => $perMetode,
            'per_poli'    => $perPoli,
        ]);
    }

    /**
     * Laporan bulanan (range tanggal custom).
     */
    public function bulanan(Request $request)
    {
        $bulan = $request->get('bulan', now()->format('Y-m'));
        [$tahun, $bln] = explode('-', $bulan);

        $start = "$tahun-$bln-01";
        $end   = date('Y-m-t', strtotime($start));

        // Pendapatan per hari dalam bulan
        $perHari = Pembayaran::selectRaw('DATE(created_at) as tanggal, SUM(total_tagihan) as total, COUNT(*) as jumlah')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Pendapatan per metode
        $perMetode = Pembayaran::selectRaw('metode_bayar, COUNT(*) as jumlah, SUM(total_tagihan) as total')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->groupBy('metode_bayar')
            ->get();

        // Pendaftaran per poli
        $perPoli = Poli::withCount(['jadwalDokter as pendaftaran_count' => function ($q) use ($start, $end) {
            $q->join('pendaftaran', 'pendaftaran.jadwal_dokter_id', '=', 'jadwal_dokter.id')
              ->whereBetween(DB::raw('DATE(pendaftaran.tanggal_periksa)'), [$start, $end]);
        }])
        ->having('pendaftaran_count', '>', 0)
        ->orderByDesc('pendaftaran_count')
        ->get(['id', 'nama_poli']);

        // Top dokter
        $topDokter = Dokter::withCount(['jadwalDokter as pendaftaran_count' => function ($q) use ($start, $end) {
            $q->join('pendaftaran', 'pendaftaran.jadwal_dokter_id', '=', 'jadwal_dokter.id')
              ->whereBetween(DB::raw('DATE(pendaftaran.tanggal_periksa)'), [$start, $end]);
        }])
        ->having('pendaftaran_count', '>', 0)
        ->orderByDesc('pendaftaran_count')
        ->take(5)
        ->get(['id', 'nama_dokter', 'spesialisasi']);

        $totalPendapatan = $perMetode->sum('total');
        $totalTransaksi  = $perMetode->sum('jumlah');

        return response()->json([
            'bulan'        => $bulan,
            'periode'      => $start . ' s/d ' . $end,
            'ringkasan'    => [
                'total_pendapatan'  => $totalPendapatan,
                'total_transaksi'   => $totalTransaksi,
                'rata_per_hari'     => $perHari->count() > 0 ? round($totalPendapatan / $perHari->count()) : 0,
                'total_pendaftaran' => Pendaftaran::whereBetween(
                    DB::raw('DATE(tanggal_periksa)'), [$start, $end]
                )->count(),
            ],
            'per_hari'     => $perHari,
            'per_metode'   => $perMetode,
            'per_poli'     => $perPoli,
            'top_dokter'   => $topDokter,
        ]);
    }

    /**
     * Laporan tahunan (12 bulan).
     */
    public function tahunan(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);

        $bulanLabel = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];

        $pendapatan = Pembayaran::selectRaw('MONTH(created_at) as bulan, SUM(total_tagihan) as total, COUNT(*) as jumlah')
            ->whereYear('created_at', $tahun)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $pendaftaran = Pendaftaran::selectRaw('MONTH(tanggal_periksa) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_periksa', $tahun)
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = [
                'bulan'        => $bulanLabel[$m - 1],
                'pendapatan'   => (float) ($pendapatan->get($m)?->total ?? 0),
                'transaksi'    => (int)   ($pendapatan->get($m)?->jumlah ?? 0),
                'pendaftaran'  => (int)   ($pendaftaran->get($m)?->total ?? 0),
            ];
        }

        // Perbandingan tahun lalu
        $prevYear      = $tahun - 1;
        $totalIniTahun = Pembayaran::whereYear('created_at', $tahun)->sum('total_tagihan');
        $totalTahunLalu = Pembayaran::whereYear('created_at', $prevYear)->sum('total_tagihan');
        $growth = $totalTahunLalu > 0
            ? round((($totalIniTahun - $totalTahunLalu) / $totalTahunLalu) * 100, 1)
            : null;

        return response()->json([
            'tahun'         => $tahun,
            'data'          => $data,
            'ringkasan'     => [
                'total_pendapatan'  => $totalIniTahun,
                'total_transaksi'   => Pembayaran::whereYear('created_at', $tahun)->count(),
                'total_pendaftaran' => Pendaftaran::whereYear('tanggal_periksa', $tahun)->count(),
                'total_pasien_baru' => Pasien::whereYear('created_at', $tahun)->count(),
                'growth'            => $growth,
                'prev_year_total'   => $totalTahunLalu,
            ],
        ]);
    }
}
