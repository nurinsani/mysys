<?php

namespace Tests\Feature\Anggota;

use App\Exports\AnggotaExport;
use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang dan tiap test
 * di-rollback otomatis.
 */
class AnggotaExportTest extends TestCase
{
    use DatabaseTransactions;

    private function buatAnggotaMentah(string $unit, string $cif): void
    {
        $no = (string) random_int(100000000, 999999999);

        Anggota::create([
            'no' => $no, 'unit' => $unit, 'kode_kel' => 'KELTEST', 'norek' => $no,
            'tgl_join' => now(), 'cif' => $cif, 'nama' => 'ANGGOTA EXPORT TEST',
            'deal_type' => '1', 'alamat' => 'ALAMAT', 'desa' => 'DESA',
            'kecamatan' => 'KECAMATAN', 'kota' => 'KOTA', 'rtrw' => '001/001',
            'kode_pos' => '12345', 'no_hp' => '081200000000', 'hp_pasangan' => '081200000001',
            'kelamin' => 'L', 'tgl_lahir' => '1990-01-01', 'ktp' => (string) random_int(1000000000000000, 9999999999999999),
            'kewarganegaraan' => 'WNI', 'status_menikah' => 'KAWIN', 'agama' => 'ISLAM',
            'ibu_kandung' => 'IBU', 'npwp' => 0, 'source_income' => 1, 'pendidikan' => 'SMA',
            'tempat_lahir' => 'JAKARTA', 'id_expired' => 0, 'waris' => 'ANAK', 'cao' => 'CAO001',
            'cao_promotor' => 'CAO001', 'userid' => 1, 'status' => 'ANGGOTA',
            'pekerjaan_pasangan' => 'SWASTA',
        ]);
    }

    public function test_export_hanya_berisi_anggota_unit_yang_login(): void
    {
        $this->buatAnggotaMentah('001', 'CIFUNIT1');
        $this->buatAnggotaMentah('999', 'CIFUNIT9');

        $hasilUnit001 = (new AnggotaExport('001'))->query()->pluck('cif')->all();

        $this->assertContains('CIFUNIT1', $hasilUnit001);
        $this->assertNotContains('CIFUNIT9', $hasilUnit001, 'Export unit 001 tidak boleh berisi anggota unit lain.');
    }

    public function test_endpoint_export_anggota_berhasil(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('anggota.export'));

        $response->assertOk();
    }
}
