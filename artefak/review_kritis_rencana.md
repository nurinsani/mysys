# 🔍 Review Kritis: Rencana Enterprise vs Logika Bisnis
> Jawaban jujur: **mana yang aman, mana yang berisiko, dan apa yang perlu dikoreksi.**

---

## ✅ KESIMPULAN SINGKAT

| Kategori | Jumlah Sesi |
|----------|-------------|
| ✅ Aman — enterprise standard, tidak ganggu logika bisnis | 11 sesi |
| ⚠️ Perlu Penyesuaian — enterprise tapi ada detail yang harus dikoreksi | 4 sesi |
| 🔴 Berisiko Tinggi — bisa rusak logika bisnis jika tidak hati-hati | 3 sesi |

---

## 🔴 3 SESI BERISIKO TINGGI

---

### ❌ Sesi 1.1 — BaseController + Menu Caching (HARUS DIKOREKSI)

**Temuan masalah nyata:**

Ada **2 versi query menu yang BERBEDA** di codebase ini:

```php
// VERSI A — AdminController (dengan filter role_id) — BENAR untuk multi-role
$menus = Menu::whereNull('parent_id')
    ->where(fn($q) => $q->where('role_id', $roleId)->orWhereNull('role_id'))
    ->with(['children' => fn($q) => $q->where('role_id', $roleId)->orWhereNull('role_id')])
    ->orderBy('order')
    ->get();

// VERSI B — 50+ controller lain (TANPA filter role_id) — menampilkan SEMUA menu
$menus = Menu::whereNull('parent_id')
    ->with('children')
    ->orderBy('order')
    ->get();
```

**Dampak jika salah:** Jika BaseController menggunakan Versi A, maka 50+ halaman yang sebelumnya pakai Versi B akan berubah tampilan menunya. Pengguna bisa kehilangan akses ke menu yang seharusnya terlihat.

**Koreksi rencana:**
> Sesi 1.1 harus dimulai dengan **investigasi menu seeder** dulu — apakah kolom `role_id` di tabel `menus` sudah terisi dengan benar untuk semua menu? Jika belum, gunakan Versi B (tanpa filter) sebagai default di BaseController untuk sementara.

---

### ❌ Sesi 1.2 — SoftDeletes (HARUS DIKOREKSI)

**Temuan masalah nyata:**

Ditemukan `delete()` di beberapa controller dengan konteks **bisnis yang SENGAJA hard-delete:**

```php
// RestrukturisasiJatuhTempoController — tunggakan dihapus setelah restrukturisasi
DB::table('tunggakan')->where('cif', $cif)->delete();

// RestKemampuanBayarController — pembiayaan_detail dihapus setelah rest
DB::table('pembiayaan_detail')->where('cif', $value)->delete();

// HapusBukuController — ini memang fitur HAPUS BUKU, sengaja hapus
DB::table(...)->delete();

// HitungShuController — tabel_rugi_laba di-clear dulu sebelum dihitung ulang
DB::table('tabel_rugi_laba')->where('unit', $unit)->delete();
```

**Dampak jika salah:** Jika tabel `tunggakan` diberi SoftDeletes, maka `DB::table('tunggakan')->delete()` akan **tetap hard-delete** (karena pakai query builder, bukan Eloquent). Ini **inkonsisten** dan bisa menyebabkan data ghost.

**Koreksi rencana:**
> SoftDeletes **TIDAK boleh dipasang sembarangan**. Harus analisa per-tabel:
> - `anggota` → ✅ Aman pasang SoftDeletes
> - `pembiayaan` → ✅ Aman, tapi cek semua query yang join ke tabel ini
> - `tunggakan` → ❌ Jangan pasang SoftDeletes, ini memang di-clear by design
> - `tabel_rugi_laba` → ❌ Jangan pasang SoftDeletes, ini tabel kalkulasi yang di-reset
> - `pembiayaan_detail` → ❌ Jangan pasang, memang sengaja dihapus saat rest

---

