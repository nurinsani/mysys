<?php

namespace Tests\Feature\Report;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang dan tiap test
 * di-rollback otomatis.
 */
class ReportPpapTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    public function test_halaman_report_ppap_bisa_diakses(): void
    {
        $response = $this->get('/report/ppap');

        $response->assertOk();
        $response->assertViewIs('admin.report_ppap.index');
    }

    public function test_cari_ppap_semua_kategori(): void
    {
        $response = $this->postJson('/report/ppap/cari', ['jenis_kolek' => 'semua']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data', 'jenis_kolek']);
    }

    public function test_cari_ppap_gagal_validasi_tanpa_jenis_kolek(): void
    {
        $response = $this->postJson('/report/ppap/cari', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jenis_kolek']);
    }

    public function test_export_pdf_ppap_berhasil(): void
    {
        $response = $this->get('/report/ppap/export/pdf?' . http_build_query([
            'tanggal_cetak' => date('Y-m-d'),
            'jenis_kolek' => 'semua',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_export_excel_ppap_berhasil(): void
    {
        $response = $this->get('/report/ppap/export/excel?' . http_build_query([
            'tanggal_cetak' => date('Y-m-d'),
            'jenis_kolek' => 'semua',
        ]));

        $response->assertOk();
    }

    /**
     * Regresi: debitur dengan ft persis 12 dulu masuk kategori "diragukan"
     * (BETWEEN 7 AND 12) SEKALIGUS "macet" (>= 12) — dobel hitung PPAP.
     * Sekarang batas diragukan dipersempit ke 7-11, macet tetap >= 12.
     */
    public function test_debitur_ft_12_hanya_masuk_macet_tidak_diragukan(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $unit = $admin->unit;

        $kelompokAo = DB::table('kelompok')
            ->join('ao', 'kelompok.cao', '=', 'ao.cao')
            ->where('kelompok.code_unit', $unit)
            ->select('kelompok.code_kel', 'ao.cao')
            ->first() ?? DB::table('kelompok')
            ->join('ao', 'kelompok.cao', '=', 'ao.cao')
            ->select('kelompok.code_kel', 'ao.cao')
            ->first();
        $this->assertNotNull($kelompokAo, 'Tidak ada pasangan kelompok+ao di database untuk dipakai sebagai sample test.');

        $baselineDiragukan = $this->postJson('/report/ppap/cari', ['jenis_kolek' => 'diragukan'])->json('data.0.total_noa');
        $baselineMacet = $this->postJson('/report/ppap/cari', ['jenis_kolek' => 'macet'])->json('data.0.total_noa');

        $cif = 'F' . substr(uniqid(), -9);
        DB::table('pembiayaan')->insert([
            'buss_date' => now(), 'code_kel' => $kelompokAo->code_kel, 'no_anggota' => 'TEST-' . $cif,
            'cif' => $cif, 'nama' => 'Test FT12', 'deal_type' => '1', 'suffix' => '1',
            'bagi_hasil' => 0, 'tenor' => 10, 'plafond' => 500000, 'os' => 800000,
            'saldo_margin' => 0, 'angsuran' => 50000, 'pokok' => 50000, 'ijaroh' => 0,
            'bulat' => 50000, 'run_tenor' => '1', 'ke' => '1', 'usaha' => '1',
            'nama_usaha' => 'Test', 'unit' => $unit, 'tgl_wakalah' => now(), 'tgl_akad' => now(),
            'tgl_murab' => now(), 'next_schedule' => now(), 'maturity_date' => now(),
            'last_payment' => now(), 'hari' => 'Senin', 'cao' => $kelompokAo->cao,
            'status' => 'ANGGOTA', 'status_usia' => 'NO', 'status_app' => 'APPROVE', 'gol' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 12; $i++) {
            DB::table('tunggakan')->insert([
                'tgl_tunggak' => now()->subDays($i)->format('Y-m-d'), 'norek' => 'TEST',
                'unit' => $unit, 'cif' => $cif, 'code_kel' => $kelompokAo->code_kel,
                'debet' => 0, 'type' => '04', 'kredit' => 10000, 'userid' => 1,
                'ket' => 'Test tunggakan ft12', 'cao' => $kelompokAo->cao, 'blok' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $diragukanSesudah = $this->postJson('/report/ppap/cari', ['jenis_kolek' => 'diragukan'])->json('data.0.total_noa');
        $macetSesudah = $this->postJson('/report/ppap/cari', ['jenis_kolek' => 'macet'])->json('data.0.total_noa');

        $this->assertSame($baselineDiragukan, $diragukanSesudah, 'Debitur ft=12 tidak boleh menambah jumlah kategori diragukan.');
        $this->assertSame($baselineMacet + 1, $macetSesudah, 'Debitur ft=12 harus menambah jumlah kategori macet tepat 1.');
    }
}
