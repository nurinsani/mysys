# Dokumentasi Endpoint

Aplikasi ini bukan REST API murni — mayoritas endpoint melayani AdminLTE (Blade + jQuery AJAX) dengan autentikasi berbasis **session**, bukan token. `routes/api.php` (prefix `/api/v1`) sudah discaffold untuk kebutuhan REST ke depan tapi belum berisi endpoint apa pun; seluruh endpoint yang berjalan hari ini ada di `routes/web.php`.

Untuk daftar lengkap & akurat (237 route saat dokumen ini ditulis), jalankan:

```bash
php artisan route:list --except-vendor
```

Dokumen ini meringkas endpoint per area fungsional, bukan daftar lengkap satu-satu.

## Autentikasi & Role

Session-based, lewat `AuthController`.

| Method | Path | Keterangan |
|---|---|---|
| GET | `/` | Halaman login (hanya guest) |
| POST | `/` | Proses login. Body: `email`, `password`. Sukses → redirect sesuai role. Gagal → redirect balik dengan `session('error')`. |
| POST | `/logout` | Logout, invalidate session |
| GET/POST | `/redirect` | Dipakai `RoleMiddleware` untuk mengarahkan user yang mengakses route di luar role-nya ke dashboard yang benar |

Role (`role_id` di tabel `users`) menentukan grup route yang bisa diakses lewat middleware `role:N`:

| role_id | Nama | Dashboard | Middleware grup |
|---|---|---|---|
| 1 | Admin | `/admin` | `role:1` — mayoritas fitur operasional (transaksi, realisasi, laporan, jurnal) |
| 2 | AL | `/al` | `role:2` |
| 3 | AH | `/ah` | `role:3` |
| 4 | KP | `/kp` | `role:4` |

Semua request yang lolos middleware tapi salah role akan di-redirect ke `/redirect`, yang mengarahkan ke dashboard sesuai role user yang sedang login (bukan 404/403).

## Dashboard & Activity Log

| Method | Path | Keterangan |
|---|---|---|
| GET | `/admin` | Dashboard admin: total pembiayaan outstanding, jumlah kelompok, jumlah penunggak |
| GET | `/admin/activity-log` | Log perubahan data (Spatie Activitylog) — hanya mencatat perubahan lewat Eloquent, **bukan** query `DB::table()` mentah yang dipakai di sebagian besar Service |

## Transaksi

| Method | Path | Keterangan |
|---|---|---|
| GET | `/transaksi/input-transaksi` | Halaman input transaksi tunai per-CIF |
| GET | `/transaksi/input-transaksi/get-cif/{cif}` | Cari nama anggota berdasar CIF (AJAX autofill) |
| POST | `/transaksi/input-transaksi` | Simpan transaksi. Body: `cif`, `nominal`, `jenis_transaksi` (1=Simpanan Pokok+Wajib, 2=Penarikan, 3=Setor Angsuran, 4=Pemindahbukuan, 5=Setoran WO), `jenis_pemindahan` (`debet`/`kredit`, dipakai kalau jenis 4), `jenis_simpanan` (`pokok`/`wajib`, dipakai kalau jenis 4) |
| GET | `/transaksi/input-transaksi/history/{cif}` | Riwayat transaksi hari berjalan untuk satu CIF |
| GET | `/transaksi/setoran-perkelompok` | Halaman setoran angsuran per kelompok |
| POST | `/transaksi/setoran-perkelompok/filter` | Ambil data kelompok + anggota yang masih punya angsuran aktif. Body: `code_kel` |
| POST | `/transaksi/setoran-perkelompok/proses/{code_kel}` | Proses setoran massal. Body: `pilih_anggota[]`, `input_nyata_setor[no_anggota]`, `input_debet[no_anggota]` |
| GET | `/transaksi/setoran-beda-hari` | Sama seperti setoran perkelompok, untuk kasus setoran di luar jadwal |
| POST | `/transaksi/setoran-beda-hari/filter` / `/proses/{code_kel}` | Sama seperti setoran perkelompok |
| GET | `/transaksi/jurnal-masuk` | Input jurnal manual |

## Realisasi Akad

| Method | Path | Keterangan |
|---|---|---|
| GET | `/realisasi-musyarakah` | Halaman realisasi akad Musyarokah |
| GET | `/realisasi-musyarakah/getData` | Data akad siap-realisasi (DataTables), filter `kode_kelompok`, `tanggal_realisasi` |
| POST | `/proses-realisasi-musyarakah` | Realisasi massal. Body: `ids[]` (array CIF). Baris dengan pembiayaan `os` masih > 0 otomatis di-skip (masuk daftar `batal` di response, bukan error) |
| GET | `/realisasi/murabahah` | Halaman realisasi Murabahah |
| POST | `/realisasi/murabahah/update` | Update status realisasi Murabahah |
| GET/POST | `/realisasi_wakalah`, `/proses_realisasi_wakalah` | Realisasi akad Wakalah |
| GET/POST | `/realisasi/tagihan-kelompok/*` | Realisasi tagihan per kelompok |

## Anggota & Kelompok

| Method | Path | Keterangan |
|---|---|---|
| Resource | `/anggota/*`, `/kelompok/*` | CRUD standar (`index`, `create`, `store`, `edit`, `update`, `destroy`) |
| GET | `/kelompok/data` | DataTables server-side untuk listing kelompok |
| GET | `.../cari` | Endpoint pencarian ber-AJAX (select2) untuk dropdown CIF/kelompok — dipakai supaya tidak preload ratusan ribu baris ke browser |

## Pull Data (integrasi eksternal)

`PullDataController` menyinkronkan data dari core system eksternal (`mobcol`, koneksi DB `cs` — lihat `DB_CS_*` di `.env`) ke database lokal. Dipanggil per `jenisPull` (mis. anggota, pembiayaan, simpanan) dan `transaksi` (insert/update). Lihat `PullDataService` untuk daftar kombinasi yang didukung.

## Jurnal & Posting

Group `Jurnal*Controller` dan `PostingJurnal*Controller` — input jurnal masuk/keluar/umum, lalu posting ke buku besar. Ada versi terpisah untuk unit cabang (`...Kp` suffix = Kantor Pusat).

## Laporan & Cetak

Dua kategori besar, masing-masing punya banyak controller serupa (`Report*Controller`, `Cetak*Controller`):
- **Report** — neraca, arus kas, ekuitas, laba rugi (SHU), tunggakan, mutasi, nominatif simpanan/pembiayaan.
- **Cetak** — cetak dokumen per transaksi (kartu angsuran, approval, CS/CS-WO, La Risywah, Musyarakah, Adendum, dll), umumnya render PDF (`barryvdh/laravel-dompdf`).

Endpoint export besar (buku besar, mutasi kas) memakai streaming CSV (`fputcsv` + `DB::cursor()`), bukan Excel/PhpSpreadsheet, khusus untuk dataset yang bisa mencapai ratusan ribu baris — lihat catatan Sesi 2.6 di `artefak/rencana_pengerjaan.md` untuk alasannya.

## Format Response

Endpoint AJAX (form-submit lewat `$.ajax`) konsisten pakai pola:

```json
{ "success": true|false, "message": "...", "data": {...} }
```

Status HTTP: `200` sukses, `400`/`404` kegagalan bisnis yang terduga (data tidak ditemukan, input kosong), `422` gagal validasi Form Request, `500` error tak terduga.