### ❌ Sesi 1.6 — Pisah routes/api.php (SANGAT BERISIKO)

**Temuan masalah nyata:**

Ada **85+ AJAX calls** di seluruh blade views (ditemukan dari grep: 50+ `$.ajax` dan 9+ `fetch()`). Sebagian besar menggunakan **URL hardcoded**, bukan Laravel named routes:

```javascript
// URL HARDCODED — akan RUSAK jika URL berubah
const response = await fetch('/al/approval-pengajuan/cari-ktp', {...})
const response = await fetch('/cari-ktp', {...})
fetch(`/get-kelompok/${cao}`)
```

**Dampak jika salah:** Mengubah URL dari `/cari-ktp` ke `/api/v1/cari-ktp` akan langsung **mematikan semua AJAX** yang pakai URL hardcoded. Ini bisa merusak hampir semua halaman transaksi.

**Koreksi rencana:**
> Sesi 1.6 **HARUS dibatalkan/diubah total**. Alternatif yang aman:
> 1. Jangan ubah URL — cukup tambahkan `routes/api.php` untuk endpoint **BARU** saja
> 2. Endpoint lama di `web.php` tetap ada (backward compatible)
> 3. Secara bertahap update blade views ke named routes (`route('...')`) baru bisa pindah URL

---

## ⚠️ 4 SESI PERLU PENYESUAIAN

---

### ⚠️ Sesi 1.3 — Audit Trail (Perlu Penyesuaian)

**Masalah:** Menambah kolom `created_by` dan `updated_by` dengan tipe `NOT NULL` pada tabel yang sudah berisi data akan **menyebabkan error migration**.

**Koreksi:**
```php
// SALAH — migration akan gagal di tabel yang ada datanya
$table->string('created_by');

// BENAR — harus nullable atau ada default
$table->string('created_by')->nullable();
$table->string('updated_by')->nullable();
$table->string('ip_address', 45)->nullable();
```

---

### ⚠️ Sesi 1.5 — Form Request (Perlu Verifikasi)

**Masalah:** Validasi inline saat ini bisa jadi **lebih longgar** dari yang terlihat. Jika Form Request baru lebih ketat, data yang sebelumnya valid bisa ditolak.

**Contoh risiko:**
```php
// Validasi saat ini di InputTransaksiController
'jenis_simpanan' => 'required|in:pokok,wajib',

// Jika Form Request salah ditulis, bisa jadi:
'jenis_simpanan' => 'required|in:POKOK,WAJIB',  // case sensitive berbeda
```

**Koreksi:** Setiap Form Request harus **copy exact** dari validasi yang sudah ada, baru kemudian dirapikan.

---

### ⚠️ Sesi 2.5 — PullDataJob / Queue (Perlu Penyesuaian UX)

**Masalah:** PullData saat ini **sinkron** — user klik tombol, tunggu, lihat hasil langsung. Jika dijadikan async (queue), alur UX berubah total.

**Koreksi:** Perlu ada:
1. Loading indicator di halaman
2. Mekanisme polling status (setiap 3 detik cek apakah job selesai)
3. Atau pakai Laravel Echo + Broadcasting (lebih kompleks)

Ini bukan sekedar refactor controller — ini **perubahan UX yang perlu disetujui pengguna aplikasi** dulu.

---

### ⚠️ Sesi 2.1 — Spatie Permission (Perlu Hati-hati Migrasi)

**Masalah:** Saat ini role divalidasi via integer `role_id` (1,2,3,4) di middleware. Spatie menggunakan string `role name`. Jika migrasi tidak tepat, semua user bisa kehilangan akses.

**Koreksi:** Jalankan paralel dulu — `RoleMiddleware` lama tetap jalan, Spatie ditambahkan secara bertahap, baru hapus yang lama.

---

## ✅ 11 SESI YANG AMAN

Sesi-sesi ini **enterprise-standard DAN tidak mengubah logika bisnis:**

