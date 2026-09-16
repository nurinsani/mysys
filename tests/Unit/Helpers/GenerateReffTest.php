<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class GenerateReffTest extends TestCase
{
    public function test_format_reff_unit_dipad_4_karakter_diikuti_ulid_26_karakter(): void
    {
        $reff = generate_reff('001');

        $this->assertSame(30, strlen($reff));
        $this->assertSame('0001', substr($reff, 0, 4));
    }

    public function test_unit_yang_sudah_4_karakter_tidak_dipotong(): void
    {
        $reff = generate_reff('0012');

        $this->assertSame('0012', substr($reff, 0, 4));
        $this->assertSame(30, strlen($reff));
    }

    public function test_dua_panggilan_berturut_turut_menghasilkan_reff_berbeda(): void
    {
        $reffs = [];
        foreach (range(1, 50) as $i) {
            $reffs[] = generate_reff('001');
        }

        $this->assertCount(50, array_unique($reffs), 'Semua reff dari 50 panggilan berturut-turut harus unik, tidak boleh ada tabrakan.');
    }

    public function test_reff_sortable_kronologis_untuk_unit_yang_sama(): void
    {
        $reffPertama = generate_reff('001');
        usleep(2000); // pastikan beda milidetik
        $reffKedua = generate_reff('001');

        $terurut = [$reffKedua, $reffPertama];
        sort($terurut);

        $this->assertSame([$reffPertama, $reffKedua], $terurut, 'reff yang dibuat lebih dulu harus terurut lebih dulu secara string sort.');
    }

    public function test_reff_terurut_per_unit_dulu_baru_kronologis(): void
    {
        $reffUnit2 = generate_reff('002');
        $reffUnit1 = generate_reff('001');

        $terurut = [$reffUnit2, $reffUnit1];
        sort($terurut);

        $this->assertSame([$reffUnit1, $reffUnit2], $terurut, 'reff unit 001 harus terurut sebelum unit 002, apapun urutan waktu pembuatannya.');
    }
}
