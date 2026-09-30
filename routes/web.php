<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokterController;
use App\Http\Controllers\JadwalDokterController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PemeriksaanController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PoliController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // SPA - Semua halaman Vue.js dilayani oleh satu view
    Route::get('/dashboard', fn() => view('app'))->name('dashboard');
    Route::get('/app/{any?}', fn() => view('app'))->where('any', '.*')->name('app');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | API Routes (JSON) - Accessible by all authenticated users
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Dashboard stats
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Master Data - Pasien (semua petugas bisa akses)
        Route::apiResource('pasien', PasienController::class);
        Route::get('/pasien-cari', [PasienController::class, 'cari'])->name('pasien.cari');

        // Transaksi - Pendaftaran
        Route::apiResource('pendaftaran', PendaftaranController::class)->except(['update']);
        Route::patch('/pendaftaran/{pendaftaran}/status', [PendaftaranController::class, 'updateStatus'])
            ->name('pendaftaran.update-status');

        // Transaksi - Pemeriksaan
        Route::apiResource('pemeriksaan', PemeriksaanController::class)->except(['destroy']);

        // Transaksi - Pembayaran
        Route::apiResource('pembayaran', PembayaranController::class)->except(['update', 'destroy']);
        Route::get('/laporan-pembayaran', [PembayaranController::class, 'laporan'])->name('pembayaran.laporan');

        // Jadwal Dokter (baca bisa semua, CRUD hanya admin)
        Route::get('/jadwal-dokter', [JadwalDokterController::class, 'index'])->name('jadwal-dokter.index');
        Route::get('/jadwal-dokter/tersedia', [JadwalDokterController::class, 'tersedia'])->name('jadwal-dokter.tersedia');
        Route::get('/jadwal-dokter/{jadwalDokter}', [JadwalDokterController::class, 'show'])->name('jadwal-dokter.show');

        // Dropdown endpoints
        Route::get('/dokter/dropdown', [DokterController::class, 'dropdown'])->name('dokter.dropdown');
        Route::get('/poli/dropdown', [PoliController::class, 'dropdown'])->name('poli.dropdown');

        /*
        |----------------------------------------------------------------------
        | Admin-Only Routes
        |----------------------------------------------------------------------
        */
        Route::middleware('admin')->group(function () {
            // Master Data - Dokter (CRUD)
            Route::apiResource('dokter', DokterController::class);

            // Master Data - Poli (CRUD)
            Route::apiResource('poli', PoliController::class);

            // Jadwal Dokter (CUD)
            Route::post('/jadwal-dokter', [JadwalDokterController::class, 'store'])->name('jadwal-dokter.store');
            Route::put('/jadwal-dokter/{jadwalDokter}', [JadwalDokterController::class, 'update'])->name('jadwal-dokter.update');
            Route::delete('/jadwal-dokter/{jadwalDokter}', [JadwalDokterController::class, 'destroy'])->name('jadwal-dokter.destroy');

            // Manajemen User
            Route::get('/users/stats', [UserController::class, 'stats'])->name('users.stats');
            Route::apiResource('users', UserController::class);
            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::patch('/users/{user}/change-role',   [UserController::class, 'changeRole'])->name('users.change-role');
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        });
    });
});

require __DIR__ . '/auth.php';
