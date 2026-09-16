# Evaluasi Code Pattern Enterprise (2026-08-20)

Evaluasi menyeluruh setelah Fase 1-3 (`rencana_pengerjaan.md`) selesai. Tujuan: bandingkan kondisi kode saat ini dengan pola enterprise standar, kasih temuan konkret + rekomendasi prioritas — bukan daftar keluhan generik.

## Metodologi

Audit berbasis pencarian pola di seluruh `app/` (67 controller, 34 model, 6 service, 2 repository) + pengecekan konfigurasi/tooling/test. **Bukan review baris-per-baris tiap file** — untuk codebase seukuran ini (237 route), itu tidak realistis dalam satu sesi. Setiap temuan di bawah disertai bukti konkret (file:baris atau angka hasil pencarian) supaya bisa diverifikasi ulang, dan setiap temuan menyebutkan cakupan pengecekannya secara eksplisit.

## Ringkasan Prioritas

| # | Temuan | Severity | Effort perbaikan |
|---|---|---|---|
| 1 | SQL injection di 2 Service transaksi (`input_debet`/`input_nyata_setor` tidak divalidasi numerik sebelum masuk `DB::raw()`) | 🔴 Kritis | Kecil |
| 2 | Kredensial database hardcoded sebagai default di `config/database.php` (sudah dicatat Sesi 3.5) | 🔴 Kritis | Kecil |
| 3 | `APP_DEBUG=true` di `.env` yang terhubung ke database berskala produksi (30 juta+ baris) | 🟠 Tinggi | Kecil (kalau memang env produksi) |
| 4 | Spatie Permission ter-install tapi 0% dipakai — otorisasi masih 100% lewat `role:N` kasar | 🟠 Tinggi | Sedang-Besar |
| 5 | Service Layer baru menjangkau ~9% controller (6 dari 67) | 🟡 Sedang | Besar, bertahap |
| 6 | Observability nyaris tidak ada: 92% catch block tidak logging | 🟡 Sedang | Sedang |
| 7 | Test coverage ~20% controller (13 dari 67, semua dari Fase 2-3) | 🟡 Sedang | Besar, bertahap |
| 8 | Tidak ada CI/CD & static analysis (phpstan/larastan) | 🟡 Sedang | Kecil-Sedang |
| 9 | Duplikasi signifikan antara `SetoranPerkelompokService` & `SetoranBedaHariService` | 🟢 Rendah | Sedang |
| 10 | 10 dari 34 model pakai nama class lowercase/snake_case (melanggar PSR-1) | 🟢 Rendah (kosmetik, risiko rename tinggi) | Besar & berisiko |

---

## 1. 🔴 SQL Injection — `SetoranPerkelompokService` & `SetoranBedaHariService`

**Lokasi:** `app/Services/Setoran/SetoranPerkelompokService.php:168-171`, `app/Services/Setoran/SetoranBedaHariService.php:166-169`

```php
$jumlahDebet = $inputDebet[$item->no_anggota] ?? 1;   // langsung dari request()->input('input_debet', [])
...
DB::table('pembiayaan')->where('no_anggota', $item->no_anggota)->update([
    'run_tenor' => DB::raw("run_tenor + $jumlahDebet"),   // interpolasi string mentah, BUKAN parameter binding
    'ke' => DB::raw("ke + $jumlahDebet"),
    'os' => DB::raw("os - $nyataSetor"),
]);
```

`$jumlahDebet` dan `$nyataSetor` berasal dari body request (`input_debet[no_anggota]`, `input_nyata_setor[no_anggota]`) tanpa validasi tipe/numerik apa pun (tidak ada Form Request untuk endpoint `proses/{code_kel}` ini — parameter diambil langsung lewat `request()->input()` di controller), lalu diinterpolasi mentah ke dalam `DB::raw()` yang menjadi bagian dari klausa `SET` sebuah `UPDATE`. Ini persis kelas bug yang sudah diperbaiki di `RealisasiMusyarokahService` pada Sesi 2.4 (waktu itu lewat `DB::statement` dengan string interpolation, sekarang di-fix pakai parameter binding) — tapi 2 service lain yang lebih baru (Sesi 2.3) belum ikut diperbaiki karena scope Sesi 2.3 saat itu adalah "pindahkan logika, jangan ubah logika bisnis".

