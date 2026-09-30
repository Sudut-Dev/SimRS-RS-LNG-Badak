<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\JadwalDokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // USERS: Admin & Petugas
        // ==========================================
        $admin = User::create([
            'name'     => 'Administrator SIMRS',
            'email'    => 'admin@simrs.id',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
            'no_hp'    => '081234567890',
            'is_active' => true,
        ]);

        $petugas1 = User::create([
            'name'     => 'Siti Rahayu',
            'email'    => 'petugas@simrs.id',
            'password' => Hash::make('petugas123'),
            'role'     => 'petugas',
            'no_hp'    => '082345678901',
            'is_active' => true,
        ]);

        User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'kasir@simrs.id',
            'password' => Hash::make('kasir123'),
            'role'     => 'petugas',
            'no_hp'    => '083456789012',
            'is_active' => true,
        ]);

        // ==========================================
        // POLI
        // ==========================================
        $poliData = [
            ['kode_poli' => 'PLI001', 'nama_poli' => 'Poli Umum',          'lokasi' => 'Gedung A, Lantai 1'],
            ['kode_poli' => 'PLI002', 'nama_poli' => 'Poli Anak',           'lokasi' => 'Gedung A, Lantai 2'],
            ['kode_poli' => 'PLI003', 'nama_poli' => 'Poli Penyakit Dalam', 'lokasi' => 'Gedung B, Lantai 1'],
            ['kode_poli' => 'PLI004', 'nama_poli' => 'Poli Bedah',          'lokasi' => 'Gedung B, Lantai 2'],
            ['kode_poli' => 'PLI005', 'nama_poli' => 'Poli Kandungan',      'lokasi' => 'Gedung C, Lantai 1'],
            ['kode_poli' => 'PLI006', 'nama_poli' => 'Poli Gigi',           'lokasi' => 'Gedung A, Lantai 1'],
        ];

        $poli = [];
        foreach ($poliData as $p) {
            $poli[] = Poli::create(array_merge($p, ['status' => 'aktif']));
        }

        // ==========================================
        // DOKTER
        // ==========================================
        $dokterData = [
            ['kode_dokter' => 'DKT0001', 'nama_dokter' => 'dr. Ahmad Fauzi, Sp.U',   'spesialisasi' => 'Umum',            'no_sip' => 'SIP/001/2024/XI', 'biaya_konsultasi' => 50000],
            ['kode_dokter' => 'DKT0002', 'nama_dokter' => 'dr. Sri Wahyuni, Sp.A',   'spesialisasi' => 'Anak',            'no_sip' => 'SIP/002/2024/XI', 'biaya_konsultasi' => 75000],
            ['kode_dokter' => 'DKT0003', 'nama_dokter' => 'dr. Hendra Kusuma, Sp.PD','spesialisasi' => 'Penyakit Dalam',  'no_sip' => 'SIP/003/2024/XI', 'biaya_konsultasi' => 100000],
            ['kode_dokter' => 'DKT0004', 'nama_dokter' => 'dr. Dewi Purnama, Sp.B',  'spesialisasi' => 'Bedah',           'no_sip' => 'SIP/004/2024/XI', 'biaya_konsultasi' => 125000],
            ['kode_dokter' => 'DKT0005', 'nama_dokter' => 'dr. Rina Kartika, Sp.OG', 'spesialisasi' => 'Kandungan',       'no_sip' => 'SIP/005/2024/XI', 'biaya_konsultasi' => 120000],
            ['kode_dokter' => 'DKT0006', 'nama_dokter' => 'drg. Beni Saputra',        'spesialisasi' => 'Gigi',            'no_sip' => 'SIP/006/2024/XI', 'biaya_konsultasi' => 80000],
        ];

        $dokter = [];
        foreach ($dokterData as $d) {
            $dokter[] = Dokter::create(array_merge($d, ['status' => 'aktif']));
        }

        // ==========================================
        // JADWAL DOKTER
        // ==========================================
        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $jadwalMapping = [
            // [dokter_index, poli_index, hari[], jam_mulai, jam_selesai]
            [0, 0, ['Senin', 'Rabu', 'Jumat'], '08:00', '12:00'],
            [1, 1, ['Selasa', 'Kamis'], '09:00', '13:00'],
            [2, 2, ['Senin', 'Rabu'], '10:00', '14:00'],
            [3, 3, ['Selasa', 'Jumat'], '08:00', '12:00'],
            [4, 4, ['Rabu', 'Sabtu'], '09:00', '13:00'],
            [5, 5, ['Senin', 'Kamis'], '08:00', '11:00'],
        ];

        foreach ($jadwalMapping as [$dIdx, $pIdx, $hariList, $mulai, $selesai]) {
            foreach ($hariList as $hari) {
                JadwalDokter::create([
                    'dokter_id'   => $dokter[$dIdx]->id,
                    'poli_id'     => $poli[$pIdx]->id,
                    'hari'        => $hari,
                    'jam_mulai'   => $mulai,
                    'jam_selesai' => $selesai,
                    'kuota'       => 20,
                    'status'      => 'aktif',
                ]);
            }
        }

        // ==========================================
        // PASIEN CONTOH
        // ==========================================
        $pasienData = [
            ['nik' => '6471012301870001', 'nama_pasien' => 'Muhammad Rizal',    'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bontang', 'tanggal_lahir' => '1987-01-23', 'jenis_pembayaran' => 'bpjs',    'no_bpjs' => '0001234567890'],
            ['nik' => '6471056708950002', 'nama_pasien' => 'Sari Indah',         'jenis_kelamin' => 'P', 'tempat_lahir' => 'Samarinda', 'tanggal_lahir' => '1995-08-27', 'jenis_pembayaran' => 'umum',    'no_bpjs' => null],
            ['nik' => '6471012907780003', 'nama_pasien' => 'Agus Prabowo',       'jenis_kelamin' => 'L', 'tempat_lahir' => 'Balikpapan', 'tanggal_lahir' => '1978-07-29', 'jenis_pembayaran' => 'umum',    'no_bpjs' => null],
            ['nik' => '6471015503000004', 'nama_pasien' => 'Dewi Lestari',       'jenis_kelamin' => 'P', 'tempat_lahir' => 'Bontang', 'tanggal_lahir' => '2000-03-15', 'jenis_pembayaran' => 'bpjs',    'no_bpjs' => '0001234567891'],
            ['nik' => '6471011212850005', 'nama_pasien' => 'Joko Widodo',        'jenis_kelamin' => 'L', 'tempat_lahir' => 'Jakarta', 'tanggal_lahir' => '1985-12-12', 'jenis_pembayaran' => 'asuransi','no_bpjs' => null],
        ];

        foreach ($pasienData as $idx => $p) {
            Pasien::create(array_merge($p, [
                'no_rm'         => 'RM-' . now()->format('Ymd') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'alamat'        => 'Jl. Raya Bontang No. ' . ($idx + 1) . ', Kaltim',
                'no_hp'         => '08' . str_pad($idx + 1, 10, '1', STR_PAD_LEFT),
                'golongan_darah' => ['A', 'B', 'O', 'AB', 'A'][$idx],
            ]));
        }
    }
}
