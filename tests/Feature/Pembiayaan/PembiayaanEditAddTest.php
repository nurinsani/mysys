<?php

namespace Tests\Feature\Pembiayaan;

use App\Models\Anggota;
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
class PembiayaanEditAddTest extends TestCase
{
    use DatabaseTransactions;

    private function buatAnggotaMentah(string $unit, string $cif): Anggota
    {
        $no = (string) random_int(100000000, 999999999);

        return Anggota::create([
            'no' => $no, 'unit' => $unit, 'kode_kel' => 'KELTEST', 'norek' => $no,
            'tgl_join' => now(), 'cif' => $cif, 'nama' => 'ANGGOTA PEMBIAYAAN TEST',
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

    private function buatTempAkadMusMentah(string $unit, string $cif, string $noAnggota): void
    {
        DB::table('temp_akad_mus')->insert([
            'buss_date' => now(), 'code_kel' => 'KELTEST', 'no_anggota' => $noAnggota,
            'cif' => $cif, 'nama' => 'PENDING TEST', 'deal_type' => '1', 'suffix' => '1',
            'bagi_hasil' => 0, 'tenor' => 1, 'plafond' => 1000000, 'os' => 1000000,
            'saldo_margin' => 0, 'angsuran' => 0, 'pokok' => 0, 'ijaroh' => 0, 'bulat' => 0,
            'run_tenor' => '0', 'ke' => '1', 'usaha' => 'DAGANG', 'nama_usaha' => 'WARUNG',
            'unit' => $unit, 'tgl_wakalah' => now(), 'tgl_akad' => now(), 'tgl_murab' => now(),
            'next_schedule' => now(), 'maturity_date' => now(), 'hari' => 'Senin',
            'cao' => 'CAO001', 'status' => 'ANGGOTA', 'status_usia' => 'NO',
            'status_app' => 'APPROVE', 'persen_margin' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    public function test_edit_pembiayaan_berhasil_diakses_untuk_cif_di_unit_sendiri(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $anggota = $this->buatAnggotaMentah($admin->unit, 'CIFEDIT1');

        $response = $this->get(route('pembiayaan.edit', $anggota->cif));

        $response->assertOk();
        $response->assertViewIs('admin.master_pembiayaan.edit');
    }

    public function test_edit_pembiayaan_redirect_jika_cif_milik_unit_lain(): void
    {
        $anggota = $this->buatAnggotaMentah('999', 'CIFEDIT9');

        $response = $this->get(route('pembiayaan.edit', $anggota->cif));

        $response->assertRedirect(route('pembiayaan.index'));
        $response->assertSessionHas('error');
    }

    public function test_add_pembiayaan_berhasil_dan_unit_userid_diambil_dari_auth_bukan_dari_input(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $anggota = $this->buatAnggotaMentah($admin->unit, 'CIFADD1');

        $param = DB::table('param_biaya')->first();
        $this->assertNotNull($param, 'Tidak ada data param_biaya untuk dipakai sample test.');

        $noRek = (string) random_int(100000000, 999999999);

        $payload = [
            // 'unit' dan 'id' SENGAJA diisi nilai palsu untuk membuktikan
            // controller tidak lagi mempercayai nilai ini dari client.
            'unit' => '999',
            'id' => 9999,
            'jenis_pembiayaan' => 1,
            'no_rek' => $noRek,
            'cif' => $anggota->cif,
            'pengajuan' => (int) $param->pla,
            'tenor' => (int) $param->jw,
            'disetujui' => (int) $param->pla,
            'tgl_wakalah' => '2027-01-04', // Senin, bukan Jumat
            'tgl_akad' => '2027-01-04',
            'bidang_usaha' => 'DAGANG',
            'keterangan_usaha' => 'WARUNG',
            'param_tanggal' => '2027-01-04',
            'cao' => 'CAO001',
            'kode_kel' => $anggota->kode_kel,
            'nama' => $anggota->nama,
            'tgl_lahir' => '1990-01-01',
        ];

        $response = $this->postJson(route('pembiayaan.add', $anggota->cif), $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $row = DB::table('temp_akad_mus')->where('no_anggota', $noRek)->first();
        $this->assertNotNull($row, 'Baris temp_akad_mus tidak ditemukan setelah addPembiayaan berhasil.');
        $this->assertSame($admin->unit, $row->unit, 'unit harus diambil dari Auth::user()->unit, bukan dari input client.');
        $this->assertSame((string) $admin->id, (string) $row->userid, 'userid harus diambil dari Auth::id(), bukan dari input client.');
    }

    public function test_add_pembiayaan_gagal_validasi_jika_field_wajib_kosong(): void
    {
        $response = $this->postJson(route('pembiayaan.add', 'ANYCIF'), []);

        $response->assertStatus(422);
    }

    public function test_data_tanpa_kode_kelompok_menampilkan_pengajuan_pending_unit_sendiri(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $noAnggota = (string) random_int(100000000, 999999999);
        $this->buatTempAkadMusMentah($admin->unit, 'CIFPEND1', $noAnggota);

        $response = $this->getJson(route('pembiayaan.data'));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $response->assertJsonFragment(['anggota_cif' => 'CIFPEND1']);
    }

    public function test_data_tanpa_kode_kelompok_tidak_menampilkan_pengajuan_pending_unit_lain(): void
    {
        $this->buatTempAkadMusMentah('999', 'CIFPEND9', (string) random_int(100000000, 999999999));

        $response = $this->getJson(route('pembiayaan.data'));

        $response->assertOk();
        $response->assertJsonMissing(['anggota_cif' => 'CIFPEND9']);
    }

    public function test_akses_pembiayaan_edit_mencatat_cif_di_activity_log(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $anggota = $this->buatAnggotaMentah($admin->unit, 'CIFLOG1');

        $this->get(route('pembiayaan.edit', $anggota->cif));

        $log = DB::table('activity_log')
            ->where('log_name', 'akses')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('(CIF: CIFLOG1)', $log->description);
        $properties = json_decode($log->properties, true);
        $this->assertSame('CIFLOG1', $properties['cif'] ?? null);
    }
}