Endpoint ini di belakang middleware `role:1` (admin only), jadi bukan celah yang bisa dieksploitasi anonim — tapi tetap eksploitasi serius kalau ada akun admin yang disusupi/disalahgunakan (ancaman internal, phishing kredensial admin, dst.), dan merupakan technical debt yang murni salah secara teknis, bukan business logic.

**Rekomendasi:** ganti ke Query Builder increment/decrement yang parameter-bound:
```php
DB::table('pembiayaan')->where('no_anggota', $item->no_anggota)->update([
    'run_tenor' => DB::raw('run_tenor + ?'), // atau ->increment()/->decrement() kalau strukturnya cocok
]);
```
atau minimal `(int) $jumlahDebet` / `(float) $nyataSetor` sebelum interpolasi (mitigasi cepat, tidak seideal parameter binding tapi cukup untuk menutup celah ini). **Effort kecil, dampak besar — kandidat pertama untuk sesi perbaikan terpisah.**

## 2. 🔴 Kredensial Database Hardcoded (sudah dicatat Sesi 3.5, ditegaskan lagi di sini)

`config/database.php:63-67` — koneksi `cs` (database `mobcol` eksternal) punya IP/username/password sebagai default value `env()`, tersimpan polos di file yang ter-track git. Lihat catatan lengkap di `rencana_pengerjaan.md` Sesi 3.5. **Belum diperbaiki.**

## 3. 🟠 `APP_DEBUG=true` di `.env`

`.env` lokal yang dipakai sepanjang sesi kerja ini terhubung ke database dengan skala data produksi (`simpanan` 30 juta+ baris, `anggota` 305 ribu baris) dan `APP_DEBUG=true`. Kalau `.env` ini (atau konfigurasi serupa) yang jalan di server produksi sungguhan, setiap exception tak tertangani akan menampilkan stack trace lengkap (path server, query SQL, kadang isi variabel) ke browser end-user — kebocoran informasi klasik. **Tidak bisa dipastikan dari sesi kerja ini apakah `.env` yang diaudit = `.env` produksi** — perlu konfirmasi langsung ke user/tim infra. Kalau iya, ini prioritas tinggi untuk diperbaiki (`APP_DEBUG=false` + pastikan `config:cache` jalan di server).

## 4. 🟠 Spatie Permission Ter-install, Tidak Dipakai

Sesi 2.1 meng-install & seed Spatie Permission (role & permission tables). Pencarian `->can(`, `Gate::`, `@can`, `hasPermissionTo`, `->hasRole(` di seluruh `app/Http/Controllers`, `routes/web.php`, dan `resources/views` → **0 hasil**. Semua otorisasi nyatanya masih lewat `RoleMiddleware` custom yang membandingkan `role_id` integer mentah terhadap daftar di route (`role:1`, `role:1,2,3,4`, dst — cuma 5 kombinasi berbeda di seluruh aplikasi).

Konsekuensi: granularitas otorisasi sangat kasar (all-or-nothing per role besar), tidak ada cara memberi 1 admin akses parsial tanpa role penuh, dan investasi Sesi 2.1 (migrasi, seeding, trait `HasRoles` di model `User`) belum memberi nilai apa pun ke aplikasi.

**Rekomendasi:** baik lanjutkan migrasi ke permission granular (`@can` di view, `authorize()` di FormRequest/Controller) secara bertahap per modul, ATAU — kalau `role:N` yang kasar memang cukup untuk kebutuhan bisnis KSPPS ini — copot Spatie Permission untuk mengurangi kompleksitas yang tidak terpakai. Keputusan ini butuh input bisnis (apakah memang perlu otorisasi granular), bukan keputusan teknis semata.

## 5. 🟡 Service Layer: Baru ~9% Controller

67 controller total. Hanya **6 controller** (`InputTransaksiController`, `SetoranPerkelompokController`, `SetoranBedaHariController`, `RealisasiMusyarokahController`, `RealisasiMurabahahController`, `PullDataController`) meng-inject `*Service` di constructor. **55 controller** (82%) masih punya `DB::table`/`DB::raw`/`DB::statement` langsung di method controller — pola "fat controller" klasik: validasi, query, dan response-building semua di satu tempat.

