<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| File ini otomatis diberi prefix "/api" dan middleware group "api" oleh
| bootstrap/app.php. Konvensi: bungkus semua endpoint baru dengan
| Route::prefix('v1') supaya URL akhirnya /api/v1/....
|
| PENTING: endpoint JSON yang SUDAH ADA di routes/web.php sengaja TIDAK
| dipindah ke sini. Ada 85+ pemanggilan AJAX di blade view yang memakai
| URL hardcoded (bukan route('...')) — memindah URL-nya berisiko mematikan
| halaman transaksi. Endpoint lama tetap di web.php sampai blade view-nya
| dirapikan satu per satu ke named route. File ini hanya untuk endpoint
| BARU ke depannya.
|
*/

Route::prefix('v1')->group(function () {
    //
});
