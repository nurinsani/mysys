<?php

use Illuminate\Support\Str;

function tambah_nol_didepan($value, $threshold = null)
{
    return sprintf("%0". $threshold . "s", $value);
}

/**
 * Reff sortable per unit lalu kronologis: prefix unit (dipad ke lebar kolom
 * `unit`, 4 karakter) diikuti ULID (26 karakter, urut leksikografis =
 * kronologis presisi milidetik). Dipakai sebagai primary key varchar(191)
 * pada tabel simpanan/simpanan_pokok/simpanan_wajib — menggantikan format
 * lama (unit + date('YmdHis') + 2 huruf acak) yang terbukti rawan tabrakan
 * (~23% pada proses batch) dan, di beberapa tempat lain, tidak pernah diisi
 * sama sekali walau kolomnya NOT NULL tanpa default.
 */
function generate_reff(string $unit): string
{
    return str_pad($unit, 4, '0', STR_PAD_LEFT) . (string) Str::ulid();
}

function terbilang ($angka) {
    $angka = abs($angka);
    $baca  = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    $terbilang = '';

    if ($angka < 12) {
        $terbilang = ' ' . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka -10) . ' Belas';
    } elseif ($angka < 100) {
        $terbilang = terbilang($angka / 10) . ' Puluh' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = ' Seratus' . terbilang($angka -100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang($angka / 100) . ' Ratus' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = ' Seribu' . terbilang($angka -1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang($angka / 1000) . ' Ribu' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang($angka / 1000000) . ' Juta' . terbilang($angka % 1000000);
    }

    return $terbilang;
}