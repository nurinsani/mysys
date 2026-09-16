# 🗂️ Rencana Pengerjaan Enterprise — Musyarokah App
> **Strategi:** Setiap sesi = 1 percakapan pendek, 1 fokus, 1 hasil nyata.  
> Cukup ketik: **"Kerjakan Sesi X.Y"** untuk memulai.

---

## ⚡ FASE 1 — Foundation (Quick Wins)
> **Target:** Perbaikan fundamental tanpa rewrite besar. ~6 sesi.

---

### ✅ Sesi 1.1 — BaseController + Menu Caching
**Status:** ✅ SELESAI (dikerjakan & diverifikasi 2026-08-20). Sisa kecil: `PembiayaanController@edit` masih pakai query menu lama tanpa filter role_id — tidak berbahaya, tinggal dirapikan kapan saja.

**Scope:** 1 file baru + edit semua controller index()

**Yang dikerjakan:**
- Buat `app/Http/Controllers/BaseController.php` dengan method `getMenus()` + caching
- Buat `app/Http/Traits/HasMenus.php`  
- Refactor semua `index()` method di setiap controller untuk pakai BaseController

**File yang disentuh:**
```
app/Http/Controllers/BaseController.php   ← [BARU]
app/Http/Controllers/*.php                ← hapus duplikasi $menus
```

**Verifikasi:** Semua halaman masih tampil menu dengan benar.

---

### ✅ Sesi 1.2 — SoftDeletes pada Model Keuangan
**Status:** ✅ SELESAI (2026-08-20). Hanya dipasang ke `anggota, pembiayaan, simpanan, simpanan_pokok, simpanan_wajib, kelompok` — sesuai koreksi di `review_kritis_rencana.md` (tabel `tunggakan`, `pembiayaan_detail`, `tabel_rugi_laba`, `temp_akad_mus`, `pull_data` dikecualikan karena memang hard-delete by design). Bug ditemukan & diperbaiki: listing kelompok (`KelompokController::data()`) masih pakai raw query sehingga kelompok yang sudah dihapus tetap muncul — sudah diganti ke Eloquent.

**Scope:** Migration baru + update models

**Yang dikerjakan:**
- Buat migration: tambah kolom `deleted_at` ke tabel: `anggota`, `pembiayaan`, `simpanan`, `kelompok`, `temp_akad_mus`
- Update semua model tersebut tambah `use SoftDeletes`
- Pastikan query yang perlu data terhapus menggunakan `withTrashed()`

**File yang disentuh:**
```
database/migrations/xxxx_add_softdelete_to_tables.php  ← [BARU]
app/Models/Anggota.php
app/Models/pembiayaan.php
app/Models/simpanan.php
app/Models/Kelompok.php
app/Models/temp_akad_mus.php
```

**Verifikasi:** `php artisan migrate` berhasil, hapus data tidak benar-benar hilang.

---

### ✅ Sesi 1.3 — Audit Trail (created_by, updated_by)
**Status:** ✅ SELESAI untuk kolom model (2026-08-20). Trait `HasAuditTrail` sudah terpasang di semua model tabel master (`anggota, pembiayaan, simpanan, simpanan_pokok, simpanan_wajib, kelompok`). Bug ditemukan & diperbaiki: `simpanan_wajib` tadinya belum pakai trait ini, sudah ditambahkan.
**Catatan:** kolom `ip_address` di tabel `tabel_transaksi` sudah ada di migration tapi belum ada yang mengisinya (tabel ini tidak punya Eloquent model, semua insert manual via `DB::table()` tersebar di 16 controller transaksi). Pekerjaan mengisi `ip_address` di semua insert tersebut dipindah ke **Sesi 1.7** (lihat di bawah), agar tidak dikerjakan sekaligus dengan Sesi 1.3.

**Scope:** 1 trait + 1 migration + update models

**Yang dikerjakan:**
- Buat migration: tambah `created_by`, `updated_by`, `ip_address` ke tabel keuangan utama
- Buat `app/Http/Traits/HasAuditTrail.php` dengan auto-fill dari `auth()->id()`
- Pasang trait ke semua model keuangan

**File yang disentuh:**
```
database/migrations/xxxx_add_audit_trail_to_tables.php  ← [BARU]
app/Http/Traits/HasAuditTrail.php                       ← [BARU]
app/Models/*.php                                         ← pasang trait
```

**Verifikasi:** Setiap create/update di tabel keuangan otomatis terisi `created_by`.

---

### ✅ Sesi 1.4 — Fix Dashboard Admin (KPI Real)
**Status:** ✅ SELESAI sebagian (2026-08-20). "Penunggak" (dari tabel `tunggakan`, distinct CIF dengan saldo tunggak > 0) dan "Kelompok" (`Kelompok::count()`) sudah diganti dari hardcoded `44` dan `1.000` ke data real. NoA & Outstanding sudah real dari sebelumnya.
**Catatan:** KPI tambahan (NPF%, Total Simpanan, SHU YTD) dan ringkasan transaksi hari ini **belum dikerjakan** — butuh definisi bisnis yang jelas (terutama NPF, belum ada definisi baku di codebase) dan SHU YTD melibatkan perhitungan P&L yang kompleks (lihat `HitungShuController`). Dipindah ke **Sesi 1.8** agar tidak menebak logika bisnis sembarangan.

**Scope:** 1 controller + 1 view

**Yang dikerjakan:**
- Refactor `AdminController@index` — ambil semua KPI dari DB (NoA real, OS real, Penunggak real, Kelompok real)
- Tambah KPI baru: NPF%, Total Simpanan, SHU YTD
- Update `resources/views/admin/index.blade.php` tampilkan semua KPI dinamis
- Tambah ringkasan transaksi hari ini

**File yang disentuh:**
```
app/Http/Controllers/AdminController.php
resources/views/admin/index.blade.php
```

**Verifikasi:** Dashboard menampilkan data real dari database.

---

### ✅ Sesi 1.5 — Form Request Classes (Transaksi & Setoran)
**Status:** ✅ SELESAI (2026-08-20). Aturan validasi di-copy-exact dari kode lama, tidak ada yang diperketat:
- `StoreInputTransaksiRequest` — persis sama dengan validasi inline lama di `InputTransaksiController::store`.
- `FilterSetoranPerkelompokRequest` & `FilterSetoranBedaHariRequest` — sebelumnya `filter()` di kedua controller **tidak ada validasi sama sekali** (`code_kel` diambil langsung dari input, kalau kosong baru gagal di query DB dan balas 404 custom). Ditambahkan `code_kel => required|string` sebagai pengaman formal — aman karena frontend (`select2-ajax`) sudah memastikan `code_kel` selalu terisi sebelum request dikirim, jadi tidak mengubah perilaku pada alur normal.
- `ProsesRealisasiMusyarokahRequest` — sebelumnya `realisasiMusyarokah()` cuma cek manual `empty($cekbox) || !is_array($cekbox)`. Diganti `ids => required|array|min:1` yang setara; frontend sudah cek `selectedIds.length === 0` sebelum kirim, jadi tidak mengubah alur normal.

**Scope:** Buat Form Request untuk controller-controller utama

**Yang dikerjakan:**
- `StoreInputTransaksiRequest.php`
- `FilterSetoranPerkelompokRequest.php`
- `FilterSetoranBedaHariRequest.php`
- `ProsesRealisasiMusyarokahRequest.php`
- Update controller terkait pakai Form Request

**File yang disentuh:**
```
app/Http/Requests/Transaksi/StoreInputTransaksiRequest.php     ← [BARU]
app/Http/Requests/Setoran/FilterSetoranPerkelompokRequest.php  ← [BARU]
app/Http/Requests/Setoran/FilterSetoranBedaHariRequest.php     ← [BARU]
app/Http/Requests/Realisasi/ProsesRealisasiMusyarokahRequest.php ← [BARU]
app/Http/Controllers/InputTransaksiController.php
app/Http/Controllers/SetoranPerkelompokController.php
app/Http/Controllers/SetoranBedaHariController.php
app/Http/Controllers/RealisasiMusyarokahController.php
```

**Verifikasi:** Validasi tetap berjalan, error message tampil dengan benar.

---

### ✅ Sesi 1.6 — Pisah routes/api.php
**Status:** ✅ SELESAI dengan scope revisi aman (2026-08-20), sesuai rekomendasi `review_kritis_rencana.md`. **Tidak ada endpoint lama yang dipindah** — sengaja dibatalkan karena 85+ AJAX call di blade pakai URL hardcoded, memindah URL-nya berisiko mematikan halaman transaksi.

Yang benar-benar dikerjakan:
- Buat `routes/api.php` (baru, kosong — cuma scaffold `Route::prefix('v1')`)
- Daftarkan di `bootstrap/app.php` via `withRouting(api: ...)` — otomatis dapat prefix `/api` + middleware group `api`
- `routes/web.php` **tidak disentuh sama sekali** — semua endpoint lama, semua AJAX hardcoded, semua nama route tetap sama persis

**Verifikasi yang sudah dilakukan:**
- `php artisan route:list` — semua 234 baris route lama masih terdaftar utuh, tidak ada yang hilang/berubah
- Tidak ada route cache (`bootstrap/cache/routes-v7.php` tidak ada) — jadi tidak ada isu stale cache
- Tidak ada konflik nama route (file api.php terpisah total, prefix `/api` berbeda dari semua path web.php yang ada)

**Untuk ke depannya:** endpoint JSON yang benar-benar **baru** (bukan hasil pindahan) bisa langsung ditaruh di `routes/api.php` dengan prefix `/api/v1/...`. Memindahkan 85+ endpoint lama ke sana butuh kerja bertahap: ganti dulu semua URL hardcoded di blade jadi `route('nama.route')`, baru aman dipindah — ini pekerjaan besar lintas puluhan file view, belum dijadwalkan sebagai sesi karena scope-nya perlu dipecah per-modul kalau mau dikerjakan.

**Scope:** Reorganisasi routes

**Yang dikerjakan:**
- Buat `routes/api.php` — pindahkan semua endpoint yang return JSON
- Update `bootstrap/app.php` register api routes
- Beri prefix `/api/v1/` untuk semua JSON endpoints
- Update semua AJAX call di frontend (JavaScript) sesuai URL baru

**File yang disentuh:**
```
routes/api.php          ← [BARU]
routes/web.php          ← hapus endpoint JSON
bootstrap/app.php       ← register api routes
resources/views/**/*.blade.php  ← update ajax URL
```

**Verifikasi:** Semua AJAX masih berfungsi, prefix `/api/v1/` aktif.

---

### ✅ Sesi 1.7 — Lengkapi Audit Trail: ip_address di tabel_transaksi
**Status:** ✅ SELESAI (2026-08-22, dikerjakan ~2 hari setelah ditemukan di Sesi 1.3)
**Scope:** Isi kolom `ip_address` (sudah ada di tabel via migration Sesi 1.3) di setiap insert manual ke `tabel_transaksi`

> [!NOTE]
> **Daftar file di rencana asli sudah usang** — 5 dari 16 controller yang disebutkan (`InputTransaksiController`, `SetoranPerkelompokController`, `SetoranBedaHariController`, `RealisasiMusyarokahController`, `RealisasiMurabahahController`) sudah tidak lagi punya insert `tabel_transaksi` langsung sejak Fase 2 memindahkan logikanya ke Service (`TransaksiService`, `SetoranPerkelompokService`, `SetoranBedaHariService`, `RealisasiMusyarokahService`, `RealisasiMurabahahService`). Dipetakan ulang lewat `grep "'id_admin' =>"` di seluruh `app/` — hasilnya tetap 16 file (11 controller + 5 service), 89 titik insert total.
>
> **Cara eksekusi:** karena polanya sangat mekanis dan berulang (`'id_admin' => $variabel` selalu jadi key terakhir sebelum `]`/`],`, tinggal tambah `'ip_address' => request()->ip(),` persis di sebelahnya) tapi variabel & indentasi beda-beda di tiap lokasi, dipakai skrip transformasi kecil (regex per-baris, preserve indentasi) daripada 89 Edit manual satu-satu — lebih presisi dan lebih cepat diverifikasi lewat diff. 1 baris sengaja dilewati (`RestrukturisasiByKelompokController.php:80`, `'id_admin' => 'required'` — itu validation rule, bukan array insert).
>
> **Insiden saat verifikasi (tidak berkaitan dengan kode produksi):** test pertama sempat "hang" tanpa output sama sekali. Diselidiki: bukan bug di kode aplikasi maupun deadlock database (dikonfirmasi `information_schema.innodb_trx` kosong, tidak ada transaksi menggantung). Akar masalah sebenarnya: `assertDatabaseHas()` di test verifikasi, saat gagal cocok, otomatis mencari "hasil mirip" untuk pesan error — dan tabel `tabel_transaksi` punya **3,6 juta+ baris**, jadi pencarian itu full-scan tabel raksasa (60-120 detik per assertion gagal). Assertion-nya sendiri gagal karena test salah tebak `kode_rekening` (menyalin dari service lain). Diperbaiki dua-duanya: `kode_rekening` yang benar, dan ganti ke query manual `ORDER BY id_transaksi DESC LIMIT 1` (pakai index primary key, tetap cepat di tabel besar) — pelajaran berlaku umum untuk test lain di tabel besar aplikasi ini ke depannya.

**Yang dikerjakan:**
- `app/Services/Transaksi/TransaksiService.php` (24 titik), `SetoranPerkelompokService.php` (4), `SetoranBedaHariService.php` (4), `RealisasiMusyarokahService.php` (2), `RealisasiMurabahahService.php` (5) — total 39 titik di layer Service.
- `app/Http/Controllers/SetoranLimaPersenController.php` (8), `PelunasanController.php` (4), `PelunasanKelompokController.php` (4), `PembatalanWakalahController.php` (2), `PemindahbukuanPerkelompokController.php` (8), `RealisasiTagihanKelompokController.php` (4), `RestrukturisasiByKelompokController.php` (8), `JurnalKeluarController.php` (2), `JurnalMasukController.php` (2), `JurnalUmumController.php` (1), `HapusBukuController.php` (7) — total 50 titik di layer Controller.
- `tests/Unit/Services/AuditTrailIpAddressTest.php` (BARU) — 2 test: `TransaksiService` (jenis_transaksi 2) dan `SetoranPerkelompokService` mengisi `ip_address` dengan benar di `tabel_transaksi`.

**Verifikasi:** reproduksi manual (dalam transaksi rollback) membuktikan `ip_address` tersimpan `'127.0.0.1'` di baris nyata. `php -l` bersih di semua 16 file. `php artisan test` → 87 test, 311 assertion, semua pass (tidak ada regresi).

---

### ✅ Sesi 1.8 — Dashboard: KPI Lanjutan (Total Simpanan, SHU YTD, Ringkasan Transaksi)
**Status:** ✅ SELESAI dengan scope revisi (2026-08-22) — NPF% sengaja tidak dikerjakan, lihat alasan di bawah.