Ini bukan kejutan — Fase 2 (`rencana_pengerjaan.md` Sesi 2.2-2.7) memang secara sadar dan eksplisit membatasi scope ke 5 alur transaksi paling kritis (bukan me-refactor semua 67 controller), keputusan yang masuk akal untuk membatasi risiko per sesi. Tapi ini berarti mayoritas aplikasi — terutama seluruh grup `Report*Controller` dan `Cetak*Controller` (laporan keuangan: neraca, laba rugi, arus kas) — **belum** mengikuti pola Service Layer yang baru dibangun, dan risiko bug/duplikasi di area itu belum tersentuh sama sekali oleh kerja Fase 1-3.

**Rekomendasi:** lanjutkan refactor Service Layer secara bertahap, prioritaskan controller dengan business logic paling kompleks/berisiko (kandidat: `HitungShuController`, karena logikanya sudah disebut "kompleks" di catatan Sesi 1.8, dan dipakai sebagai sumber kebenaran SHU) — bukan sekaligus semua 55 sisanya.

## 6. 🟡 Observability: 92% Catch Block Tidak Logging

36 `catch` block ditemukan di `app/Http/Controllers` + `app/Services`, hanya **3** yang memanggil `Log::`. Total pemakaian `Log::` di seluruh `app/` cuma di 9 file. Pola dominan: `catch (\Exception $e) { return response()->json(['success' => false, 'message' => $e->getMessage()], 500); }` — error terekspos ke client (bisa jadi kebocoran detail internal, mirip poin 3) tapi **tidak pernah tercatat di server**. Kalau ada bug produksi yang cuma muncul sesekali, tidak ada jejak di log untuk investigasi — satu-satunya cara tahu adalah user melapor manual.

Instalasi Telescope di Sesi 3.5 sedikit membantu (menangkap exception & failed request otomatis, `TelescopeServiceProvider::register()` sudah difilter untuk selalu menyimpan `isReportableException()`/`isFailedRequest()` walau di luar local) — tapi Telescope dev-only di setup ini, jadi kalau tidak diaktifkan juga di production dengan `Telescope::night()`/gate yang sesuai, ini belum menutup gap-nya.

**Rekomendasi:** tambahkan `Log::error($e->getMessage(), ['exception' => $e])` (minimal) di setiap catch block yang menangani exception tak terduga, terutama di Service layer yang menangani transaksi uang. Bisa dilakukan bertahap, per modul yang disentuh sesi berikutnya — tidak perlu 1 sesi besar untuk 36 lokasi sekaligus.

## 7. 🟡 Test Coverage: ~20% Controller

8 file test (63 test case) dari Fase 3, semuanya untuk alur yang sudah di-refactor ke Service Layer (Transaksi, Setoran, Realisasi, Auth, Dashboard). 54 dari 67 controller (80%) tidak punya test sama sekali — termasuk seluruh grup Laporan (`Report*`) dan Cetak (`Cetak*`), yang notabene berisi logika keuangan (neraca, laba rugi, SHU) yang salah hitungnya bisa berdampak besar kalau ada regresi diam-diam.

Ini konsisten dengan temuan #5 — test coverage saat ini mengikuti tepat controller mana yang sudah di-refactor ke Service, karena Service yang testable (dependency jelas, tidak terikat langsung ke HTTP layer) jauh lebih gampang ditest ketimbang controller fat yang campur validasi+query+response.