| Sesi | Status |
|------|--------|
| 1.4 — Fix Dashboard KPI | ✅ Hanya tambah data, tidak ubah logika |
| 2.2 — TransaksiService | ✅ Aman jika `DB::beginTransaction` tetap terjaga |
| 2.3 — SetoranService | ✅ Sama seperti 2.2 |
| 2.4 — RealisasiService | ✅ Sama seperti 2.2 |
| 2.6 — LaporanJob (Export) | ✅ Export tidak ubah data |
| 2.7 — Repository Pattern | ✅ Hanya abstraksi data access |
| 3.1 — Activity Log | ✅ Hanya tambah logging, tidak ubah logika |
| 3.2 — Unit Tests | ✅ Tidak ubah kode produksi |
| 3.3 — Feature Tests Auth | ✅ Tidak ubah kode produksi |
| 3.4 — Feature Tests Transaksi | ✅ Tidak ubah kode produksi |
| 3.5 — Telescope + Docs | ✅ Dev-only, tidak masuk produksi |

---

## 📋 RENCANA YANG DIREVISI

| Sesi | Nama | Status Lama | Status Baru |
|------|------|-------------|-------------|
| **1.1** | BaseController + Cache | Aman | ⚠️ **Harus investigasi menu seeder dulu** |
| **1.2** | SoftDeletes | Aman | 🔴 **Hanya untuk tabel aman, skip tunggakan & rugi_laba** |
| **1.3** | Audit Trail | Aman | ⚠️ **Semua kolom harus nullable** |
| **1.4** | Fix Dashboard | Aman | ✅ Tetap aman |
| **1.5** | Form Request | Aman | ⚠️ **Copy exact validasi, jangan ubah rules** |
| **1.6** | Pisah API Routes | Aman | 🔴 **BATALKAN — ganti strategi backward-compatible** |
| **2.1** | Spatie Permission | Aman | ⚠️ **Jalankan paralel dengan middleware lama** |
| **2.2** | TransaksiService | Aman | ✅ Tetap aman |
| **2.3** | SetoranService | Aman | ✅ Tetap aman |
| **2.4** | RealisasiService | Aman | ✅ Tetap aman |
| **2.5** | PullDataJob/Queue | Aman | ⚠️ **Butuh persetujuan perubahan UX** |
| **2.6** | LaporanJob | Aman | ✅ Tetap aman |
| **2.7** | Repository Pattern | Aman | ✅ Tetap aman |
| **3.1–3.5** | Testing & Monitor | Aman | ✅ Semua tetap aman |

---

## 🔑 PRINSIP KERJA YANG WAJIB DIPATUHI

Untuk setiap sesi yang dikerjakan, wajib mengikuti aturan ini:

```
1. BACA DULU kode yang ada sebelum mengubah
2. COPY EXACT logika bisnis — jangan "improve" validasi sembarangan
3. DB::beginTransaction tetap terjaga di tempat yang sama
4. Test manual setelah setiap sesi sebelum lanjut ke sesi berikutnya
5. Jangan ubah URL endpoint yang sudah ada (backward compatible)
6. Kolom baru selalu nullable jika tabelnya sudah berisi data
7. SoftDeletes hanya untuk tabel yang memang tidak boleh hard-delete
```

> [!CAUTION]
> Sesi 1.6 (pisah routes/api.php) di rencana sebelumnya **TIDAK AMAN** karena ada 85+ AJAX call dengan URL hardcoded. Jika dikerjakan tanpa mitigasi, akan mematikan hampir semua halaman transaksi.

> [!WARNING]
> SoftDeletes di Sesi 1.2 **TIDAK boleh dipasang** ke tabel `tunggakan`, `tabel_rugi_laba`, dan `pembiayaan_detail` karena tabel-tabel ini memang by-design di-clear/hard-delete dalam proses bisnis.

> [!IMPORTANT]
> Service Layer (Sesi 2.2, 2.3, 2.4) adalah **pekerjaan paling aman** karena hanya memindahkan kode, bukan mengubah logika. Ini prioritas utama Fase 2.