> [!NOTE]
> **NPF% — sengaja di-skip (keputusan user).** Diinvestigasi dulu: kolom `gol` (kolektibilitas) di tabel `pembiayaan` ternyata **selalu di-hardcode `1`** saat realisasi di seluruh kode (`RealisasiMusyarokahController`/Service, dst.) — tidak ada proses apa pun yang meng-update kolektibilitas berdasarkan keterlambatan bayar. NPF% berbasis kolom itu akan selalu 0%, menyesatkan kalau ditampilkan sebagai KPI manajemen. Ditanyakan ke user: hitung dari tabel `tunggakan` (butuh definisi ambang hari) atau skip. **Dipilih: skip** — KPI ini tidak ditampilkan sampai ada proses bisnis yang benar-benar meng-update `gol` secara berkala.
>
> **SHU YTD — ditemukan bug desain penting saat investigasi.** `HitungShuController::proses()` (dipanggil user manual lewat menu Hitung SHU) **bukan** perhitungan read-only — itu proses yang me-reset SELURUH `tabel_master` (saldo_awal/akhir/mutasi di-nol-kan) lalu rebuild dari nol, dan hapus+insert-ulang `tabel_rugi_laba`. Kalau logika itu di-"reuse" secara naif dipanggil dari Dashboard (rencana asli: extract ke Service, dipanggil bersama), **setiap kali admin buka Dashboard akan memicu reset+rebuild seluruh buku besar akuntansi** — berat, dan berisiko race condition kalau 2 admin buka Dashboard bersamaan. Ditanyakan ke user, **dipilih: Dashboard cukup BACA angka hasil terakhir** (`tabel_master` kode_rekening `3902000` kolom `saldo_akhir`), tidak memicu hitung ulang apa pun. `HitungShuController` **tidak disentuh sama sekali** — tetap satu-satunya yang boleh menjalankan proses hitung/reset, dipicu manual oleh user seperti sebelumnya.

**Yang dikerjakan:**
- `app/Http/Controllers/AdminController.php` — tambah `$totalSimpanan` (`SUM(kredit-debet)` dari `simpanan` + `simpanan_pokok` + `simpanan_wajib`, pola saldo yang sudah konsisten dipakai di seluruh aplikasi ini), `$shuYtd` (baca `tabel_master`, read-only), `$transaksiHariIni` (COUNT + SUM debet/kredit dari `tabel_transaksi` where `tanggal_transaksi` = hari ini, di-scope per `unit` seperti KPI lain di controller yang sama).
- `resources/views/admin/index.blade.php` — tambah baris kedua 3 small-box (Total Simpanan, SHU YTD dengan link ke menu Hitung SHU, Ringkasan Transaksi Hari Ini).
- `tests/Feature/Dashboard/AdminDashboardTest.php` — tambah 2 test: SHU YTD terbukti dibaca dari `tabel_master` (bukan dihitung ulang — set nilai dummy di `tabel_master`, verifikasi dashboard menampilkan nilai itu persis, bukan hasil kalkulasi baru), total simpanan numerik. Test yang sudah ada diupdate untuk assert semua view data baru tersedia.

**Verifikasi:** `php artisan test` → 89 test, 320 assertion, semua pass (tidak ada regresi). **Catatan:** tidak dicek visual lewat browser (tidak ada akses browser interaktif di sesi ini) — verifikasi terbatas pada HTTP response 200 + isi view data via test, bukan pengecekan tampilan/layout sungguhan.

---

## 🚨 TEMUAN AD-HOC — Modul Anggota (2026-08-20, di luar alur sesi)

> [!NOTE]
> **Update:** Tabel `anggota` uppercase (`CUST_SHORT_NAME`, `MOBILE_PHONE`, `CODE_KEL`, dst.) yang dianalisa di bawah ini ternyata tabel yang salah taruh (tabel lama/legacy). Tabel yang benar pakai huruf kecil di semua kolom, sesuai asumsi kode asli. **Semua fix penamaan kolom sudah di-revert kembali ke huruf kecil** (`nama`, `no_hp`, `norek`, `cao`, `kode_kel`, `unit`) — kode sekarang sama seperti sebelum insiden ini. Perbaikan struktural yang TIDAK tergantung skema (hapus `Anggota::all()` yang crash, DataTables pakai Query Builder bukan `->get()`, dropdown CIF jadi AJAX-search) **tetap dipertahankan** karena itu perbaikan yang valid untuk tabel manapun.

Bermula dari laporan "halaman /anggota tidak ditemukan", ternyata membongkar rangkaian bug yang saling terkait — semuanya berakar dari satu masalah: **skema live database tabel `anggota` (305.775 baris) berbeda total dari yang diasumsikan kode aplikasi**, kemungkinan besar karena tabel ini diisi dari import data eksternal (core banking) dengan skema asli (kolom besar seperti `CUST_SHORT_NAME`, `MOBILE_PHONE`, `CODE_KEL`), bukan lewat form "Tambah Anggota" aplikasi ini sendiri.

**Sudah diperbaiki & diverifikasi:**
1. `AnggotaController::index()` — `Anggota::all()` (305rb baris, tidak dipakai di view) bikin PHP fatal error kehabisan memori → halaman "tidak ditemukan". Dihapus.
2. `KelompokController::index()` — bug identik (`Anggota::all()` untuk dropdown yang sama), ikut diperbaiki sebelum sempat dilaporkan.
3. Dropdown "Pilih CIF Ketua" (modal Tambah Kelompok, dipakai di 2 halaman) — diubah dari preload 305rb opsi → cari-sambil-ketik (select2 AJAX), endpoint baru `AnggotaController::cari()`.
4. `KelompokController::getAnggotaByCif()` — return raw Eloquent object dengan nama kolom asli, JS mengharap `no_hp` → diperbaiki.
5. `AnggotaController::data()` (sumber DataTable listing anggota) — `->latest()` error karena tabel tidak punya `created_at`; lalu ketahuan method ini juga **memuat semua 305rb baris ke PHP sebelum diserahkan ke DataTables** (menghilangkan tujuan server-side pagination) → diganti jadi kirim Query Builder langsung ke `datatables()->of()`, kolom di-alias (`CIF as cif`, `CUST_SHORT_NAME as nama`, dst.) supaya cocok dengan JS. Sekarang 1.4 detik & 40MB (dari crash/error total).

**BELUM diperbaiki — butuh sesi terpisah, scope besar:**
Saat menelusuri `AnggotaController::store()` (fitur "Tambah Data Anggota"), ketemu bahwa **hampir semua key yang di-insert (`kode_kel`, `nama`, `alamat`, `desa`, `kecamatan`, `kota`, `rtrw`, `no_hp`, `hp_pasangan`, `kelamin`, `tgl_lahir`, `ktp`, `kewarganegaraan`, `status_menikah`, `agama`, `ibu_kandung`, `pendidikan`, `kode_pos`, `tgl_join`) tidak match dengan kolom asli tabel `anggota`** (yang pakai nama seperti `CODE_KEL`, `CUST_SHORT_NAME`, `HADDRESS1-5`, `MOBILE_PHONE`, `GENDER`, `BIRTH_DATE`, `ID_NUMBER`, `CITIZENSHIP`, `MARITAL_STATUS`, `RELIGION`, `MOTHER_NAME`, `EDUCATION`). Indikasi kuat: **fitur "Tambah Data Anggota" via form aplikasi kemungkinan besar belum pernah berhasil menyimpan data** sejak skema tabel berubah — akan gagal SQL error saat submit.

Ini juga kemungkinan mempengaruhi `AnggotaController::update()`/`edit()` dan modul lain yang menyentuh data detail anggota (`AnggotaDetail`). **Rekomendasi:** jangan ditambal sepotong-sepotong lagi — perlu sesi investigasi khusus untuk memetakan SEMUA field form "Tambah/Edit Anggota" ke kolom asli yang benar sekaligus, baru diperbaiki dan ditest end-to-end (submit form sungguhan, bukan cuma simulasi API).

> [!NOTE]
> **Update (2026-08-21):** temuan "kolom tidak match" di atas sudah basi — sejak insiden tabel `anggota` di atas di-revert ke huruf kecil, skema live tabel `anggota` **sudah cocok** dengan key yang dipakai `store()`/`update()` (dikonfirmasi ulang lewat `SHOW FULL COLUMNS`). Tapi ada bug BARU yang beda akar masalah: kolom `cao_promotor` di tabel `anggota` adalah `NOT NULL` tanpa default, dan `update()` sudah mengisinya (`'cao_promotor' => strtoupper($request->cao)`) tapi `store()` **tidak** — jadi setiap submit "Tambah Data Anggota" 100% gagal dengan `SQLSTATE[HY000]: 1364 Field 'cao_promotor' doesn't have a default value`. Ini yang bikin user melaporkan "input anggota gagal". Diperbaiki: tambah `'cao_promotor' => strtoupper($request->cao)` ke `Anggota::create()` di `store()`, sama seperti yang sudah ada di `update()`. Diverifikasi lewat reproduksi manual (gagal sebelum fix, sukses sesudah, dalam transaksi yang di-rollback) + test baru `tests/Feature/Anggota/AnggotaStoreTest.php`.
>
> **Update lanjutan (2026-08-21):** 2 bug tambahan ditemukan & diperbaiki di file yang sama saat menangani laporan di atas:
> 1. **Kelamin selalu hardcode 'P'** — `store()` dan `update()` sama-sama pakai `'kelamin' => 'P'` alih-alih nilai dari form, dan form `create`/`edit` anggota memang belum punya field pilihan jenis kelamin sama sekali. Diperbaiki: tambah field select "Jenis Kelamin" (L/P) di `form.blade.php` & `form-edit.blade.php`, ganti hardcode jadi `strtoupper($request->kelamin)` di kedua method controller, tambah validasi `'kelamin' => 'required|in:L,P'` di `store()`.
> 2. **Bug `=` vs `==` di `form-edit.blade.php`** — 11 kondisi `@if ($anggota->kolom = 'NILAI')` di dropdown Status Perkawinan, Agama, dan Pendidikan pakai operator **assignment** (`=`), bukan **perbandingan** (`==`/`===`). Efeknya: SEMUA opsi di tiap dropdown itu selalu bernilai true (assignment selalu truthy), sehingga browser selalu menampilkan **opsi terakhir** di tiap dropdown (JANDA / KONGHUCU / SMA) sebagai yang ter-pilih saat form edit dibuka — **apapun data asli anggota di database**. Kalau petugas submit ulang tanpa sadar mengoreksi dropdown ini, data asli tertimpa jadi salah. Murni bug teknis (salah ketik operator), diperbaiki jadi `==` di ketiga field tsb, plus dropdown kelamin baru ditulis dengan `==` sejak awal.
>
> Diverifikasi lewat `tests/Feature/Anggota/AnggotaStoreTest.php` (3 test: simpan berhasil, kelamin tersimpan sesuai input — bukan selalu P, validasi gagal kalau kelamin kosong). Full suite: 71 test, 249 assertion, semua pass.

---

## ⚙️ FASE 2 — Refactoring (Service Layer)
> **Target:** Pisahkan business logic dari controller. ~7 sesi.

---

### ✅ Sesi 2.1 — Aktifkan Spatie Permission (Role & Permission)
**Status:** ✅ SELESAI dengan scope revisi aman (2026-08-20), sesuai `review_kritis_rencana.md` — dijalankan **paralel**, `RoleMiddleware` & `role_id` lama **tidak disentuh sama sekali**.

**Temuan kritis sebelum eksekusi (tidak ada di rencana/review awal):** Migrasi default Spatie membuat tabel bernama `roles` — tapi aplikasi ini **sudah punya tabel `roles`** sendiri (skema beda, dipakai `users.role_id` + `RoleMiddleware`). Kalau dijalankan apa adanya, migrasi akan gagal/bentrok dengan tabel produksi. Sudah diantisipasi: nama tabel role Spatie diganti ke `permission_roles` di `config/permission.php` (didahului `--pretend` dry-run untuk pastikan SQL final sudah benar sebelum benar-benar migrate).

**Yang dikerjakan:**
- Publish config + migration Spatie, rename `table_names.roles` → `permission_roles` supaya tidak bentrok
- Migrate — tabel baru: `permissions`, `permission_roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`
- `User` model ditambah trait `HasRoles` (method `roles()` — tidak bentrok dengan `role()` legacy yang sudah ada)
- `PermissionSeeder` — 62 permission granular per modul (mengikuti struktur menu yang ada), sudah dijalankan & diverifikasi masuk ke DB
- **TIDAK diubah:** `RoleMiddleware`, semua `role:1,2,3,4` di `web.php`, kolom `role_id` — akses login existing 100% sama seperti sebelumnya (diverifikasi lewat tinker: `role_id` & relasi `role()` legacy tetap jalan normal)

**Belum dikerjakan (sengaja, karena berisiko & butuh keputusan bisnis):**
- Assign role/permission Spatie ke user yang ada
- Migrasi `RoleMiddleware` dari cek `role_id` integer ke cek permission Spatie — ini perlu dipetakan dulu: role_id mana dapat permission apa saja, baru middleware-nya diganti bertahap. Kalau mau lanjut, sarankan jadi sesi terpisah supaya bisa ditest hati-hati (satu role dulu, baru role lain) — jangan sekaligus semua role, sesuai prinsip kerja di `review_kritis_rencana.md`.

**Scope:** Setup permission granular