**Rekomendasi:** coverage test sebaiknya jalan beriringan dengan refactor Service Layer (temuan #5), bukan usaha terpisah — begitu sebuah controller di-refactor ke Service, langsung tulis test untuk Service itu (pola yang sudah terbukti jalan baik di Fase 3).

## 8. 🟡 Tidak Ada CI/CD & Static Analysis

- `laravel/pint` terpasang di `composer.json` (code style otomatis) — bagus, tapi tidak ada bukti dijalankan otomatis (tidak ada git hook/CI yang menjalankannya).
- Tidak ada `phpstan`/`larastan` — tidak ada pengecekan tipe statis, jadi kelas bug seperti "kolom tidak ada di tabel" (yang berulang kali muncul sepanjang sesi ini akibat schema drift) baru ketahuan saat runtime, bukan sebelum deploy.
- Tidak ada folder `.github/workflows` — tidak ada pipeline yang otomatis menjalankan `php artisan test` saat push/PR. Artinya regresi cuma ketahuan kalau seseorang menjalankan test secara manual, seperti yang dilakukan sepanjang sesi ini.

**Rekomendasi:** minimal viable — 1 GitHub Actions workflow yang jalankan `php artisan test` (perlu database test MySQL terpisah di CI runner dulu, karena app ini tidak bisa pakai sqlite — lihat keputusan Sesi 3.3) + `vendor/bin/pint --test` di setiap PR. Larastan bisa menyusul kalau tim ingin investasi lebih jauh.

## 9. 🟢 Duplikasi: `SetoranPerkelompokService` vs `SetoranBedaHariService`

285 baris vs 252 baris, dengan porsi signifikan logika yang mirip (proses cek tunggakan, insert simpanan, update pembiayaan, insert jurnal) tapi tidak identik — bukan copy-paste murni, ada percabangan logika "beda hari" yang genuinely berbeda. Duplikasi ini kemungkinan besar hasil pola "copy service lalu ubah sedikit" yang lazim di banyak codebase, bukan sesuatu yang salah sengaja.

**Rekomendasi:** effort rendah-prioritas — kalau ada perbaikan bug di salah satu (termasuk temuan #1 di atas, yang menimpa KEDUA file ini), pertimbangkan ekstrak logika bersama ke trait/base class saat itu juga, daripada sesi ekstraksi terpisah yang berdiri sendiri.

## 10. 🟢 Konvensi Penamaan Model (PSR-1)

10 dari 34 model pakai nama class lowercase/snake_case, bukan StudlyCase: `ao`, `branch`, `paramBiaya`, `pembiayaan`, `pull_data`, `simpanan`, `simpanan_pokok`, `simpanan_wajib`, `temp_akad_mus`, `tunggakan`. Ini pelanggaran PSR-1 (nama class harus StudlyCaps) dan tidak konsisten dengan 24 model lain di codebase yang sama (`Anggota`, `Kelompok`, `Coa`, dst.).

**Sengaja TIDAK direkomendasikan untuk diperbaiki sekarang** — rename class butuh mengubah setiap `use App\Models\simpanan;` dan referensi lain di seluruh codebase (`grep -rl "Models\\\\simpanan"` akan menunjukkan cakupannya), risiko regresi tidak sebanding dengan manfaat kosmetiknya, apalagi mengingat riwayat sesi ini penuh insiden schema-drift yang butuh presisi tinggi soal nama tabel/kolom. Kalau suatu saat ada rewrite besar model-model ini untuk alasan lain, sekalian saja dirapikan penamaannya — bukan proyek berdiri sendiri.

---

## Yang Sengaja Tidak Diperiksa (batas cakupan audit ini)

- **Tidak** review baris-per-baris ke-67 controller atau ke-34 model satu-satu — audit ini pola/arsitektur level, bukan code review lengkap.
- **Tidak** audit performa query (N+1, index database) secara sistematis — di luar waktu sesi ini, dan butuh data produksi riil untuk EXPLAIN yang bermakna.
- **Tidak** audit `resources/views/**/*.blade.php` (XSS, penanganan CSRF di form) secara menyeluruh — spot-check saja (form input transaksi, cek `$.ajax` pattern untuk keperluan Sesi 3.4).
- **Tidak** menyentuh/menguji koneksi database `cs` (mobcol) yang sungguhan — kredensialnya sensitif, tidak dicoba connect di luar apa yang sudah divalidasi `PullDataService` di Fase 2.

## Rekomendasi Urutan Kerja

Kalau mau ditindaklanjuti sebagai sesi-sesi baru, urutan yang disarankan berdasar rasio dampak/effort:

1. **Sesi keamanan** (gabungkan #1 + #2 + konfirmasi #3) — effort kecil, dampak besar, semuanya sudah presisi lokasinya.
2. **Sesi observability** (#6) — tambah `Log::` di catch block, effort sedang, langsung membantu diagnosa produksi.
3. **Sesi CI minimal** (#8) — sekali setup, manfaat berkelanjutan (mencegah regresi diam-diam ke depannya).
4. **Keputusan otorisasi** (#4) — ini butuh keputusan bisnis dulu (granular atau tidak) sebelum ada kerja teknis.
5. **Refactor bertahap** (#5 + #7 + #9) — kerja jangka panjang, per modul, sama seperti pola Fase 2 sebelumnya (pilih 1 controller kompleks per sesi, bukan sekaligus).
6. **Penamaan model** (#10) — ditunda sampai ada alasan lain untuk menyentuh model-model itu.
