# SIMRS — Sistem Informasi Manajemen Rumah Sakit


> Aplikasi web untuk manajemen rawat jalan RS LNG Badak — mencakup pendaftaran pasien, pemeriksaan, pembayaran, laporan analitik, dan manajemen pengguna dalam satu platform terintegrasi.

---

##  Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Arsitektur](#-arsitektur)
- [Instalasi](#-instalasi)
- [Akun Demo](#-akun-demo)
- [Struktur Direktori](#-struktur-direktori)
- [API Endpoint](#-api-endpoint)
- [Screenshots](#-screenshots)

---

##  Tentang Proyek

SIMRS RS LNG Badak adalah aplikasi **Single Page Application (SPA)** berbasis web untuk mengelola alur pelayanan rawat jalan. Dibangun dengan arsitektur **Laravel + Vue.js**, sistem ini dirancang agar mudah digunakan oleh petugas rumah sakit di berbagai perangkat — desktop maupun mobile.

### Alur Pelayanan
```
Pasien Datang → Pendaftaran → Antrian Poli → Pemeriksaan Dokter → Pembayaran → Selesai
```

---

##  Fitur Utama

###  Autentikasi & Keamanan
- Login dengan halaman premium split-panel (dark/light mode)
- Demo credentials dengan satu klik
- Middleware proteksi role (`admin` / `petugas`)
- Injeksi user session ke Vue SPA via `window.__AUTH_USER__`

###  Dashboard
- Statistik real-time hari ini (pendaftaran, pendapatan, pasien menunggu)
- Grafik trend pendaftaran 7 hari terakhir
- Donut chart pendaftaran per poli
- Tabel antrian aktif

###  Pendaftaran Rawat Jalan
- Cari pasien by NIK / nama
- Pilih jadwal dokter & poli yang tersedia
- Nomor urut antrian otomatis
- Update status antrian (menunggu → dipanggil → selesai)

###  Pemeriksaan
- Input anamnesa, diagnosa (ICD-10), tindakan, resep obat
- Riwayat pemeriksaan pasien
- Integrasi langsung dari antrian pendaftaran

###  Pembayaran & Kasir
- Input pembayaran multi-metode (Tunai, Transfer, BPJS, Asuransi)
- Kalkulasi otomatis total tagihan & kembalian
- Nomor kwitansi generate otomatis
- Riwayat transaksi harian dengan filter

###  Laporan Analitik (3 Jenis)
| Laporan | Isi |
|---------|-----|
| **Harian** | Ringkasan per hari, breakdown metode & poli, tabel transaksi |
| **Bulanan** | Trend line chart, top dokter, rincian per hari |
| **Tahunan** | Bar chart 12 bulan, growth YoY, perbandingan tahun lalu |

> Semua laporan mendukung **Export CSV** dan **Cetak**

### 👥 Manajemen Pengguna
- CRUD pengguna (Tambah, Edit, Hapus)
- **Quick toggle** status aktif/nonaktif langsung dari tabel
- **Quick toggle** role Admin ↔ Petugas
- Reset password oleh admin (dengan strength meter)
- Statistik pengguna (total, admin, petugas, aktif, nonaktif)
- Filter pencarian & export CSV
- Konfirmasi hapus dengan ketik nama

###  UI/UX
- **Dark Mode & Light Mode** — persisten via localStorage
- **Responsive** — sidebar collapsible, tabel adaptif untuk mobile
- Tema warna medical (biru primer, tipografi Inter + Plus Jakarta Sans)
- Toast notification untuk semua aksi
- Loading state & spinner di setiap operasi async

---

##  Teknologi

### Backend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| **Laravel** | 12.x | Framework PHP, routing, middleware |
| **Laravel Breeze** | — | Autentikasi (login, register, reset password) |
| **MySQL** | 8.x | Database utama |
| **Eloquent ORM** | — | Model & relasi database |

### Frontend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| **Vue.js** | 3.x | SPA framework (Composition API) |
| **Vue Router** | 4.x | Client-side routing |
| **Chart.js + vue-chartjs** | — | Grafik dashboard & laporan |
| **Axios** | — | HTTP client untuk API calls |
| **Vite** | 7.x | Build tool & dev server |

---

##  Arsitektur

```
┌─────────────────────────────────────────────────┐
│                  Browser (SPA)                   │
│  Vue.js 3 + Vue Router + Chart.js + Axios        │
└──────────────────┬──────────────────────────────┘
                   │ HTTP (JSON API)
┌──────────────────▼──────────────────────────────┐
│                Laravel 12                        │
│  ┌────────────┐  ┌──────────────┐               │
│  │ web.php    │  │ Middleware   │               │
│  │ (routes)   │  │ auth, admin  │               │
│  └─────┬──────┘  └──────────────┘               │
│        │                                         │
│  ┌─────▼──────────────────────────────────┐      │
│  │            Controllers                  │      │
│  │  Dashboard · Pendaftaran · Pembayaran  │      │
│  │  Laporan · User · Dokter · Poli        │      │
│  └─────┬──────────────────────────────────┘      │
│        │                                         │
│  ┌─────▼──────────────────────────────────┐      │
│  │         Eloquent Models & DB           │      │
│  └────────────────────────────────────────┘      │
└─────────────────────────────────────────────────┘
                   │
        ┌──────────▼──────────┐
        │       MySQL         │
        └─────────────────────┘
```

---

##  Instalasi

### Prasyarat
- PHP >= 8.2
- Composer
- Node.js >= 18.x
- MySQL >= 8.0

### Langkah Instalasi

```bash
# 1. Clone repository
git clone <url-repo>
cd simrs

# 2. Install dependensi PHP
composer install

# 3. Install dependensi Node.js
npm install

# 4. Salin file environment
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Konfigurasi database di .env
# DB_DATABASE=simrs
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Jalankan migrasi & seeder
php artisan migrate --seed

# 8. Build assets
npm run build

# 9. Jalankan server
php artisan serve
```

### Development Mode

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (HMR)
npm run dev
```

---

##  Akun Demo

| Role | Email | Password | Akses |
|------|-------|----------|-------|
| **Administrator** | `admin@simrs.id` | `admin123` | Semua fitur + Manajemen User, Dokter, Poli |
| **Petugas** | `petugas@simrs.id` | `petugas123` | Pendaftaran, Pemeriksaan, Pembayaran, Laporan |
| **Kasir** | `kasir@simrs.id` | `kasir123` | Pendaftaran, Pembayaran, Laporan |

---

##  Struktur Direktori

```
simrs/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── PendaftaranController.php
│   │   │   ├── PemeriksaanController.php
│   │   │   ├── PembayaranController.php
│   │   │   ├── LaporanController.php      ← Laporan Harian/Bulanan/Tahunan
│   │   │   ├── UserController.php         ← Manajemen Pengguna
│   │   │   ├── PasienController.php
│   │   │   ├── DokterController.php
│   │   │   └── PoliController.php
│   │   └── Middleware/
│   │       └── AdminMiddleware.php
│   └── Models/
│       ├── User.php
│       ├── Pasien.php
│       ├── Dokter.php
│       ├── Poli.php
│       ├── JadwalDokter.php
│       ├── Pendaftaran.php
│       ├── Pemeriksaan.php
│       └── Pembayaran.php
│
├── resources/
│   ├── css/
│   │   └── app.css                        ← CSS variables, dark/light mode
│   ├── js/
│   │   ├── App.vue                        ← Layout utama (sidebar, topbar)
│   │   ├── router.js                      ← Vue Router config
│   │   ├── app.js                         ← Entry point
│   │   ├── composables/
│   │   │   ├── useApi.js                  ← HTTP wrapper
│   │   │   └── useToast.js                ← Notifikasi toast
│   │   └── pages/
│   │       ├── Dashboard.vue
│   │       ├── Pendaftaran.vue
│   │       ├── Pembayaran.vue
│   │       ├── Laporan.vue                ← Laporan Harian/Bulanan/Tahunan
│   │       ├── ManajemenUser.vue          ← Manajemen Pengguna
│   │       ├── MasterPasien.vue
│   │       ├── MasterDokter.vue
│   │       ├── MasterPoli.vue
│   │       └── JadwalDokter.vue
│   └── views/
│       ├── app.blade.php                  ← SPA shell
│       └── auth/
│           └── login.blade.php            ← Halaman login premium
│
├── routes/
│   └── web.php                            ← Semua route (SPA + API)
│
└── database/
    ├── migrations/                         ← Skema tabel
    └── seeders/
        └── DatabaseSeeder.php             ← Data awal (user, poli, dokter, jadwal, pasien)
```

---

## 🔌 API Endpoint

Semua endpoint memerlukan autentikasi (`auth` middleware). Endpoint bertanda `` khusus Admin.

### Dashboard
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/dashboard` | Statistik & data chart dashboard |

### Laporan
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/laporan/harian` | Laporan harian (param: `tanggal`) |
| `GET` | `/api/laporan/bulanan` | Laporan bulanan (param: `bulan` Y-m) |
| `GET` | `/api/laporan/tahunan` | Laporan tahunan (param: `tahun`) |

### Manajemen Pengguna 
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/users` | Daftar pengguna (filter: search, role, status) |
| `GET` | `/api/users/stats` | Statistik ringkasan pengguna |
| `POST` | `/api/users` | Tambah pengguna baru |
| `PUT` | `/api/users/{id}` | Update data pengguna |
| `DELETE` | `/api/users/{id}` | Hapus pengguna |
| `PATCH` | `/api/users/{id}/toggle-status` | Toggle aktif/nonaktif |
| `PATCH` | `/api/users/{id}/change-role` | Ubah role pengguna |
| `POST` | `/api/users/{id}/reset-password` | Reset password |

### Master Data 
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET/POST/PUT/DELETE` | `/api/dokter` | CRUD data dokter |
| `GET/POST/PUT/DELETE` | `/api/poli` | CRUD data poli |
| `GET/POST/PUT/DELETE` | `/api/jadwal-dokter` | CRUD jadwal dokter |

### Transaksi
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET/POST` | `/api/pasien` | Data pasien (semua user) |
| `GET/POST` | `/api/pendaftaran` | Pendaftaran rawat jalan |
| `PATCH` | `/api/pendaftaran/{id}/status` | Update status antrian |
| `GET/POST/PUT` | `/api/pemeriksaan` | Data pemeriksaan |
| `GET/POST` | `/api/pembayaran` | Data pembayaran & kasir |

---

##  Screenshots

### Login
> Halaman login split-panel dengan panel kiri dekoratif, demo credentials, toggle dark/light mode.

### Dashboard
> Statistik hari ini, grafik trend pendaftaran 7 hari, donut chart per poli, antrian aktif.

### Laporan
> Tiga tab laporan (Harian / Bulanan / Tahunan) dengan grafik, tabel, dan export CSV.

### Manajemen Pengguna
> Stat cards yang bisa diklik sebagai filter, quick toggle status & role, modal reset password dengan strength meter.

---

##  Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).

---