**Yang dikerjakan:**
- `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- `php artisan migrate`
- Buat `PermissionSeeder.php` — definisikan semua permission per modul
- Update `RoleMiddleware` untuk pakai Spatie
- Update `User` model tambah `HasRoles`

**File yang disentuh:**
```
database/seeders/PermissionSeeder.php  ← [BARU]
app/Models/User.php
app/Http/Middleware/RoleMiddleware.php
```

**Verifikasi:** `php artisan db:seed --class=PermissionSeeder` berhasil.

---

### ✅ Sesi 2.2 — Service Layer: TransaksiService
**Status:** ✅ SELESAI + 5 bug pre-existing ditemukan & diperbaiki (2026-08-20).

**Extract logic:** Semua logika dari `InputTransaksiController::store()` (±710 baris) dipindah utuh ke `App\Services\Transaksi\TransaksiService::simpanTransaksi()`. Controller sekarang tinggal terima request → panggil service → return response. `DB::beginTransaction/commit/rollBack` tetap di tempat yang sama persis (sesuai prinsip kerja).

**Bug ditemukan saat verifikasi (SEMUA sudah ada sebelum sesi ini — dikonfirmasi lewat `git show HEAD`, bukan hasil extract):**
Fitur "Input Transaksi" (setoran/penarikan/pemindahbukuan) ternyata **tidak pernah bisa jalan** untuk sebagian besar jenis transaksi, karena tabel `anggota` & `simpanan` punya skema live database yang beda dari asumsi kode (kemungkinan besar tabel-tabel ini diisi lewat import data eksternal dengan skema lama, bukan lewat migration Laravel-nya):
1. `$anggota->norek/cao/kode_kel/unit` (huruf kecil) dibaca dari tabel `anggota` yang kolomnya sebenarnya `NOREK/CAO/CODE_KEL/UNIT` (huruf besar) — PHP object property case-sensitive, jadi selalu `null`, mengotori data transaksi baru secara diam-diam di SEMUA jenis transaksi. **Fix:** baca pakai nama kolom asli (huruf besar).
2. `$anggota->nama` dipakai di 24 tempat (keterangan transaksi) — kolom itu **tidak ada sama sekali** di tabel `anggota`, yang ada `CUST_SHORT_NAME`. **Fix:** ganti ke `$anggota->CUST_SHORT_NAME`. Bug yang sama juga ada di `InputTransaksiController::getByCif()` dan `getHistory()` (fitur "cari CIF tampilkan nama" di halaman input transaksi selalu kosong) — ikut diperbaiki.
3. Insert ke tabel `simpanan` (jenis transaksi 2/3/4) pakai key `'debet'`, tapi kolom aslinya `DEBIT` (beda ejaan, bukan cuma beda huruf besar/kecil) → selalu gagal SQL "Unknown column". **Fix:** key diganti `'debit'`.
4. Model `simpanan` tidak set `$timestamps = false`, padahal tabel live-nya tidak punya kolom `created_at`/`updated_at` (beda dari migration file yang usang/tidak dipakai untuk tabel ini) → insert selalu gagal. **Fix:** tambah `public $timestamps = false;`.
5. Kode set `'reff' => ...` (string custom) saat insert ke `simpanan`, padahal `REFF` di tabel live adalah `bigint auto_increment` PRIMARY KEY → data ke-truncate/gagal. **Fix:** hapus key `'reff'` dari insert ke `simpanan` (biarkan auto-increment). Catatan: `simpanan_pokok`/`simpanan_wajib` TIDAK kena masalah ini — `reff` di situ memang `varchar(191)` sesuai migration, jadi tetap diisi manual seperti semula.

**Verifikasi:** Ditest lewat tinker, 8 kombinasi jenis transaksi (1–5, termasuk 4 sub-kasus pemindahbukuan) + `getByCif` + `getHistory`, semua dibungkus transaksi DB yang di-rollback (tidak ada data tes yang tertinggal di database 30 juta baris). Sebelum fix: 6 dari 8 gagal. Setelah fix: **8/8 berhasil**, jumlah baris `simpanan` tetap sama persis setelah test (tidak ada data bocor).

**Scope:** Extract logic dari `InputTransaksiController` (823 baris)

**Yang dikerjakan:**
- Buat `app/Services/Transaksi/TransaksiService.php`
- Pindahkan semua business logic dari `InputTransaksiController` ke Service
- Controller hanya tinggal: terima request → panggil service → return response

**File yang disentuh:**
```
app/Services/Transaksi/TransaksiService.php  ← [BARU]
app/Http/Controllers/InputTransaksiController.php  ← slim down
```

**Verifikasi:** Semua transaksi masih berfungsi normal.

---

### ✅ Sesi 2.3 — Service Layer: SetoranService
**Status:** ✅ SELESAI + 1 bug pre-existing ditemukan & diperbaiki (2026-08-20).

Logika `proses()` dipindah utuh dari `SetoranPerkelompokController` dan `SetoranBedaHariController` ke `SetoranPerkelompokService`/`SetoranBedaHariService`. **Perbedaan bisnis asli antara keduanya dipertahankan persis** — bukan disatukan jadi satu service generik: format tanggal (`SetoranBedaHari` pakai tanggal saja, `SetoranPerkelompok` awalnya pakai tanggal+jam — lihat bug di bawah), teks keterangan jurnal ("Setoran an..." vs "Setoran Beda Hari An..."), dan yang paling penting — cabang "setoran melebihi OS": `SetoranPerkelompok` insert ke tabel `simpanan`, sedangkan `SetoranBedaHari` insert ke tabel `tunggakan`. Ini beda logika bisnis nyata, bukan duplikasi kebetulan.

**Bug ditemukan (hanya di SetoranPerkelompok, SetoranBedaHari sudah benar):** `$tgl_system` diisi `now()->format('Y-m-d H:i:s')` (19 karakter) untuk kolom `tabel_transaksi.tanggal_posting` yang ternyata `varchar(12)` — setiap proses setoran perkelompok yang mencapai tahap posting jurnal (jalur sukses normal) **selalu gagal SQL "Data too long"**. Diperbaiki jadi `now()->format('Y-m-d')` (10 karakter), mengikuti pola yang sudah benar di `SetoranBedaHari` dan `TransaksiService` (Sesi 2.2).

**Verifikasi:** ditest dengan data pembiayaan aktif sungguhan (dibungkus rollback) — sebelum fix: `SetoranPerkelompokService` gagal SQL, `SetoranBedaHariService` sukses. Sesudah fix: keduanya sukses (200), plus 2 skenario error (kelompok tidak ditemukan → 404, tidak ada anggota dipilih → 400) berperilaku sama seperti kode asli.

**Scope:** Extract logic dari Setoran controllers

**Yang dikerjakan:**
- Buat `app/Services/Setoran/SetoranPerkelompokService.php`
- Buat `app/Services/Setoran/SetoranBedaHariService.php`
- Slim down kedua controller terkait

**File yang disentuh:**
```
app/Services/Setoran/SetoranPerkelompokService.php  ← [BARU]
app/Services/Setoran/SetoranBedaHariService.php     ← [BARU]
app/Http/Controllers/SetoranPerkelompokController.php
app/Http/Controllers/SetoranBedaHariController.php
```

---

### ✅ Sesi 2.4 — Service Layer: RealisasiService
**Status:** ✅ SELESAI + 1 perbaikan keamanan wajib + 3 bug pre-existing ditemukan & diperbaiki (2026-08-20).

Logika `realisasiMusyarokah()` dan `updateStatus()` dipindah utuh ke `RealisasiMusyarokahService`/`RealisasiMurabahahService`.

**Perbaikan keamanan (wajib, bukan opsional):** `RealisasiMusyarokahController` punya SQL injection nyata — `DB::statement("delete from pembiayaan where cif = '$value'")` dan statement INSERT-SELECT sesudahnya, dengan `$value` (dari input `ids[]` milik user, cuma divalidasi `required|array`) disisipkan langsung ke SQL mentah. Diganti parameter binding (`?`).

**Bug pre-existing ditemukan (pola sama seperti Sesi 2.2/2.3, tabel `simpanan`):**
1. Insert ke `simpanan` di kedua service pakai `'debet'` (harusnya `'debit'`, sesuai nama kolom asli) dan set `'reff'` manual (padahal `REFF` di tabel live adalah `bigint auto_increment`). RealisasiMusyarokah juga sempat set `created_at`/`updated_at` padahal tabel `simpanan` tidak punya kolom itu. Semua diperbaiki mengikuti pola Sesi 2.2.
2. `RealisasiMusyarokahService`: `tanggal_posting` di `tabel_transaksi` (varchar 12) diisi tanggal+jam lengkap → selalu gagal SQL. Diperbaiki dengan variabel `$tgl_posting` terpisah (tanggal saja), `$tgl_system` (tanggal+jam) tetap dipakai untuk `tanggal_transaksi` yang memang kolom `datetime`.
3. **Ditemukan bug yang jadi tanggung jawab saya sendiri:** `INSERT INTO pembiayaan SELECT * FROM temp_akad_mus` berhenti berfungsi karena migrasi Sesi 1.2/1.3 menambah 4 kolom (`deleted_at`, `created_by`, `updated_by`, `ip_address`) ke `pembiayaan` tapi sengaja tidak ke `temp_akad_mus` — jumlah kolom jadi tidak cocok (42 vs 38). Diperbaiki dengan menyebutkan nama kolom eksplisit di INSERT dan SELECT (bukan `SELECT *`), sekaligus jadi lebih tahan terhadap perubahan skema di masa depan.

**Verifikasi:** kedua service ditest dengan data pembiayaan/akad sungguhan (dipaksa masuk jalur sukses dengan mengubah `pembiayaan.os` sementara di dalam transaksi yang di-rollback). Sebelum fix: keduanya gagal SQL di titik berbeda-beda. Sesudah fix: keduanya sukses (200), dan data (`pembiayaan.os`, jumlah baris `simpanan`/`tabel_transaksi`) dikonfirmasi kembali seperti semula setelah rollback — tidak ada kebocoran data tes.

**Scope:** Extract logic dari Realisasi controllers

**Yang dikerjakan:**
- Buat `app/Services/Realisasi/RealisasiMusyarokahService.php`
- Buat `app/Services/Realisasi/RealisasiMurabahahService.php`
- Slim down controller terkait

**File yang disentuh:**
```
app/Services/Realisasi/RealisasiMusyarokahService.php   ← [BARU]
app/Services/Realisasi/RealisasiMurabahahService.php    ← [BARU]
app/Http/Controllers/RealisasiMusyarokahController.php
app/Http/Controllers/RealisasiMurabahahController.php
```

---

### ✅ Sesi 2.5 — PullDataService (revisi: DRY, bukan Queue)
**Status:** ✅ SELESAI dengan scope revisi (2026-08-20).

> [!NOTE]
> Rencana asli (jadikan job antrian async) **dibatalkan** setelah baca kode aslinya — `PullDataController` ternyata bukan operasi bulk/berat. Tidak ada satu pun panggilan ke server eksternal; setiap request selalu difilter per satu kelompok/CIF (`where('code_kel', ...)` / `where('cif', ...)`), datanya kecil, prosesnya sudah cepat. Panjang 1.135 baris murni karena duplikasi kode (~10 blok nyaris identik untuk kombinasi jenisPull kelompok/individu × 6 jenis transaksi). Opsi diajukan ke user, dipilih: rapikan jadi Service (DRY), bukan queue.

**Yang dikerjakan:**
- `PullDataService::pull()` — 1 method routing + beberapa helper privat (`kelompok()`, `individuLima()`, `individuBulat()`, `individuSimpanan()`, `individuPenarikan()`, `buildRecord()`) menggantikan ~1.050 baris switch/if-else berulang.
- `PullDataController::data()` — dari ~1.050 baris jadi 8 baris.

**3 bug pre-existing ditemukan & diperbaiki (bukan cuma duplikasi, ini bikin salah/selalu gagal):**
1. Query individu (jenisPull=02, transaksi selain 'lima') select `pembiayaan.nama_kel` — kolom itu **tidak ada** di tabel `pembiayaan` (hasil JOIN ke `kelompok`) → selalu SQL error "Unknown column". Diganti `kelompok.nama_kel`.
2. `SUM(debet)` pada agregasi saldo tabel `simpanan` (jalur pelunasan & penarikan individu) — kolom aslinya `DEBIT` (pola sama seperti Sesi 2.2/2.3/2.4) → diganti `SUM(debit)`.
3. Variabel `$os` dan `$nominal` dipakai tanpa pernah didefinisikan (harusnya `$row->os` dan `$request->nominal`) di jalur pelunasan & penarikan individu → hasil hitung selalu salah (efektif diabaikan, dianggap 0/null).

**Sengaja TIDAK diubah:** teks pesan sukses yang kelihatan salah copy-paste (beberapa jalur individu selalu bilang "Pull Data Lebaran sukses" apa pun jenis transaksinya) — itu kosmetik, bukan bug yang bikin gagal, jadi dipertahankan persis seperti kode asli untuk menghindari menebak maksud pesan yang "benar".

**Verifikasi:** ditest 12 kombinasi (jenisPull 01/02 × 6 jenis transaksi) dengan data pembiayaan/akad sungguhan, dibungkus rollback. Semua 12 sukses (200). Jumlah baris `pull_data` dan semua tabel `tagihan_*` dikonfirmasi tetap 0 setelah test (tidak ada data bocor).

**File yang disentuh:**
```
app/Services/PullData/PullDataService.php         ← [BARU]
app/Http/Controllers/PullDataController.php       ← slim down
```

---

### ✅ Sesi 2.6 — Export Chunked/Streaming (revisi: bukan Queue)
**Status:** ✅ SELESAI dengan scope revisi (2026-08-20), sesuai pola Sesi 2.5 — opsi diajukan ke user, dipilih: export chunked/streaming tetap sinkron (bukan queue), karena tidak ada queue worker jalan di environment ini.

**Premis dikonfirmasi dulu (beda dari Sesi 2.5):** `tabel_transaksi` punya 21,8 juta baris; akun COA tersibuk (`2101000`) sendirian punya **6,68 juta baris**. `BukuBesarController::download()` dengan opsi "semua data" akan coba `->get()` semua itu sekaligus — nyata berisiko crash memori.

**Yang dikerjakan:**
- `BukuBesarStreamSheetExport.php` — dilengkapi (sebelumnya sudah ada tapi tidak dipakai, dan belum hitung kolom Saldo berjalan). Sekarang tiap sheet cuma `->get()` maksimal `chunkSize` baris (default 150.000), bukan seluruh tabel.
- `BukuBesarMultiSheetExport.php` — dari terima Collection yang sudah di-fetch (masalahnya), jadi terima parameter query + hitung jumlah sheet dari `count()` (bukan `get()`). Saldo berjalan **dibawa nyambung antar sheet** lewat query agregat `SUM(debet)/SUM(kredit)` per chunk (di sisi database, tidak menarik baris ke PHP).
- `BukuBesarController::download()` — hapus `$query->get()` yang jadi sumber crash, kirim parameter filter langsung ke Export.
- `ListJurnalExport.php` — dari `FromCollection` (eager `->get()`) ke `FromQuery` + `WithChunkReading`. Total debet/kredit di baris akhir dihitung lewat query `SUM()` terpisah, bukan akumulasi saat fetch.
- `ReportPpapController`/`PpapExport` — **sengaja tidak disentuh**. Datanya sudah dibatasi ke pembiayaan bermasalah saja (`havingRaw('total_tunggakan > 0')`), realistis cuma ratusan baris per unit, bukan risiko crash yang sama.

**Temuan teknis penting saat implementasi:** Maatwebsite Excel's `FromQuery` **mengabaikan `offset()`/`limit()` manual** — package ini menerapkan pagination-nya sendiri, jadi kalau dipaksa gabung dengan offset manual (untuk fitur pecah-per-sheet), setiap sheet malah balik baca dari baris pertama lagi (sudah dites & konfirmasi bug ini sebelum akhirnya diperbaiki). Solusinya: `BukuBesarStreamSheetExport` pakai `FromCollection` dengan query offset/limit dijalankan manual di `collection()` — menghindari auto-pagination Maatwebsite sepenuhnya. `ListJurnalExport` aman pakai `FromQuery`+`WithChunkReading` apa adanya karena tidak butuh offset manual (cuma 1 query berkelanjutan, tidak dipecah sheet).

**Verifikasi:**
- BukuBesar: dites dengan `chunkSize` dipaksa kecil (50) untuk memaksa 4 sheet dari 154 baris — saldo baris terakhir sheet terakhir dibandingkan dengan hitungan manual penuh (ground truth), **cocok persis**. Dites juga lewat controller sungguhan dengan `chunkSize` default: 0,1 detik, 62MB memori.
- ListJurnal: dites dengan data sungguhan 45.406 baris (1 bulan, unit sibuk) — baris Total di file cocok persis dengan `SUM()` database.

> [!WARNING]
> **Koreksi setelah test lebih lanjut:** klaim "tidak akan crash" di atas ternyata **terlalu optimis**. Dites dengan data sungguhan 452.513 baris (unit tersibuk, 1 tahun penuh) — prosesnya **crash sungguhan** (proses PHP mati, exit code 255, tidak sempat tercatat di log Laravel = bukan graceful error, kehabisan memori sistem). Root cause: chunked reading cuma mengatasi sisi "tarik data dari database", tapi PhpSpreadsheet **tetap menyimpan representasi SEMUA sel di memori** sebelum menulis file akhir — dipecah jadi berapa pun sheet, total memorinya tetap menumpuk sama. RAM mesin cuma tersisa ~4GB bebas saat test.

**Solusi final (diskusi dengan user): CSV streaming murni, bukan cuma pembatas.** User bertanya kenapa tidak sekalian pakai chunk+job untuk bulk Excel — jawabannya: job/queue TIDAK menyelesaikan masalah memori ini, cuma memindahkan proses yang sama-sama berat ke worker (yang juga akan crash, cuma bukan proses web yang kena). Akar masalahnya PhpSpreadsheet, bukan soal sinkron/async. Solusi yang benar-benar menghilangkan batasan: tulis CSV langsung pakai `fputcsv()` + `DB::cursor()` (streaming baris-per-baris ke `response()->streamDownload()`), **sama sekali tidak lewat PhpSpreadsheet** — jadi tidak ada objek Spreadsheet yang menumpuk di memori sama sekali, memori tetap datar berapa pun jumlah barisnya.

**Yang dikerjakan:**
- `BukuBesarController::streamCsv()` & `ListJurnalController::streamCsv()` — metode baru, CSV streaming murni.
- `download()`/`export()` — di atas 200.000 baris (`MAKS_BARIS_EXPORT_XLSX`), otomatis dialihkan ke `streamCsv()` alih-alih ditolak. Di bawah itu, tetap `.xlsx` seperti biasa (styling lengkap).
- Saldo berjalan (BukuBesar) & total debet/kredit (ListJurnal) tetap dihitung sama seperti versi Excel — cuma hasilnya `.csv` polos, bukan `.xlsx` berwarna.

**Verifikasi:** kasus yang TADI CRASH (452.513 baris, unit tersibuk, setahun penuh) ditest ulang lewat `streamCsv()` — **selesai 16,9 detik, 174MB memori** (termasuk overhead capture test; produksi sungguhan lebih rendah karena stream langsung ke browser, tidak ditahan di variabel), baris Total di CSV cocok persis dengan `SUM()` database. BukuBesar CSV dites juga (154 baris) — saldo baris terakhir cocok persis dengan hitungan manual.

**Kesimpulan akhir:** user sekarang bisa ambil SEMUA datanya tanpa batasan jumlah baris — export wajar (≤200rb) dapat `.xlsx` berformat lengkap, export ekstrem (jutaan baris, termasuk akun COA tersibuk 6,68 juta baris) otomatis dapat `.csv` yang tetap benar datanya, cuma tanpa styling. Tidak perlu queue/worker sama sekali.

---

### ✅ Sesi 2.7 — Repository Pattern (revisi scope, sesuai duplikasi nyata)
**Status:** ✅ SELESAI dengan scope revisi (2026-08-20).

**Premis dicek dulu (pola yang sama sepanjang Fase 2):** disurvei dulu query mana yang BENAR-BENAR duplikat copy-paste vs yang cuma kebetulan menyentuh tabel yang sama. Hasilnya: `PembiayaanRepository`/`TransaksiRepository` (sesuai rencana lama) **dibatalkan** — query pembiayaan/transaksi di seluruh app sangat spesifik per-fitur (beda join, beda filter bisnis), memaksa jadi repository generik cuma menambah lapisan tanpa manfaat reuse nyata. Yang genuinely duplikat: pola "cari kelompok" (7 controller, 2 nama method beda tapi query identik) dan "cari anggota by CIF" (2 controller).

**Yang dikerjakan:**
- `KelompokRepositoryInterface`/`KelompokRepository::search($term, $unit, $limit)` — konsolidasi dari 7 controller: `SetoranPerkelompokController`, `SetoranBedaHariController`, `PelunasanKelompokController`, `PemindahbukuanPerkelompokController`, `RealisasiMusyarokahController`, `RestKemampuanBayarController`, `SetoranLimaPersenController`.
- `AnggotaRepositoryInterface`/`AnggotaRepository::findByCif($cif)` — konsolidasi dari `InputTransaksiController::getByCif()` dan `KelompokController::getAnggotaByCif()`.
- Dibind di `AppServiceProvider::register()`.

**Bug ditemukan & diperbaiki sekalian:** `SetoranPerkelompokController::cari()` adalah **satu-satunya** dari 7 controller yang TIDAK punya filter `code_unit` — kemungkinan celah data-scoping (user bisa mencari/memilih kelompok dari unit lain, bukan cuma unitnya sendiri). 6 controller lain konsisten punya filter ini. Diperbaiki mengikuti pola mayoritas (ditambahkan filter unit), bukan dipertahankan sebagai "mungkin disengaja" — karena polanya terlalu konsisten di 6 tempat lain untuk dianggap bukan oversight.

**Pattern lain yang DIBIARKAN tidak dikonsolidasi (sengaja):** kombinasi "get kelompok + get anggota dalam kelompok" di 4 controller Setoran/Pelunasan/Pemindahbukuan — skeleton query-nya sama tapi kolom SELECT dan filter bisnis beda-beda di tiap caller (subquery saldo_rek, saldo_pokok/wajib, filter run_tenor). Memaksa ini jadi satu repository method berisiko mengubah perilaku salah satu dari 4 alur bisnis yang berbeda — tidak sepadan dengan manfaatnya.

**Verifikasi:** binding container dikonfirmasi resolve dengan benar. Semua 9 endpoint (7 kelompok + 2 anggota) ditest lewat controller sungguhan dengan data nyata — hasil cocok dengan ground truth (query manual langsung), termasuk untuk `SetoranPerkelompokController` yang perilakunya sekarang berubah (kena filter unit) sesuai perbaikan bug di atas.

**File yang disentuh:**
```
app/Repositories/Contracts/KelompokRepositoryInterface.php  ← [BARU]
app/Repositories/Eloquent/KelompokRepository.php            ← [BARU]
app/Repositories/Contracts/AnggotaRepositoryInterface.php   ← [BARU]
app/Repositories/Eloquent/AnggotaRepository.php             ← [BARU]
app/Providers/AppServiceProvider.php                        ← bind repo
app/Http/Controllers/SetoranPerkelompokController.php       ← + fix bug unit filter
app/Http/Controllers/SetoranBedaHariController.php
app/Http/Controllers/PelunasanKelompokController.php
app/Http/Controllers/PemindahbukuanPerkelompokController.php
app/Http/Controllers/RealisasiMusyarokahController.php
app/Http/Controllers/RestKemampuanBayarController.php
app/Http/Controllers/SetoranLimaPersenController.php
app/Http/Controllers/InputTransaksiController.php
app/Http/Controllers/KelompokController.php
```

---

## 🧪 FASE 3 — Quality & Monitoring
> **Target:** Observability, testing, dan dokumentasi. ~5 sesi.

---

### ✅ Sesi 3.1 — Activity Log (Spatie Activitylog)
**Status:** ✅ SELESAI (2026-08-20).

**Yang dikerjakan:**
- Install `spatie/laravel-activitylog` (dicek dulu tidak ada tabrakan tabel `activity_log`, aman).
- Pasang trait `LogsActivity` ke `Anggota`, `pembiayaan`, `simpanan` — masing-masing log field kunci (`status/unit/kode_kel/cao`, `os/status/run_tenor/ke`, `debit/kredit/type`), `logOnlyDirty()`.
- `ActivityLogController` + `resources/views/admin/activity_log/index.blade.php` — listing berpaginasi, filter opsional by subject/log_name, tampilkan causer & detail before/after.
- Route `/admin/activity-log` (`role:1`) + entri menu sidebar baru "Activity Log".

> [!IMPORTANT]
> **Batasan cakupan yang perlu dipahami:** `LogsActivity` cuma menangkap perubahan yang lewat **Eloquent** (`::create()`, `->save()`, `->update()`, `->delete()`). Sepanjang sesi-sesi sebelumnya kita temukan mayoritas transaksi keuangan di aplikasi ini (setoran, realisasi, pull data, dll) ditulis lewat `DB::table()->insert()/update()` mentah — itu **tidak tercatat** di activity log ini. Activity log ini akan menangkap perubahan lewat form CRUD (Tambah/Edit Anggota, dst.) tapi bukan sebagian besar arus transaksi. Sudah dicatat sebagai peringatan langsung di halaman index-nya juga.

**Verifikasi:** ditest ubah status Anggota lewat Eloquent (dibungkus rollback) — tercatat benar (`old: ANGGOTA` → `attributes: KELUAR`), causer terisi benar ("Admin User"). Halaman index dirender, HTML 36KB, judul & data tampil benar.

**File yang disentuh:**
```
app/Models/pembiayaan.php, Anggota.php, simpanan.php     ← pasang trait
app/Http/Controllers/ActivityLogController.php            ← [BARU]
resources/views/admin/activity_log/index.blade.php        ← [BARU]
routes/web.php                                             ← tambah route
database/seeders/MenuSeeder.php                            ← tambah menu (untuk fresh install)
```

---

### ✅ Sesi 3.2 — Unit Tests: Helpers & Services
**Status:** ✅ SELESAI (2026-08-20) — plus 1 temuan besar & 1 bug baru.

> [!CAUTION]
> **Keputusan infrastruktur test:** aplikasi ini belum punya database test terpisah (tidak ada `.env.testing`, baris sqlite di `phpunit.xml` dikomentari) — artinya `php artisan test` jalan terhadap **database MySQL development yang sungguhan** (yang sama dipakai sehari-hari, 30 juta+ baris). Semua test di Sesi 3.2 (dan seterusnya di Fase 3) SENGAJA pakai `DatabaseTransactions`, **BUKAN** `RefreshDatabase` — `RefreshDatabase` akan menjalankan ulang migrasi yang TIDAK merepresentasikan skema tabel live sebenarnya (sudah berkali-kali terbukti menyimpang, lihat Sesi 2.2/2.4/2.5/3.2 ini sendiri). Siapa pun yang menambah test baru ke aplikasi ini **wajib** pakai `DatabaseTransactions`, jangan `RefreshDatabase`, kecuali database test terpisah benar-benar disiapkan dulu.

> [!WARNING]
> **Temuan besar saat menulis test:** tabel `simpanan` **berubah skema lagi** sejak Sesi 2.2 — kolomnya sekarang `debet` (ejaan benar, huruf kecil, sesuai kode asli), BUKAN `DEBIT` (huruf besar) seperti waktu Sesi 2.2/2.4/2.5 diperbaiki. Kemungkinan besar ini perbaikan tabel serupa kasus `anggota` sebelumnya, tapi belum sempat diberitahukan. Semua perbaikan `'debet'`→`'debit'` di `TransaksiService`, `RealisasiMusyarokahService`, `RealisasiMurabahahService`, `PullDataService` (dan `simpanan.php` model dari Sesi 3.1) **dikembalikan** ke `'debet'`. Kolom `reff` (bigint auto-increment) dan ketiadaan `created_at`/`updated_at` **tetap sama seperti sebelumnya** — cuma bagian ejaan debet/debit yang berubah.

> [!WARNING]
> **Bug baru ditemukan (murni baru, bukan efek skema berubah):** model `simpanan` tidak pernah mendeklarasikan `$primaryKey`, jadi Eloquent defaultnya menganggap PK-nya `id` — padahal PK sebenarnya `reff`. Setiap kali `simpanan::create()` dipanggil, Eloquent otomatis coba `SELECT * FROM simpanan WHERE id = ...` untuk refresh model setelah insert → selalu gagal SQL "Unknown column 'id'". Ini baru ketahuan sekarang karena sebelumnya proses selalu gagal duluan di step "Unknown column 'debet'/'debit'" (jadi tidak pernah sampai ke step refresh ini). Diperbaiki: tambah `protected $primaryKey = 'reff';` ke `app/Models/simpanan.php`.

**Yang dikerjakan:**
- `tests/Unit/Helpers/TerbilangTest.php` — 22 test kasus (nilai dasar, belasan, puluhan, ratusan, ribuan, jutaan, nilai negatif), pakai nilai ground-truth dari fungsi asli (bukan tebakan manual).
- `tests/Unit/Services/TransaksiServiceTest.php` — 10 test, cover semua 5 jenis_transaksi + 4 sub-kasus pemindahbukuan + kasus CIF tidak ditemukan.
- `tests/Unit/Services/SetoranServiceTest.php` — 4 test, cover SetoranPerkelompokService & SetoranBedaHariService (sukses + 2 kasus error).
- Perbaikan reaktif dari temuan di atas: `TransaksiService.php`, `RealisasiMusyarokahService.php`, `RealisasiMurabahahService.php`, `PullDataService.php`, `app/Models/simpanan.php` (revert debet + fix primaryKey).

**Temuan lain (dicatat, tidak diperbaiki — di luar scope "tulis test"):** `terbilang()` di `app/Support/helpers.php` menghasilkan PHP 8.3 deprecation warning ("Implicit conversion from float to int") karena pembagian (`$angka / 10` dst.) tidak di-cast ke int sebelum dipakai sebagai index array/parameter rekursif. Tidak mengubah hasil hitungan saat ini, tapi berpotensi jadi error fatal di versi PHP mendatang.

**Verifikasi:** `php artisan test --filter="TerbilangTest|TransaksiServiceTest|SetoranServiceTest"` → 36 test, semua pass. Service Realisasi & PullData (dari sesi sebelumnya, ikut kena revert) dites ulang manual — tidak ada regresi.

---

### ✅ Sesi 3.3 — Feature Tests: Auth & Dashboard
**Status:** ✅ SELESAI (2026-08-20) — plus 1 bug ditemukan & diperbaiki, 1 deviasi dari rencana.

> [!NOTE]
> **Deviasi dari rencana:** item "Setup test database (`DB_CONNECTION=sqlite`)" **tidak dikerjakan**. Sebelum mulai, ditemukan bahwa `RealisasiMusyarokahService.php` pakai syntax SQL MySQL-only (`DATE_ADD(tgl_wakalah, INTERVAL 7 DAY)`) yang tidak jalan di SQLite — jika dipaksakan, Feature test untuk Realisasi di Sesi 3.4 akan gagal karena beda dialek SQL, bukan karena bug sungguhan. Ditanyakan ke user, dipilih: **tetap pakai database MySQL development + `DatabaseTransactions`**, sama seperti pola Sesi 3.2 — konsisten, tidak perlu ubah kode produksi demi kebutuhan testing.

> [!WARNING]
> **Bug ditemukan & diperbaiki:** `RoleMiddleware::handle()` melakukan `redirect('/redirect')` (GET) saat user dengan role salah mengakses route yang dibatasi `role:X`. Tapi route `/redirect` di `routes/web.php` cuma didaftarkan sebagai `Route::post()`, tidak ada `Route::get()`. Akibatnya: user dengan role salah yang mengakses route admin-only selalu berakhir di **405 Method Not Allowed**, bukan diarahkan ke dashboard mereka sendiri seperti maksud aslinya. Tidak ada pemakaian JS/AJAX ke endpoint ini (dicek di `resources/`), jadi POST-nya kemungkinan cuma sisa/tidak terpakai. **Fix:** tambah `Route::get('/redirect', [RedirectController::class, 'check']);` di samping `Route::post()` yang sudah ada (`routes/web.php`). Ditemukan lewat test `AdminDashboardTest::test_dashboard_admin_menolak_role_bukan_admin` (gagal duluan dengan 405 sebelum fix).

**Yang dikerjakan:**
- `tests/Feature/Auth/LoginTest.php` — 8 test: halaman login (guest), login sukses redirect sesuai 4 role (pakai user seed asli `admin@ni`/`al@ni`/`ah@ni`/`kp@ni`, password ditimpa sementara di dalam transaksi test lalu di-rollback), login gagal (password salah & email tidak terdaftar), logout.
- `tests/Feature/Dashboard/AdminDashboardTest.php` — 3 test: dashboard bisa diakses role admin (cek data view lengkap), guest ditolak (redirect ke halaman login), role bukan admin ditolak (redirect ke `/redirect` lalu diteruskan ke `/al`).
- `routes/web.php` — fix bug di atas.

**Catatan teknis:** tabel `users` punya kolom NOT NULL tanpa default di luar skema Laravel standar (mis. `param_tanggal`) — percobaan awal bikin user baru dari nol untuk test role gagal karena ini. Diganti pakai user seed asli yang sudah ada (4 role sudah tersedia by default), cuma password-nya ditimpa sementara di dalam transaksi test.

**Verifikasi:** `php artisan test` → 49 test, 136 assertion, semua pass (termasuk semua test dari Sesi 3.2 sebelumnya — tidak ada regresi).

---

### ✅ Sesi 3.4 — Feature Tests: Transaksi Kritis
**Status:** ✅ SELESAI (2026-08-20)

> [!NOTE]
> **Catatan teknis — Accept header untuk FormRequest:** semua route transaksi ini pakai `$.ajax` (jQuery) di sisi frontend, yang otomatis kirim header `X-Requested-With: XMLHttpRequest` → Laravel mengembalikan JSON 422 saat validasi gagal (`expectsJson()` true). Test yang menguji jalur validasi gagal SENGAJA pakai `postJson()` (bukan `post()`) supaya perilaku HTTP request-nya cocok dengan AJAX asli, bukan form submission biasa.

**Yang dikerjakan:**
- `tests/Feature/Transaksi/InputTransaksiTest.php` — 6 test: halaman index, get-by-cif (ditemukan/tidak), store penarikan tunai berhasil, store gagal validasi cif tidak ada (422), get history.
- `tests/Feature/Transaksi/SetoranPerkelompokTest.php` — 5 test: halaman index, filter kelompok (ditemukan/tidak ditemukan), proses setoran berhasil, proses setoran tanpa anggota dipilih (400). Sample data dicari dinamis (kelompok dengan pembiayaan aktif `run_tenor < tenor`), sama seperti pola `SetoranServiceTest` di Sesi 3.2.
- `tests/Feature/Realisasi/RealisasiMusyarokahTest.php` — 3 test: halaman index, proses realisasi berhasil (pakai trik yang sama dari verifikasi manual Sesi 3.2: paksa `pembiayaan.os = 0` sementara di dalam transaksi test supaya jalur sukses beneran teruji, bukan cuma jalur skip), validasi `ids` kosong (422).

**Verifikasi:** `php artisan test` → 63 test, 191 assertion, semua pass (termasuk semua test dari Sesi 3.1-3.3 sebelumnya — tidak ada regresi).

---

### ✅ Sesi 3.5 — Laravel Telescope + Dokumentasi API
**Status:** ✅ SELESAI (2026-08-20) — plus 1 perbaikan penting di setup Telescope, 1 temuan keamanan dicatat (tidak diperbaiki).

> [!WARNING]
> **Perbaikan wajib di setup Telescope:** `php artisan telescope:install` secara default mendaftarkan `TelescopeServiceProvider` langsung di `bootstrap/providers.php` — tanpa syarat, berlaku di semua environment. Karena `laravel/telescope` di-install sebagai `require-dev`, kalau nanti production deploy pakai `composer install --no-dev`, class package-nya tidak ke-load tapi provider tetap dipanggil → **fatal error di semua request**, bukan cuma Telescope-nya yang hilang. Diperbaiki sesuai rekomendasi resmi Laravel: baris registrasi provider dihapus dari `bootstrap/providers.php`, diganti registrasi kondisional di `AppServiceProvider::register()` — hanya jalan kalau `app()->environment('local')` **dan** class package-nya memang ada (`class_exists()` guard, jaga-jaga kalau suatu saat `--no-dev` dipakai juga di local). Diverifikasi: `php artisan route:list --name=telescope` nemu route saat jalan normal (APP_ENV=local dari `.env`), tapi 404 saat dites lewat `php artisan test` (APP_ENV=testing di `phpunit.xml`) — bukti Telescope memang cuma aktif di local, sesuai maksud "dev only".

> [!CAUTION]
> **Temuan keamanan — dicatat, TIDAK diperbaiki (di luar scope sesi ini):** `config/database.php` koneksi `'cs'` (ke database eksternal `mobcol`, dipakai `PullDataService`) punya kredensial **hardcoded sebagai default value** kalau env var kosong: `env('DB_CS_HOST', '185.201.9.210')`, `env('DB_CS_USERNAME', 'adminni')`, `env('DB_CS_PASSWORD', '147Teriyak')` — IP, username, dan password tersimpan polos di file yang ter-track git. Tidak disentuh karena berisiko (kredensial produksi, bukan sekadar bug teknis) dan di luar scope "Telescope + Docs". **Rekomendasi untuk sesi terpisah:** pindahkan nilai default itu ke `.env` yang sudah ada di server (kredensial sudah pasti perlu diisi di sana supaya koneksi jalan), lalu hapus default value dari `config/database.php` (biarkan `env('DB_CS_PASSWORD')` tanpa fallback) — dan pertimbangkan rotasi password karena sudah pernah ada di riwayat git.

**Yang dikerjakan:**
- Install `laravel/telescope` (`composer require --dev`), publish scaffolding, migrasi tabel `telescope_entries` (dicek dulu `Schema::hasTable()` sebelum migrate — tidak ada collision).
- Fix registrasi provider dev-only (lihat di atas).
- `README.md` — ditulis ulang total (sebelumnya masih skeleton default Laravel): deskripsi aplikasi, kebutuhan sistem, langkah instalasi (termasuk 2 koneksi database: `DB_*` utama dan `DB_CS_*` eksternal), cara jalankan test + penjelasan kenapa wajib `DatabaseTransactions`, struktur folder ringkas, pointer ke `artefak/rencana_pengerjaan.md` dan `docs/api.md`.
- `docs/api.md` — dokumentasi endpoint per area fungsional (Auth & Role, Dashboard, Transaksi, Realisasi, Anggota/Kelompok, Pull Data, Jurnal & Posting, Laporan & Cetak), format response standar, pointer ke `php artisan route:list` untuk daftar lengkap (237 route, tidak didokumentasikan satu-satu).
- `.env.example` — diperbarui supaya cocok dengan realita (`DB_CONNECTION` sebelumnya `sqlite` padahal aplikasi jalan di MySQL; ditambah `DB_CS_*` dan `SANCTUM_STATEFUL_DOMAINS` yang sebelumnya tidak ada contohnya sama sekali).

**File yang disentuh:**
```
docs/api.md                                              ← [BARU]
README.md                                                ← ditulis ulang
.env.example                                              ← update (DB_CONNECTION, DB_CS_*, SANCTUM_STATEFUL_DOMAINS)
config/telescope.php                                      ← [BARU via artisan]
app/Providers/TelescopeServiceProvider.php                ← [BARU via artisan]
app/Providers/AppServiceProvider.php                       ← registrasi kondisional Telescope
bootstrap/providers.php                                    ← revert (Telescope TIDAK didaftarkan di sini)
database/migrations/2026_08_20_075339_create_telescope_entries_table.php ← [BARU via artisan]
```

**Verifikasi:** `php artisan test` → 63 test, 191 assertion, semua pass (tidak ada regresi dari Telescope/env changes). Route `/telescope` terdaftar saat local, tidak terdaftar saat testing.

---

## ✅ FASE 3 — SELESAI (2026-08-20)
Sesi 3.1 s.d. 3.5 semua selesai. Ringkasan bug yang ditemukan & diperbaiki sepanjang Fase 3:
1. Model `simpanan` tidak punya `$primaryKey` (default salah asumsi `id`, harusnya `reff`) — Sesi 3.2.
2. Skema tabel `simpanan` berubah lagi (`debet` kembali ke huruf kecil) — reactive fix, Sesi 3.2.
3. `RoleMiddleware` redirect ke `/redirect` (GET) padahal route-nya cuma `POST` → 405 — Sesi 3.3.
4. Setup Telescope default rawan fatal error di production (`--no-dev`) — Sesi 3.5.

Temuan yang dicatat tapi sengaja TIDAK diperbaiki (di luar scope, butuh keputusan/sesi terpisah):
- `terbilang()` di `app/Support/helpers.php` — PHP 8.3 deprecation warning (implicit float→int).
- Kredensial database `cs` (mobcol) hardcoded sebagai default di `config/database.php`.

**Selanjutnya:** evaluasi ulang menyeluruh terhadap code pattern enterprise sudah dikerjakan — lihat [`artefak/evaluasi_enterprise.md`](evaluasi_enterprise.md) untuk temuan & prioritas lengkap.

---

## 🆕 FASE 4 — Pengembangan Setelah Evaluasi

### ✅ Sesi 4.1 — Audit Trail Akses User (Login → Akses Menu → Logout) untuk KPI
**Status:** ✅ SELESAI (2026-08-20)
**Latar belakang:** permintaan user di luar rencana awal — catat proses akses tiap user dari login, tiap request yang dilakukan (termasuk AJAX/POST, bukan cuma buka halaman menu), sampai logout, disimpan ke database untuk keperluan evaluasi & KPI.

**Keputusan desain (dikonfirmasi ke user lewat pertanyaan sebelum implementasi):**
- **Storage:** reuse tabel `activity_log` (Spatie) yang sudah ada dari Sesi 3.1, bukan tabel baru — semua entri baru pakai `log_name = 'akses'` (terpisah dari `log_name = 'default'` milik audit trail perubahan data Anggota/Pembiayaan/Simpanan), dibedakan lagi lewat kolom `event` (`login` / `akses` / `logout`).
- **Granularitas:** SEMUA request dari user yang sudah login (bukan cuma buka halaman menu) — termasuk AJAX/POST di dalam halaman.
- **Retensi:** dibersihkan otomatis tiap 3 bulan (quarterly), menyisakan 2 bulan terakhir — pakai command bawaan Spatie `activitylog:clean` yang mendukung filter per `log_name`, supaya pembersihan HANYA menghapus `log_name = 'akses'` dan tidak menyentuh audit trail perubahan data yang harus disimpan lebih lama.

**Yang dikerjakan:**
- `app/Http/Middleware/LogUserAccess.php` — middleware terminable (jalan di `terminate()`, tidak menambah latency response), dipasang global di grup `web` (`bootstrap/app.php`). Untuk tiap request dari user yang sudah login, catat 1 baris `activity_log`: method, path, status HTTP, IP, user agent, session ID. Skip request ke `/telescope/*` biar tidak ikut ke-log kalau ada dev yang buka Telescope. Request dari guest (belum login) otomatis tidak tercatat.
- `app/Providers/AppServiceProvider.php` — listener untuk `Illuminate\Auth\Events\Login` dan `...\Logout` (event bawaan Laravel yang otomatis terpicu oleh `auth()->attempt()`/`auth()->logout()` yang sudah dipakai `AuthController`, tidak perlu ubah `AuthController` sama sekali), masing-masing catat 1 baris `activity_log` dengan `event` sesuai.
- `routes/console.php` — jadwal `Schedule::command('activitylog:clean akses --days=60 --force')->quarterly()`.
- `app/Http/Controllers/ActivityLogController.php` + `resources/views/admin/activity_log/index.blade.php` — tambah filter `event` (selain `log_name` yang sudah ada sebelumnya) dan dropdown filter di UI (sebelumnya cuma bisa lewat query param manual), plus perjelas teks keterangan halaman soal 2 jenis log yang sekarang tercampur di tabel yang sama.
- `tests/Feature/ActivityLog/UserAccessLogTest.php` — 5 test: login tercatat, request terautentikasi tercatat sebagai `akses` (termasuk path & method yang benar), logout tercatat, halaman activity log bisa difilter per `event`, request tanpa login TIDAK tercatat (privasi guest terjaga).

**Catatan konsekuensi:** karena mencatat SEMUA request (bukan cuma buka menu), volume `activity_log` akan tumbuh cepat untuk user aktif — inilah alasan retensi 2 bulan + cleanup quarterly di atas. Command cleanup butuh scheduler jalan di server (`php artisan schedule:work` atau cron `* * * * * php artisan schedule:run`) — perlu dipastikan sudah terpasang di server produksi, di luar cakupan kerja lewat kode.

**Verifikasi:** `php artisan test` → 68 test, 200 assertion, semua pass (tidak ada regresi dari Fase 1-3 sebelumnya). `php artisan schedule:list` mengonfirmasi jadwal cleanup terdaftar & jatuh tempo sesuai (quarterly).

---

### ✅ Sesi 4.2 — Perbaikan Anggota (cao_promotor, kelamin, bug `=`/`==`) & Select2 Kelompok di Master Pembiayaan
**Status:** ✅ SELESAI (2026-08-21)

**Bagian 1 — laporan "input anggota gagal":** lihat detail lengkap di catatan `[!NOTE]` pada bagian "TEMUAN AD-HOC — Modul Anggota" di atas. Ringkas: `AnggotaController::store()` gagal 100% karena kolom `cao_promotor` (NOT NULL, tanpa default) tidak diisi — diperbaiki, plus 2 bug tambahan ditemukan di file yang sama (hardcode `kelamin` selalu 'P', dan 11 kondisi `=` vs `==` di `form-edit.blade.php` yang bikin dropdown Status Perkawinan/Agama/Pendidikan selalu tampil opsi terakhir apapun data aslinya).

**Bagian 2 — permintaan user: pencarian kelompok di Master Pembiayaan belum pakai select2.** Halaman `admin/master_pembiayaan/index.blade.php` (dipakai untuk mencari anggota per kelompok sebelum proses tambah/edit pembiayaan) sebelumnya cuma punya `<input type="text">` polos untuk "Kode Kelompok" — user harus tahu & ketik persis kode kelompoknya, tanpa bantuan pencarian.

**Yang dikerjakan:**
- `app/Repositories/Contracts/KelompokRepositoryInterface.php` / `KelompokRepository::search()` — **reuse langsung**, tidak bikin query baru (repository ini sudah dikonsolidasi dari 7 controller sejak Sesi 2.7, dipakai juga oleh `SetoranPerkelompokController`, `PelunasanKelompokController`, dll).
- `app/Http/Controllers/PembiayaanController.php` — inject `KelompokRepositoryInterface` via constructor, tambah method `cariKelompok(Request $request)`.
- `routes/web.php` — `GET /pembiayaan/cari-kelompok` (nama route `pembiayaan.cariKelompok`), di dalam grup `role:1` yang sama dengan route pembiayaan lain.
- `resources/views/admin/master_pembiayaan/index.blade.php` — ganti `<input type="text" id="kode_kelompok">` jadi `<select class="select2-ajax">`, inisialisasi select2 dengan AJAX search (pola & konfigurasi disalin persis dari `pelunasan_kelompok/index.blade.php` yang sudah established: cari lewat `code_kel` ATAU `nama_kel`, tampil `"KODE - Nama Kelompok"`). CSS/JS select2 sudah dimuat global lewat `layouts/main.blade.php`, tidak perlu load ulang.
- `tests/Feature/Anggota/AnggotaStoreTest.php` — tambah 2 test (kelamin tersimpan sesuai input, validasi gagal kalau kelamin kosong).
- `tests/Feature/Pembiayaan/PembiayaanCariKelompokTest.php` — 3 test baru: halaman master pembiayaan bisa diakses, cari kelompok berdasar kode, cari kelompok berdasar (potongan) nama.

**Verifikasi:** `php artisan test` → 74 test, 272 assertion, semua pass (tidak ada regresi).

---

### ✅ Sesi 4.3 — Perbaikan Menyeluruh Kolom `reff` pada `simpanan`/`simpanan_pokok`/`simpanan_wajib`
**Status:** ✅ SELESAI (2026-08-22)
**Latar belakang:** user meminta audit kolom `reff` (dipakai format `unit + date('YmdHis') + 2 huruf acak` di banyak tempat) — apakah berisiko data tidak terinput/redundan/bug lain.

> [!WARNING]
> **Temuan audit (sebelum perbaikan):**
> 1. **Skema `reff` berubah lagi** (drift ke-3 untuk tabel `simpanan` di sesi ini): dulu (Sesi 3.2) `bigint auto_increment`, sekarang `varchar(191)` PRIMARY KEY **tanpa** auto_increment, tanpa default. `simpanan_pokok`/`simpanan_wajib` sama-sama `varchar(191)` (konsisten, tidak berubah).
> 2. **Bug aktif — gagal 100%:** 8 lokasi insert (`TransaksiService.php` ×6, `RealisasiMusyarokahService.php`, `RealisasiMurabahahService.php`) sama sekali tidak mengisi `reff` — sisa dari perbaikan Sesi 3.2 saat kolom itu masih dianggap auto_increment. Dibuktikan: `SQLSTATE[HY000]: 1364 Field 'reff' doesn't have a default value`. Terkonfirmasi lewat regresi nyata: `TransaksiServiceTest` yang tadinya lolos 10/10 turun jadi 4/10 di tengah sesi (skema berubah saat sesi kerja berlangsung).
> 3. **Risiko tabrakan primary key** di 20+ lokasi lain yang pakai format lama (`unit+YmdHis+2 huruf acak`, ruang hanya 1.296 kombinasi per detik). Simulasi: dari 1000 percobaan skenario "kelompok 25 anggota diproses dalam detik yang sama", 236 kali (23,6%) mengalami minimal 1 tabrakan. Dibuktikan nyata: `SQLSTATE[23000]: 1062 Duplicate entry ... for key 'simpanan.PRIMARY'`. Karena proses ini dibungkus 1 transaksi DB, 1 tabrakan membatalkan seluruh batch, bukan cuma 1 anggota.
> 4. **Bonus temuan saat verifikasi:** model `simpanan.php` masih pakai trait `SoftDeletes` + `HasAuditTrail` (Sesi 1.2/1.3) yang butuh kolom `deleted_at`/`created_by`/`updated_by`/`ip_address` — kolom-kolom itu **sudah tidak ada** lagi di tabel `simpanan` (drift yang sama). `simpanan_pokok`/`simpanan_wajib` tidak kena, kolomnya masih lengkap di sana.

**Solusi yang disepakati dengan user:** reff harus tetap **sortable per unit dulu, baru kronologis** (bukan cuma "sortable" generik) — jadi bukan auto_increment maupun UUID acak biasa.

**Yang dikerjakan:**
- `app/Support/helpers.php` — fungsi baru `generate_reff(string $unit): string` = `str_pad($unit, 4, '0', STR_PAD_LEFT) . Str::ulid()`. ULID (26 karakter, sortable leksikografis = kronologis presisi milidetik, native Laravel) dipilih di atas auto_increment (butuh counter terpusat, tidak cocok untuk generate di level aplikasi) dan UUID v4 acak biasa (tidak sortable, index B-tree MySQL jadi terfragmentasi untuk tabel besar seperti `simpanan` yang 30 juta+ baris — insert baru dengan key acak "nyempil" di tengah struktur, bukan selalu di ujung).
- **8 lokasi yang tidak punya `reff` sama sekali** — ditambahkan: `TransaksiService.php` (6×, jenis_transaksi 2/3/4), `RealisasiMusyarokahService.php` (1×, bulk insert 2 baris), `RealisasiMurabahahService.php` (1×, bulk insert 2 baris).
- **20+ lokasi format lama (`unit+timestamp+random`)** diganti `generate_reff($unit)`: `TransaksiService.php`, `SetoranPerkelompokService.php`, `SetoranBedaHariService.php`, `PemindahbukuanPerkelompokController.php`, `RealisasiTagihanKelompokController.php`, `PelunasanKelompokController.php`, `PelunasanController.php`, `HapusBukuController.php` (pola beda, `'WO-'.Str::random(10)`, diseragamkan juga), `SetoranLimaPersenController.php` (pola beda & lebih buruk — pakai `COUNT(*)` tabel sebagai "nomor urut", rawan race condition DAN lambat untuk tabel besar; dihapus, diganti `generate_reff()`).
- `app/Models/simpanan.php` — lepas `SoftDeletes` + `HasAuditTrail` (kolom pendukungnya sudah tidak ada di skema live), `simpanan_pokok`/`simpanan_wajib` tidak disentuh (kolomnya masih lengkap).
- `tests/Unit/Helpers/GenerateReffTest.php` — 5 test baru: format (4+26 karakter), unit dipad benar, 50 panggilan berturut-turut semua unik, sortable kronologis untuk unit sama, sortable per-unit-dulu untuk unit beda.
- File yang **tidak** punya test coverage (`PemindahbukuanPerkelompokController`, `RealisasiTagihanKelompokController`, `PelunasanKelompokController`, `PelunasanController`, `HapusBukuController`, `SetoranLimaPersenController`) diverifikasi manual (baca ulang tiap lokasi, cocokkan jumlah `insert(`/`::create(` vs jumlah `'reff' =>`) + `php -l` untuk memastikan tidak ada syntax error — bukan reproduksi database penuh (butuh data pembiayaan/kelompok valid yang setup-nya mahal untuk tiap kontroler), karena perubahannya murni substitusi 1 ekspresi generator, bukan restrukturisasi logika.

**Verifikasi:** `php artisan test` → 79 test, 279 assertion, semua pass (termasuk regresi yang sempat muncul di tengah sesi karena skema berubah live — sudah diperbaiki & dikonfirmasi ulang).

---

### ✅ Sesi 4.4 — Sweep Lanjutan: Pastikan Semua Titik Insert `simpanan`/`simpanan_pokok`/`simpanan_wajib` Terisi `reff`
**Status:** ✅ SELESAI (2026-08-22)
**Latar belakang:** permintaan user untuk mengulang pengecekan Sesi 4.3 secara lebih menyeluruh — pastikan tidak ada titik insert ke 3 tabel itu yang terlewat.

**Metodologi:** pencarian pola diperluas (bukan cuma `'reff' =>`, tapi semua bentuk `::create(`, `::insert(`, `DB::table('simpanan...')->insert(`, `DB::insert(`/`DB::statement(` dengan `INSERT INTO simpanan`), mencakup seluruh `app/` **dan** `database/seeders/` — bukan cuma folder yang sudah disentuh Sesi 4.3.

**Hasil:**
- Semua titik insert yang sudah diperbaiki Sesi 4.3 (`TransaksiService`, `SetoranPerkelompokService`, `SetoranBedaHariService`, `RealisasiMusyarokahService`, `RealisasiMurabahahService`, `PemindahbukuanPerkelompokController`, `RealisasiTagihanKelompokController`, `PelunasanKelompokController`, `PelunasanController`, `HapusBukuController`, `SetoranLimaPersenController`) dikonfirmasi ulang — tidak ada yang terlewat.
- File lain yang menyebut `simpanan`/`simpanan_pokok`/`simpanan_wajib` (`PullDataService`, `RestrukturisasiByKelompokController`, seluruh folder `Exports/`, `ReportNominativeSimpananController`, `InputTransaksiController::getHistory()`, dll) diperiksa satu-satu — **semua murni `SELECT`/agregat untuk laporan atau perhitungan saldo**, tidak ada `INSERT`, jadi tidak perlu disentuh.
- **3 seeder ditemukan dengan bug terpisah** (`database/seeders/simpanan.php`, `simpanan_pokok.php`, `simpanan_wajib.php`): sudah mengisi `reff`, jadi tidak gagal — tapi nilainya **hardcode statis** (`'REF001'`, `'REF002'`). Efeknya: `php artisan db:seed` yang dijalankan dua kali akan gagal `Duplicate entry` di run kedua, karena reff-nya selalu sama persis. Diperbaiki jadi `generate_reff('001')`, konsisten dengan seluruh aplikasi. Diverifikasi: jalankan ketiga seeder lewat transaksi yang di-rollback, jumlah baris `simpanan` bertambah 2→4, semua `reff` baru berformat ULID 30 karakter.

**Verifikasi:** `php artisan test` → 79 test, 279 assertion, semua pass (tidak ada regresi).

---

### ✅ Sesi 4.5 — Select2 Pencarian Kelompok: Realisasi Wakalah & Realisasi Murabahah
**Status:** ✅ SELESAI (2026-08-22)
**Latar belakang:** permintaan user — di menu Realisasi Wakalah dan Realisasi Murabahah, field pencarian kelompok masih `<input type="text">` polos (user harus tahu & ketik persis kode kelompok), belum select2 seperti yang sudah dikerjakan untuk Master Pembiayaan (Sesi 4.2) dan yang sudah ada duluan di Realisasi Musyarokah (referensi pola: `RealisasiMusyarokahController::getSetKelompok()` + `realisasi_musyarokah/index.blade.php`, dipakai sebagai contoh persis untuk 2 halaman ini karena satu keluarga modul Realisasi).

**Yang dikerjakan:**
- `app/Http/Controllers/RealisasiWakalahController.php` — inject `KelompokRepositoryInterface` (reuse, bukan query baru), tambah method `cariKelompok(Request $request)`.
- `app/Http/Controllers/RealisasiMurabahahController.php` — sama, ditambahkan ke constructor yang sudah ada.
- `routes/web.php` — `GET /realisasi_wakalah/cari-kelompok` dan `GET /realisasi/murabahah/cari-kelompok` (nama route `realisasiMurabahah.cariKelompok`), keduanya di grup `role:1` yang sama dengan route Realisasi lain.
- `resources/views/admin/realisasi_wakalah/index.blade.php` dan `realisasi_murabahah/index.blade.php` — ganti `<input type="text">` jadi `<select class="select2bs3">`, inisialisasi select2 (tema `bootstrap3`, cari lewat `code_kel` ATAU `nama_kel`, tampil `"KODE - Nama Kelompok"`) — **dipindah ke `@push('scripts')`** karena script lama ditulis inline langsung di `@section('content')`, yang dieksekusi SEBELUM library `select2.min.js` selesai dimuat oleh `layouts/main.blade.php` (baru dimuat setelah `@yield('content')`) — kalau tidak dipindah, `$(...).select2 is not a function`. Juga diperbaiki: pola reset field (`$('#kodeKelompok').val('')` / `.reset()`) ditambah `.trigger('change')` supaya tampilan select2 ikut ter-refresh, bukan cuma value internalnya.
- `tests/Feature/Realisasi/RealisasiWakalahCariKelompokTest.php` dan `RealisasiMurabahahCariKelompokTest.php` — 3 test masing-masing: halaman bisa diakses (sekaligus membuktikan tidak ada syntax error Blade dari perubahan `@push`/`@endpush`), cari kelompok berdasar kode, cari kelompok berdasar (potongan) nama.

**Verifikasi:** `php artisan test` → 85 test, 301 assertion, semua pass (tidak ada regresi).

---

### ✅ Sesi 4.6 — Investigasi Report PPAP & Proses Update Kolektibilitas (`pembiayaan.gol`)
**Status:** ✅ SELESAI (2026-08-22)
**Latar belakang:** user melaporkan `http://127.0.0.1:8000/report/ppap` "tidak bisa diakses", dan minta dipelajari bagaimana field `gol` (kolektibilitas) seharusnya di-update dari proses PPAP.

> [!NOTE]
> **"Tidak bisa diakses" — bukan bug kode.** Semua endpoint `ReportPpapController` (index, cari, export PDF, export Excel) diuji lewat test HTTP otomatis dan semuanya merespons normal (200), tidak ada jejak error di log aplikasi dari akses browser. Dikonfirmasi ke user: gejalanya "browser tidak bisa connect sama sekali" — ciri khas server lokal (`php artisan serve`) tidak sedang berjalan, bukan error di sisi Laravel. Tidak ada perubahan kode untuk bagian ini.
>
> **Temuan utama — field `gol` tidak pernah di-update oleh proses apa pun.** Ditelusuri ke seluruh `app/`: `pembiayaan.gol` cuma di-*set* SEKALI, hardcode ke `1`, saat pembiayaan dibuat (`PembiayaanController.php`). `ReportPpapController` MENGHITUNG kolektibilitas secara terpisah (real-time dari tabel `tunggakan`, metrik "ft" = Frekuensi Tunggakan) semata-mata untuk ditampilkan di laporan — hasilnya **tidak pernah ditulis balik** ke kolom `gol`. Dampak nyata di luar laporan itu sendiri: `HapusBukuController` mensyaratkan `gol > 3` untuk proses Write-Off kategori NPF — karena `gol` tidak pernah lebih dari 1, **syarat itu selalu gagal, tidak ada pembiayaan yang pernah bisa di-Write-Off lewat jalur NPF**.
>
> **Keputusan bersama user:** (1) proses update `gol` dijalankan sebagai **job terjadwal harian** (bukan real-time saat lihat laporan, bukan tombol manual) — konsisten dengan pola proses akhir hari yang lazim di sistem keuangan; (2) pemetaan `ft` → `gol`: **1/2/3/4 berurutan** (lancar=1, kurang_lancar=2, diragukan=3, macet=4) — konsekuensinya cuma kategori "macet" yang sekarang lolos syarat `gol>3` di `HapusBukuController`, "diragukan" belum.

**Yang dikerjakan:**
- `app/Console/Commands/UpdateKolektibilitasPembiayaan.php` (BARU) — command `pembiayaan:update-kolektibilitas`. Reset `gol` semua pembiayaan aktif (`os>0`) ke `1` dulu (supaya idempotent — pembiayaan yang tunggakannya sudah lunas otomatis balik ke Lancar), lalu kategorikan ulang yang overdue berdasarkan `ft` (rumus SQL identik dengan `ReportPpapController`, sengaja tidak direfactor jadi shared code karena scope sesi ini dibatasi ke pembuatan command baru, bukan restrukturisasi controller yang sudah berjalan).
- `routes/console.php` — `Schedule::command('pembiayaan:update-kolektibilitas')->dailyAt('01:00')`.
- `tests/Unit/Commands/UpdateKolektibilitasPembiayaanTest.php` (BARU) — 6 test: keempat kategori (lancar/kurang_lancar/diragukan/macet) diuji dengan data tunggakan simulasi terkontrol (bukan bergantung data live yang kebetulan cocok), pembiayaan tanpa tunggakan direset ke Lancar, pembiayaan yang sudah lunas (`os=0`) tidak ikut ter-update.
- `tests/Feature/Report/ReportPpapTest.php` (BARU) — 5 test menutup `ReportPpapController` yang sebelumnya tidak ada test coverage sama sekali: halaman, cari (sukses & gagal validasi), export PDF, export Excel.

**Follow-up yang belum dikerjakan (di luar scope diminta, dicatat untuk sesi lanjutan bila diperlukan):** dengan `gol` sekarang punya proses update yang benar, NPF% yang di-skip di Sesi 1.8 (karena saat itu `gol` terbukti tidak reliable) berpotensi dihidupkan kembali di Dashboard — tapi ini butuh keputusan terpisah (definisi NPF% dari sisi bisnis: kategori mana yang dihitung "bermasalah", dan kapan job harian ini sudah "matang" datanya untuk dipercaya sebagai KPI manajemen). Tidak dikerjakan sekarang karena tidak diminta eksplisit di sesi ini.

**Verifikasi:** `php artisan test` → 100 test, 351 assertion, semua pass (tidak ada regresi). `php artisan schedule:list` mengonfirmasi job terjadwal terdaftar (`dailyAt('01:00')`). Reproduksi manual (transaksi rollback) membuktikan command bekerja benar pada data live (1 pembiayaan overdue nyata, ft=1, dikategorikan Lancar dengan tepat).

---

### ✅ Sesi 4.7 — Review Per-Menu (Enterprise Code Review): Menu Report PPAP
**Status:** ✅ SELESAI untuk menu PPAP (2026-08-22) — awal dari review menu satu-per-satu, dikonfirmasi ke user setelah tiap menu selesai sebelum lanjut ke menu berikutnya.

> [!WARNING]
> **Bug ditemukan & diperbaiki — duplikasi hitungan PPAP di batas `ft=12`.** Di `exportPdf()`/`exportExcel()`, kategori "diragukan" (`ft BETWEEN 7 AND 12`) dan "macet" (`ft >= 12`) overlap tepat di `ft=12` — debitur dengan `ft` persis 12 terhitung DUA KALI (masuk kedua kategori sekaligus di `$dataKolektibilitas`). Dibuktikan dengan angka konkret: 1 debitur, saldo pinjaman Rp 800.000, seharusnya PPAP Rp 800.000 (100%, macet) tapi jadi Rp 1.200.000 (400rb dobel dari 50% "diragukan" + 100% "macet") — provisi PPAP yang dilaporkan ke manajemen membengkak. Efek yang sama (bukan dobel dalam 1 laporan, tapi debitur muncul di 2 hasil pencarian terpisah) juga ada di `havingRaw` SQL yang dipakai `cari()`/`exportPdf()`/`exportExcel()` (`ft BETWEEN 7 AND 12` vs `ft >= 12`, overlap sama). **Diperbaiki:** batas atas "diragukan" dipersempit dari 12 ke 11 di 6 lokasi (3× filter PHP Collection, 3× `havingRaw` SQL) — "macet" tetap `>= 12` (definisi aslinya tidak diubah).

**Temuan lain (dicatat, tidak diperbaiki — di luar scope "cek bug", masuk kategori saran pola enterprise):**
- **DRY** — query kompleks (join `pembiayaan`+`kelompok`+`ao`+`tunggakan`, hitung `ft`) diduplikasi 3× nyaris identik di `cari()`/`exportPdf()`/`exportExcel()`. Kandidat kuat diekstrak ke Service, pola yang sama seperti Fase 2.
- **Form Request tidak konsisten** — `cari()` validasi `jenis_kolek` via `$request->validate()`, `exportPdf()`/`exportExcel()` tidak validasi sama sekali.
- **Magic numbers** — persentase PPAP (0.5%/10%/50%/100%) dan batas `ft` hardcoded tersebar di controller, bukan constant/config bernama — inilah salah satu penyebab akar bug boundary-overlap di atas tidak ketahuan lebih awal.
- **Efek samping dari Sesi 4.6:** kolom `GOL` di export Excel dibaca dari `pembiayaan.gol` (snapshot harian jam 01:00), sedangkan penempatan baris ke tab "Kolek 1-4" dihitung ulang real-time dari `ft` saat laporan digenerate — bisa tampak kontradiktif kalau situasi debitur berubah sejak update terakhir. Bukan bug baru, tapi konsekuensi nyata yang baru "terlihat" sekarang karena `gol` akhirnya punya makna.

**Yang dikerjakan:**
- `app/Http/Controllers/ReportPpapController.php` — fix boundary overlap (6 lokasi, `'diragukan'` batas atas 12→11).
- `tests/Feature/Report/ReportPpapTest.php` — tambah 1 test regresi: debitur dengan `ft` persis 12 dikonfirmasi cuma menambah hitungan kategori "macet", tidak menambah "diragukan" (dites lewat data live yang diinsert terkontrol dalam transaksi rollback, dibandingkan baseline sebelum-sesudah — bukan asumsi angka absolut, supaya tidak rapuh terhadap data live yang sudah ada).

**Verifikasi:** `php artisan test` → 101 test, 355 assertion, semua pass (tidak ada regresi).

**Selanjutnya:** menu berikutnya untuk direview menyusul, dikonfirmasi satu-per-satu ke user.

---

### ✅ Sesi 4.8 — Review Per-Menu: Menu Master Anggota
**Status:** ✅ SELESAI (2026-08-22)

**Keputusan bisnis yang dikonfirmasi ke user sebelum eksekusi:** aturan unik NIK dipilih **per-unit** (bukan global) — NIK boleh sama asal beda unit, cuma diblokir kalau duplikat di unit yang sama. Ini sesuai perilaku KODE yang sudah ada sebelumnya (bukan sesuai pesan errornya yang sebelumnya salah tulis) — jadi yang diperbaiki adalah PESAN-nya, bukan logikanya.

**Bug ditemukan & diperbaiki:**
1. **`update()` sama sekali tidak divalidasi** — `store()` (tambah) punya 16 aturan validasi (KTP 16 digit, umur maks 60, kelamin L/P, dll), `update()` (edit) langsung `->update([...])` tanpa `$request->validate()` apa pun. Diperbaiki dengan mengekstrak `StoreAnggotaRequest`/`UpdateAnggotaRequest` (pola Sesi 1.5) — sekarang keduanya konsisten tervalidasi.
2. **Pesan error KTP duplikat kontradiksi dengan logikanya** — kode cuma blokir duplikat di unit yang SAMA, tapi pesannya bilang "sudah ada di unit LAIN". Diperbaiki: pesan jadi "NIK sudah terdaftar di unit ini." (logika tetap, sesuai keputusan di atas). `UpdateAnggotaRequest` menambahkan pengecualian diri sendiri (anggota yang sedang diedit tidak dianggap "duplikat" terhadap KTP-nya sendiri) via `where('no', '!=', $noSedangDiedit)`.
3. **Generate `no_anggota` tidak di-scope per unit, race condition** — `Anggota::latest()->first()` global (bukan per-unit) dipakai sebagai basis nomor urut, dan tidak ada proteksi concurrent request. Diperbaiki: scope `where('unit', $unit)->whereDate('created_at', today())`, dibungkus `DB::transaction()` + `lockForUpdate()` untuk mengurangi risiko 2 admin di unit & hari yang sama dapat `no` yang sama persis (catatan: proteksi ini tidak 100% menutup celah untuk kasus "anggota pertama hari itu" — `lockForUpdate()` tidak mengunci apa pun kalau belum ada baris yang cocok; kalau ini jadi masalah nyata butuh tabel counter terpisah, di luar scope perbaikan proporsional sesi ini).
4. **`AnggotaExport` tanpa scope unit dan tanpa chunking** — export bisa ambil SEMUA anggota lintas unit, dan pakai `FromCollection` (unbounded load, pola crash yang sama seperti `Anggota::all()` yang sudah pernah diperbaiki di `index()`/`data()` tapi luput di sini). Diperbaiki: constructor terima `$unit`, scope query, dan diganti ke `FromQuery` (chunked reading otomatis dari Maatwebsite Excel).

> [!WARNING]
> **Bug baru ditemukan saat menulis test (di luar 8 temuan awal) — `Anggota` model salah konfigurasi primary key.** Model tidak mendeklarasikan `$incrementing = false` / `$keyType = 'string'` untuk primary key `no` (VARCHAR, bukan auto-increment) — persis kelas bug yang sama dengan `simpanan.php` yang sudah diperbaiki di Sesi 3.2. Akibatnya: SETELAH `Anggota::create([...])` berhasil, Eloquent otomatis menimpa atribut `no` di objek PHP (BUKAN di database — baris di DB tetap benar) dengan `lastInsertId()`, yang untuk tabel non-auto-increment selalu `'0'`. Dibuktikan lewat reproduksi manual: `$anggota->no` jadi `'0'` padahal baris di database punya `no` yang benar. Dampak nyata yang ditemukan: `Log::info('Data anggota berhasil disimpan:', $anggota->toArray())` di `store()` mencatat `no` yang SALAH ke log (audit trail menyesatkan), dan kode lain mana pun yang memakai `$anggota->no` (bukan variabel lokal `$noAnggota`) setelah `create()` akan dapat nilai salah. **Diperbaiki:** tambah `public $incrementing = false;` dan `protected $keyType = 'string';` ke `app/Models/Anggota.php`.
>
> **Efek samping dari fix di atas — ditemukan, DICATAT TAPI TIDAK DIPERBAIKI (di luar scope menu ini):** begitu `no` dipertahankan dengan benar sebagai string, `LogsActivity` (Spatie, dipasang di Sesi 3.1) mencoba mencatat `subject_id` ke tabel `activity_log` — kolomnya bertipe `BIGINT`, jadi kalau `no` mengandung karakter non-angka, insert GAGAL (`Incorrect integer value`). Saat ini AMAN untuk penggunaan normal karena `no` produksi selalu all-digit (format `unit+tanggal+urut`), tapi ini rapuh — kalau suatu saat ada `no` yang bukan murni angka (input manual, format berubah, dll), fitur activity log untuk Anggota akan crash total. **Kemungkinan besar masalah yang sama juga berlaku untuk model `simpanan` (primary key `reff` sekarang ULID, jelas bukan angka, sejak Sesi 4.3)** — belum diverifikasi langsung, direkomendasikan jadi bagian scope saat menu Transaksi/Simpanan direview.

**Enterprise pattern yang diperbaiki:**
- `cariKtp()` — URL API eksternal (`http://mobcoll.nurinsani.co.id/...`, sudah pernah pindah server sekali dalam riwayat proyek ini) dipindah ke `config/services.php` (key `mobcol.ktp_url`, fallback ke URL lama supaya tidak breaking kalau `.env` belum diisi) + `.env.example` didokumentasikan. `$nik` diganti jadi query-array (`Http::get($url, ['ktp' => $nik])`) alih-alih interpolasi string mentah.
- `destroy()`/`show()` — stub kosong dihapus dari controller, resource route dipersempit `->except(['show', 'destroy'])` (dikonfirmasi dulu tidak dipakai di view manapun).
- Import mati `use function Illuminate\Log\log;` dihapus.

**Yang dikerjakan:**
- `app/Http/Requests/Anggota/StoreAnggotaRequest.php`, `UpdateAnggotaRequest.php` (BARU).
- `app/Http/Controllers/AnggotaController.php` — Form Request di `store()`/`update()`, sequence generation dengan lock, hapus `show()`/`destroy()`, `cariKtp()` pakai config, `export()` teruskan unit.
- `app/Models/Anggota.php` — fix primary key config.
- `app/Exports/AnggotaExport.php` — `FromQuery` + scope unit.
- `config/services.php`, `.env.example` — key `mobcol.ktp_url`.
- `routes/web.php` — resource route dipersempit.
- `tests/Feature/Anggota/AnggotaStoreTest.php` — 6 test baru (di atas 3 yang sudah ada dari Sesi 4.2): sequence per-unit, KTP boleh duplikat lintas unit, KTP gagal duplikat unit sama, update gagal data invalid, update tidak gagal karena KTP milik sendiri, update gagal KTP diganti jadi duplikat anggota lain.
- `tests/Feature/Anggota/AnggotaExportTest.php` (BARU) — 2 test: export hanya berisi unit yang login, endpoint export berhasil.

**Verifikasi:** `php artisan test` → 109 test, 385 assertion, semua pass (tidak ada regresi di seluruh aplikasi, termasuk modul lain yang memakai model `Anggota`).

**Selanjutnya:** menu berikutnya menyusul, dikonfirmasi ke user sebelum lanjut.

---

### ✅ Sesi 4.9 — Review Per-Menu: Menu Master Anggota → Input Pembiayaan (`PembiayaanController`)

**Status:** ✅ SELESAI (2026-08-22)

**Bug ditemukan & diperbaiki:**
1. **CRITICAL — `edit()` crash 100% reproducible (500 error)** — `Menu::whereNull('parent_id')->with('children')->orderBy('order')->get()` dipanggil tanpa `use App\Models\Menu;`, jadi PHP resolve `Menu` relatif ke namespace `App\Http\Controllers` dan lempar `Class "App\Http\Controllers\Menu" not found`. Artinya **halaman "Input Pembiayaan" (`/pembiayaan/edit/{cif}`) tidak bisa diakses sama sekali** sebelum perbaikan ini. Dibuktikan lewat test debug (`GET /pembiayaan/edit/086120` → status 500, error persis tercatat di `storage/logs/laravel.log`). Diperbaiki: ganti dengan `$this->getMenus()`, helper cache+role-filter yang sudah dipakai konsisten di controller lain (sekaligus menutup inkonsistensi pola yang sempat dicatat di Sesi 1.1).
2. **`edit()` tidak di-scope per unit** — query `anggota.cif = $cif` tanpa filter unit, jadi admin unit manapun bisa buka halaman input pembiayaan untuk CIF milik unit lain. Diperbaiki: tambah `->where('anggota.unit', Auth::user()->unit)`.
3. **`addPembiayaan()` mempercayai `unit` dan `id` dari client** — form mengirim field `unit` dan `id` (dipakai sebagai `userid`) yang divalidasi tapi TIDAK diverifikasi terhadap user yang login; keduanya langsung dipakai untuk insert baris `temp_akad_mus`, termasuk untuk logika keamanan "CIF sudah dipakai di unit lain". Client bisa memalsukan kedua nilai ini. Diperbaiki: field `unit`/`id` dihapus dari `AddPembiayaanRequest`, controller derive `$unit = Auth::user()->unit` dan `$userId = Auth::id()` sendiri, dipakai di semua titik (cek existing record, insert `temp_akad_mus.unit`, `temp_akad_mus.userid`).
4. **Exception bocor ke response client** — blok `catch (\Throwable $e)` mengembalikan `$e->getMessage()` mentah ke JSON response (bisa membocorkan detail query/struktur DB). `Log::error(...)` di atasnya sudah mencatat detail lengkap ke server. Diperbaiki: pesan client diganti generik ("Terjadi kesalahan saat menyimpan pembiayaan..."), logging server tidak diubah.

**Enterprise pattern yang diperbaiki:**
- Validasi `addPembiayaan()` diekstrak ke `app/Http/Requests/Pembiayaan/AddPembiayaanRequest.php` (pola Form Request, konsisten dengan Sesi 1.5/4.8), sekaligus jadi tempat yang jelas untuk mendokumentasikan kenapa `unit`/`id` sengaja tidak divalidasi dari input.
- Konsistensi perbandingan `jenis_pembiayaan` — sebelumnya campur `(int) $validated['jenis_pembiayaan'] === 2` dan `$validated['jenis_pembiayaan'] == 2` di beberapa tempat berbeda; disatukan jadi satu variabel `$jenisPembiayaan = (int) $validated['jenis_pembiayaan']` yang dicast sekali di awal, dibandingkan dengan `===` di semua titik.

**Yang dikerjakan:**
- `app/Http/Requests/Pembiayaan/AddPembiayaanRequest.php` (BARU) — rules tanpa `unit`/`id`.
- `app/Http/Controllers/PembiayaanController.php` — `edit()` pakai `getMenus()` + scope unit; `addPembiayaan()` pakai Form Request, `$unit`/`$userId`/`$jenisPembiayaan` dari `Auth::`, pesan error generik di catch.
- `tests/Feature/Pembiayaan/PembiayaanEditAddTest.php` (BARU) — 4 test: `edit()` berhasil untuk CIF di unit sendiri, `edit()` redirect (bukan crash/bocor data) untuk CIF di unit lain, `addPembiayaan()` sukses dan `unit`/`userid` di DB terbukti berasal dari `Auth::` walau client mengirim `unit`/`id` palsu di payload, `addPembiayaan()` gagal validasi 422 untuk payload kosong.

**Verifikasi:** `php artisan test` → 113 test, 401 assertion, semua pass (baseline 109 test Sesi 4.8 + 4 test baru, tidak ada regresi).

**Selanjutnya:** menu berikutnya menyusul, dikonfirmasi ke user sebelum lanjut.

---

### ✅ Sesi 4.10 — Bug Laporan User: Pencarian Kelompok di Input Pembiayaan Tidak Menampilkan Data yang Sudah Ada

**Status:** ✅ SELESAI (2026-08-22)

**Laporan user:** "kenapa ketika melakukan pencarian kelompok di pembiayaan data tidak muncul padahal datanya ada".

**Root cause:** `PembiayaanController::data()` sengaja menyembunyikan (`whereNotExists`) anggota yang masih punya baris staging di `temp_akad_mus` (supaya anggota dengan pengajuan pending tidak muncul dobel) — logika ini sendiri benar. Bug-nya ada di `RealisasiMusyarokahService::realisasikan()`: setelah baris akad berhasil dipindah dari `temp_akad_mus` ke `pembiayaan` (`INSERT INTO pembiayaan ... SELECT ... FROM temp_akad_mus`), statement pembersihan `DELETE FROM temp_akad_mus` di baris berikutnya **di-comment**, sehingga baris staging tidak pernah terhapus. Akibatnya, setiap anggota yang pernah direalisasi lewat jalur Musyarokah, `temp_akad_mus`-nya nyangkut selamanya → dianggap "masih pending" → permanen hilang dari hasil pencarian kelompok di menu Input Pembiayaan, walaupun anggota & pembiayaannya benar-benar ada dan valid. Dikonfirmasi lewat data live: kelompok "003-0220" punya 3 anggota (CIF 086120/086121/086122) yang sudah punya baris `pembiayaan` aktif tapi tetap tersembunyi karena `temp_akad_mus`-nya belum terhapus.

Dibandingkan dengan `RealisasiMurabahahService` (jalur akad Murabahah) yang SUDAH benar melakukan `DB::table('temp_akad_mus')->where('cif', $akad->cif)->delete();` setelah realisasi sukses — mengonfirmasi ini murni bug/statement yang lupa diaktifkan lagi, bukan perbedaan desain yang disengaja.

**Perbaikan:** `RealisasiMusyarokahService.php` baris delete yang tadinya di-comment diaktifkan (pola disamakan dengan Murabahah, pakai query builder bukan raw string interpolation).

**Catatan:** 3 baris data lama (CIF 086120/086121/086122) yang sudah telanjur nyangkut di `temp_akad_mus` sejak sebelum fix ini **sengaja TIDAK dibersihkan** — user memilih fix kode saja dulu, pembersihan data lama ditangani terpisah kalau diperlukan.

**Yang dikerjakan:**
- `app/Services/Realisasi/RealisasiMusyarokahService.php` — aktifkan `DB::table('temp_akad_mus')->where('cif', $value)->delete();` setelah insert ke `pembiayaan`.
- `tests/Feature/Realisasi/RealisasiMusyarokahTest.php` — tambah assertion `assertDatabaseMissing('temp_akad_mus', ['cif' => $row->cif])` di `test_proses_realisasi_berhasil` sebagai regression test untuk bug ini.

**Verifikasi:** `php artisan test` → 113 test, 402 assertion, semua pass (tidak ada regresi).

---

## 🚨 INSIDEN — DatabaseTransactions Tidak Rollback untuk Kode dengan `DB::beginTransaction()`/`DB::commit()` Eksplisit Bersarang (2026-08-22, belum diinvestigasi tuntas)

**Status:** ⚠️ TERBUKA — root cause belum ditemukan, dampak penuh belum diverifikasi.

Saat menjalankan `php artisan test --filter=RealisasiMusyarokahTest` dua kali berturut-turut (untuk verifikasi Sesi 4.10), 3 baris `temp_akad_mus` (CIF 086120/086121/086122 — yang sengaja TIDAK dihapus di Sesi 4.10 atas permintaan user) **terhapus permanen dari database live**, padahal test tersebut pakai `DatabaseTransactions` yang seharusnya rollback otomatis. Dibuktikan: data lain (Anggota, dll — tanpa `DB::beginTransaction()` eksplisit di controller/service-nya) rollback normal; hanya kode yang memanggil `DB::beginTransaction()`/`DB::commit()` eksplisit (`RealisasiMusyarokahService::realisasikan()`) yang datanya bocor permanen ke database sungguhan.

**Dampak potensial (belum diverifikasi):** service lain dengan pola sama (`TransaksiService`, `SetoranService`, `RealisasiMurabahahService`, `PullDataService`) berpotensi punya masalah sama — test apa pun (dari sesi ini atau sesi-sesi sebelumnya) yang mengeksekusi kode di service-service itu mungkin sudah menulis perubahan permanen ke database sungguhan tanpa disadari.

**Belum dikerjakan:** investigasi root cause, audit dampak ke service lain. User dikonfirmasi soal ini, lalu mengalihkan ke temuan lain (Sesi 4.11) — kembali ke sini kalau diminta.

---

### ✅ Sesi 4.11 — Menu Activity Log: Sertakan CIF di Deskripsi/Properties Log

**Status:** ✅ SELESAI (2026-08-22)

**Konteks:** Review menu Activity Log (diminta user, "cek menu activity log berantakan") menemukan bahwa `activity_log.subject_id` (BIGINT) kehilangan leading zero primary key `Anggota.no` (mis. `'00108612001'` tersimpan sebagai `108612001`) — dibuktikan lewat reproduksi manual (transaksi di-rollback manual, bukan lewat test harness yang lagi diinvestigasi di insiden atas). Selain itu `LogUserAccess` middleware (log akses per-request) tidak pernah menyertakan CIF walau request-nya jelas tentang satu anggota tertentu (mis. `GET pembiayaan/edit/086120`). User minta: kolom "Perubahan"/deskripsi menyertakan CIF yang diubah/di-input/di-GET/di-POST.

**Yang diperbaiki:**
1. **`app/Models/Anggota.php`** — tambah `tapActivity()` supaya SETIAP log `log_name=default` untuk Anggota (create/update/delete) menyisipkan `cif` dan `no_anggota` (nilai penuh, bukan lewat `subject_id` yang leading zero-nya hilang) ke `properties`, terlepas dari field mana yang dirty.
2. **`app/Http/Middleware/LogUserAccess.php`** — CIF dicari dari route parameter (`{cif}` di URL), lalu query string/body (`$request->input('cif')`), dicakup untuk GET maupun POST. Kalau ketemu: ditambahkan ke `properties['cif']` DAN langsung ditulis di deskripsi (`"GET pembiayaan/edit/086120 (CIF: 086120)"`) supaya langsung kelihatan di kolom Deskripsi tanpa perlu buka detail JSON.

**Verifikasi:** direproduksi manual dulu (transaksi di-rollback manual via tinker, menghindari test harness yang lagi bermasalah) — properties berisi `cif`/`no_anggota` dengan benar. Setelah itu, dikonfirmasi aman dites lewat `php artisan test` biasa (middleware & `tapActivity` TIDAK memakai `DB::beginTransaction()` eksplisit — pola insert biasa lewat Spatie, sama seperti tulisan lain yang sudah terbukti rollback normal) — baris test yang diinsert dicek eksplisit sudah hilang lagi setelah test selesai.

**Yang dikerjakan:**
- `app/Models/Anggota.php` — `tapActivity()` baru.
- `app/Http/Middleware/LogUserAccess.php` — deteksi & sisipkan CIF.
- `tests/Feature/Anggota/AnggotaStoreTest.php` — `test_update_anggota_mencatat_cif_di_activity_log`.
- `tests/Feature/Pembiayaan/PembiayaanEditAddTest.php` — `test_akses_pembiayaan_edit_mencatat_cif_di_activity_log`.

**Verifikasi akhir:** `php artisan test` → 116 test lain semua pass, 417 assertion (1 test gagal — `RealisasiMusyarokahTest::test_proses_realisasi_berhasil` — itu murni akibat insiden data di atas, bukan regresi dari perubahan sesi ini).

**Belum dikerjakan (temuan lain dari review Activity Log, menunggu prioritas dari user):**
- `LogUserAccess` mencatat SETIAP request (termasuk AJAX/autocomplete) sebagai `log_name=akses` — membanjiri tabel, bikin log `default` (perubahan data beneran) tenggelam di tabel/tampilan yang sama.
- Kolom "Subjek" di tabel tampil blank (bukan "-") untuk baris `akses`.

---

### ✅ Sesi 4.12 — Bug Laporan User: Riwayat Input Transaksi Tidak Menampilkan Transaksi yang Sudah Masuk ke `simpanan`

**Status:** ✅ SELESAI (2026-08-22)

**Laporan user:** "pada menu Transaksi Setoran Input Transaksi data sudah masuk kesimpanan ke tabel simpanan, namun pada data tabel menu tersebut historis inputan tidak mucul".

**Root cause:** `InputTransaksiController::getHistory($cif)` cuma query `UNION ALL` dari `simpanan_wajib` dan `simpanan_pokok`. Tapi `TransaksiService::simpanTransaksi()` untuk `jenis_transaksi` 2 (penarikan tunai), 3 (setor angsuran), dan 4 (pemindahbukuan) menulis ke tabel `simpanan` (bukan pokok/wajib) — jadi transaksi jenis-jenis itu berhasil tersimpan (uangnya benar masuk) tapi TIDAK PERNAH ikut ditampilkan di tabel riwayat pada menu yang sama, karena `simpanan` tidak pernah diikutkan di UNION.

**Perbaikan:** tambah cabang `UNION ALL` ketiga dari tabel `simpanan` (label `jenis = 'Simpanan'`). Kolom disebutkan eksplisit di ketiga cabang (bukan `table.*`) karena `simpanan` tidak punya kolom `deleted_at`/`created_by`/`updated_by`/`ip_address` yang ada di `simpanan_pokok`/`simpanan_wajib` — UNION ALL butuh jumlah kolom sama persis di semua SELECT, jadi tidak bisa langsung `simpanan.*`.

**Yang dikerjakan:**
- `app/Http/Controllers/InputTransaksiController.php` — `getHistory()` tambah union ke `simpanan`, kolom eksplisit disamakan di 3 cabang.
- `tests/Feature/Transaksi/InputTransaksiTest.php` — `test_penarikan_tunai_muncul_di_history` (store jenis_transaksi=2, lalu assert baris barunya muncul di response history).

**Catatan verifikasi:** karena `TransaksiService::simpanTransaksi()` juga pakai `DB::beginTransaction()`/`DB::commit()` eksplisit (pola sama seperti `RealisasiMusyarokahService` yang lagi diinvestigasi di insiden terbuka di atas), dicek dulu SEBELUM percaya hasil test: `DB::table('simpanan')->where('ket', '<label test>')->count()` di database live setelah test selesai — hasilnya 0 (tidak bocor), beda dengan `RealisasiMusyarokahService`. Jadi bug rollback itu spesifik ke situ, bukan masalah umum di semua service yang pakai transaksi eksplisit — tapi root cause-nya sendiri tetap belum ditemukan (lihat catatan insiden).

**Verifikasi:** `php artisan test` → 117 test lain semua pass, 424 assertion (1 gagal — `RealisasiMusyarokahTest::test_proses_realisasi_berhasil` — murni dari insiden data sebelumnya, bukan regresi sesi ini).

---

## 📌 TEMUAN — Menu "Pemeliharaan CIF" (`/pemeliharaan-cif`) Belum Pernah Dibangun (2026-08-23)

**Status:** 📋 DICATAT, BUKAN PRIORITAS — sengaja dibiarkan atas keputusan user.

User lapor menu ini error saat diklik. Ditelusuri: menu-nya ADA di tabel `menus` (id 26, "Pemeliharaan CIF", url `/pemeliharaan-cif`, satu grup dengan "View Data" dan "Kelompok"), tapi **tidak ada route, controller, atau view sama sekali** untuk URL tersebut di codebase — beda dengan menu sebelahnya "Kelompok" (`/pemeliharaan-kelompok`) yang implementasinya lengkap (`PemeliharaanKelompok.php` + view). Jadi ini bukan bug regresi, melainkan link menu ke fitur yang memang belum pernah dibangun — user diberi tahu, dan diputuskan untuk dibiarkan dulu (bukan prioritas sekarang). Kalau nanti mau dikerjakan, perlu klarifikasi dulu fungsi yang dimaksud (edit CIF anggota? riwayat perubahan CIF? merge CIF duplikat?) sebelum implementasi.

---

## 📋 SUMMARY TABEL

| Sesi | Nama | File Baru | File Edit | Est. |
|------|------|-----------|-----------|------|
| 1.1 | BaseController + Menu Cache | 1 | 50+ | 🟢 Mudah |
| 1.2 | SoftDeletes | 1 migration | 5 model | 🟢 Mudah |
| 1.3 | Audit Trail | 1 migration + 1 trait | 5+ model | 🟢 Mudah |
| 1.4 | Fix Dashboard KPI | 0 | 2 | 🟢 Mudah |
| 1.5 | Form Request | 4 | 4 controller | 🟡 Sedang |
| 1.6 | Pisah API Routes | 1 | banyak | 🟡 Sedang |
| 1.7 | Audit Trail: ip_address tabel_transaksi | 0 | 16 controller | 🟡 Sedang |
| 1.8 | Dashboard KPI Lanjutan (NPF%, SHU YTD) | 0 | 2 | 🔴 Kompleks |
| 2.1 | Spatie Permission | 1 seeder | 2 | 🟡 Sedang |
| 2.2 | TransaksiService | 1 | 1 controller | 🔴 Kompleks |
| 2.3 | SetoranService | 2 | 2 controller | 🔴 Kompleks |
| 2.4 | RealisasiService | 2 | 2 controller | 🔴 Kompleks |
| 2.5 | PullDataJob | 1 | 1 controller | 🔴 Kompleks |
| 2.6 | LaporanJob | 1 | 3 controller | 🟡 Sedang |
| 2.7 | Repository Pattern | 4 | beberapa | 🔴 Kompleks |
| 3.1 | Activity Log | 2 | 3+ model | 🟡 Sedang |
| 3.2 | Unit Tests | 3 | 0 | 🟡 Sedang |
| 3.3 | Feature Tests Auth | 2 | 0 | 🟡 Sedang |
| 3.4 | Feature Tests Transaksi | 3 | 0 | 🟡 Sedang |
| 3.5 | Telescope + Docs | 2 | 1 | 🟢 Mudah |

---

## 🚀 CARA PENGGUNAAN

Cukup ketik di percakapan baru:

```
"Kerjakan Sesi 1.1"
"Kerjakan Sesi 1.2"
"Kerjakan Sesi 2.2 — TransaksiService"
```

Setiap sesi dirancang selesai dalam **1 percakapan** tanpa perlu context panjang.

> [!TIP]
> Mulai dari **Sesi 1.1 → 1.2 → 1.3** secara berurutan. Fase 1 adalah pondasi yang Fase 2 bergantung padanya.

> [!IMPORTANT]
> Sesi 2.2, 2.3, 2.4 (Service Layer) sebaiknya dikerjakan **setelah** Sesi 1.5 (Form Request) selesai, karena Form Request akan digunakan di dalam Service.
