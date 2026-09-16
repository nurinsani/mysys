# Musyarokah App

Aplikasi inti operasional **KSPPS** (koperasi simpan pinjam syariah): pencatatan anggota &amp; kelompok, transaksi simpanan/pembiayaan, realisasi akad (Musyarokah, Murabahah, Wakalah), jurnal &amp; posting akuntansi, sampai pelaporan (neraca, laba rugi, tunggakan, SHU). Dibangun di atas Laravel 11 + AdminLTE 3.

## Kebutuhan Sistem

- PHP ^8.2
- MySQL (skema live sudah menyimpang di beberapa tabel dari file migration bawaan — lihat [`artefak/rencana_pengerjaan.md`](artefak/rencana_pengerjaan.md) untuk catatan lengkapnya)
- Composer
- Node.js + npm (untuk build asset Vite)

## Instalasi

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Isi `.env`:
- `DB_*` — koneksi ke database MySQL utama aplikasi.
- `DB_CS_*` — koneksi read-only ke database `mobcol` (core system eksternal), dipakai `PullDataService` untuk sinkronisasi data. Minta kredensial ke tim infra; jangan commit nilai sungguhannya.

```bash
php artisan migrate
php artisan db:seed   # opsional: role, menu, param dasar
php artisan serve
```

### Laravel Telescope (dev only)

Terpasang sebagai `require-dev` dan hanya didaftarkan saat `APP_ENV=local` (lihat `AppServiceProvider::register()`) — tidak aktif dan tidak akan crash di production meski `composer install --no-dev`. Akses lewat `/telescope` saat berjalan lokal.

## Menjalankan Test

Aplikasi ini **tidak punya database test terpisah** — seluruh test (`php artisan test`) jalan terhadap database MySQL development yang sama dengan yang dipakai aplikasi sehari-hari. Untuk menjaga keamanan data:

- Semua test class memakai trait `DatabaseTransactions` (bukan `RefreshDatabase`) — tiap test dibungkus transaksi dan otomatis di-rollback, tidak ada data yang tertinggal permanen.
- **Jangan** ganti ke `RefreshDatabase` kecuali database test terpisah benar-benar disiapkan — `RefreshDatabase` menjalankan ulang migrasi yang skemanya sudah tidak 1:1 dengan tabel live.

```bash
php artisan test
```

## Struktur Kode (ringkas)

```
app/
  Http/Controllers/     ← HTTP layer, tipis: validasi via Form Request, delegasi ke Service
  Http/Requests/         ← Form Request (validasi terpusat per aksi)
  Services/              ← business logic (Transaksi, Setoran, Realisasi, PullData, ...)
  Repositories/           ← akses data untuk entitas dengan query kompleks/berulang
    Contracts/            ← interface, di-bind ke implementasi Eloquent di AppServiceProvider
    Eloquent/
  Models/                ← Eloquent model (nama tabel lowercase menyesuaikan skema live)
routes/
  web.php                 ← seluruh rute aplikasi (AdminLTE, session-based auth, role:N middleware)
  api.php                 ← scaffold /api/v1, belum dipakai (lihat komentar di file)
artefak/
  rencana_pengerjaan.md   ← riwayat & rencana kerja refactoring, per sesi
  evaluasi_enterprise.md  ← audit pola arsitektur vs standar enterprise, temuan & prioritas
docs/
  api.md                  ← ringkasan endpoint & pola integrasi
```

Detail lebih lanjut soal pola arsitektur (Service Layer, Repository, Form Request) dan riwayat setiap tahap refactoring ada di [`artefak/rencana_pengerjaan.md`](artefak/rencana_pengerjaan.md).

## Dokumentasi Endpoint

Lihat [`docs/api.md`](docs/api.md).

## Lisensi

Internal — tidak untuk didistribusikan di luar organisasi.
