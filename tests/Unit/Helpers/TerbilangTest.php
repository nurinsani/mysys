<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TerbilangTest extends TestCase
{
    #[DataProvider('angkaProvider')]
    public function test_terbilang_menghasilkan_teks_yang_benar(int $angka, string $harapan): void
    {
        $this->assertSame($harapan, terbilang($angka));
    }

    public static function angkaProvider(): array
    {
        return [
            'nol' => [0, ' '],
            'satu' => [1, ' Satu'],
            'sepuluh' => [10, ' Sepuluh'],
            'sebelas' => [11, ' Sebelas'],
            'dua belas' => [12, ' Dua Belas'],
            'sembilan belas' => [19, ' Sembilan Belas'],
            'dua puluh' => [20, ' Dua Puluh '],
            'dua puluh satu' => [21, ' Dua Puluh Satu'],
            'sembilan puluh sembilan' => [99, ' Sembilan Puluh Sembilan'],
            'seratus' => [100, ' Seratus '],
            'seratus satu' => [101, ' Seratus Satu'],
            'seratus sembilan puluh sembilan' => [199, ' Seratus Sembilan Puluh Sembilan'],
            'dua ratus' => [200, ' Dua Ratus '],
            'sembilan ratus sembilan puluh sembilan' => [999, ' Sembilan Ratus Sembilan Puluh Sembilan'],
            'seribu' => [1000, ' Seribu '],
            'seribu satu' => [1001, ' Seribu Satu'],
            'seribu sembilan ratus sembilan puluh sembilan' => [1999, ' Seribu Sembilan Ratus Sembilan Puluh Sembilan'],
            'dua ribu' => [2000, ' Dua Ribu '],
            'lima belas ribu' => [15000, ' Lima Belas Ribu '],
            'sembilan ratus sembilan puluh sembilan ribu sembilan ratus sembilan puluh sembilan' => [999999, ' Sembilan Ratus Sembilan Puluh Sembilan Ribu Sembilan Ratus Sembilan Puluh Sembilan'],
            'satu juta' => [1000000, ' Satu Juta '],
        ];
    }

    public function test_nilai_negatif_menghasilkan_teks_yang_sama_dengan_nilai_absolutnya(): void
    {
        $this->assertSame(terbilang(50), terbilang(-50));
    }
}
