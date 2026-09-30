<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Pendaftaran;
use App\Models\Pembayaran;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();
        $user = auth()->user();

        // Statistik utama hari ini
        $stats = [
            'pendaftaran_hari_ini' => Pendaftaran::whereDate('tanggal_periksa', $today)->count(),
            'pasien_selesai'       => Pendaftaran::whereDate('tanggal_periksa', $today)->where('status', 'selesai')->count(),
            'pasien_menunggu'      => Pendaftaran::whereDate('tanggal_periksa', $today)->whereIn('status', ['menunggu', 'dipanggil'])->count(),
            'pendapatan_hari_ini'  => Pembayaran::whereDate('created_at', $today)->sum('total_tagihan'),
            'total_pasien'         => Pasien::count(),
            'total_dokter'         => Dokter::where('status', 'aktif')->count(),
        ];

        // Data chart: pendaftaran 7 hari terakhir
        $chartPendaftaran = Pendaftaran::selectRaw('DATE(tanggal_periksa) as tanggal, COUNT(*) as total')
            ->where('tanggal_periksa', '>=', now()->subDays(6)->toDateString())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(fn($row) => [
                'tanggal' => \Carbon\Carbon::parse($row->tanggal)->locale('id')->isoFormat('ddd, D MMM'),
                'total'   => $row->total,
            ]);

        // Data chart: pendaftaran per poli hari ini
        $chartPerPoli = Poli::withCount(['jadwalDokter as pendaftaran_count' => function ($q) use ($today) {
            $q->join('pendaftaran', 'pendaftaran.jadwal_dokter_id', '=', 'jadwal_dokter.id')
              ->whereDate('pendaftaran.tanggal_periksa', $today);
        }])->where('status', 'aktif')->get()
            ->map(fn($p) => ['nama' => $p->nama_poli, 'total' => $p->pendaftaran_count]);

        // Data chart: pendapatan per metode bayar (bulan ini)
        $chartMetodeBayar = Pembayaran::selectRaw('metode_bayar, SUM(total_tagihan) as total')
            ->whereMonth('created_at', now()->month)
            ->groupBy('metode_bayar')
            ->get()
            ->map(fn($r) => ['metode' => ucfirst($r->metode_bayar), 'total' => $r->total]);

        // Antrian terkini hari ini (10 terakhir)
        $antrianTerkini = Pendaftaran::with(['pasien', 'jadwalDokter.dokter', 'jadwalDokter.poli'])
            ->whereDate('tanggal_periksa', $today)
            ->whereIn('status', ['menunggu', 'dipanggil'])
            ->orderBy('no_urut')
            ->take(10)
            ->get();

        return response()->json([
            'stats'            => $stats,
            'chartPendaftaran' => $chartPendaftaran,
            'chartPerPoli'     => $chartPerPoli,
            'chartMetodeBayar' => $chartMetodeBayar,
            'antrianTerkini'   => $antrianTerkini,
            'user'             => [
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
}
