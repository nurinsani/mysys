<?php

namespace Tests\Feature\Anggota;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang (skema live sudah
 * menyimpang dari file migration di beberapa tempat) dan tiap test
 * di-rollback otomatis.
 */
class AnggotaStoreTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    private function dataAnggotaValid(array $override = []): array
    {
        return array_merge([
            'cao' => 'CAO001',
            'kode_kel' => 'KELTEST',
            'cif' => 'TESTCIF',
            'nama' => 'Test Nama',
            'alamat' => 'Alamat Test',
            'rtrw' => '001/002',
            'desa' => 'Desa Test',
            'kecamatan' => 'Kecamatan Test',
            'kota' => 'Kota Test',
            'kode_pos' => '12345',
            'tgl_lahir' => '1990-01-01',
            'ktp' => '9999888877776666',
            'kelamin' => 'L',
            'kewarganegaraan' => 'WNI',
            'status_menikah' => 'Kawin',
            'agama' => 'Islam',
            'no_hp' => '081234567890',
            'hp_pasangan' => '081234567891',
            'ibu_kandung' => 'Ibu Test',
            'pendidikan' => 'SMA',
            'tempat_lahir' => 'Jakarta',
            'waris' => 'Anak',
            'pekerjaan_pasangan' => 'Swasta',
        ], $override);
    }

    public function test_store_anggota_baru_berhasil(): void
    {
        $response = $this->post(route('anggota.store'), $this->dataAnggotaValid());

        $response->assertRedirect(route('anggota.index'));
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('anggota', [
            'nama' => 'TEST NAMA',
            'ktp' => '9999888877776666',
            'cao_promotor' => 'CAO001',
        ]);
    }

    public function test_store_anggota_menyimpan_kelamin_sesuai_input_bukan_selalu_p(): void
    {
        $response = $this->post(route('anggota.store'), $this->dataAnggotaValid([
            'ktp' => '1111222233334444',
            'kelamin' => 'L',
        ]));

        $response->assertRedirect(route('anggota.index'));

        $this->assertDatabaseHas('anggota', [
            'ktp' => '1111222233334444',
            'kelamin' => 'L',
        ]);
    }

    public function test_store_anggota_gagal_validasi_jika_kelamin_kosong(): void
    {
        $response = $this->post(route('anggota.store'), $this->dataAnggotaValid([
            'ktp' => '5555666677778888',
            'kelamin' => '',
        ]));

        $response->assertSessionHasErrors(['kelamin']);
        $this->assertDatabaseMissing('anggota', ['ktp' => '5555666677778888']);
    }

    /**
     * `no` sengaja dibuat murni angka (meniru format produksi unit+tanggal+urut,
     * bukan huruf dari uniqid()) — LogsActivity (Spatie) menyimpan primary key
     * model ke kolom `activity_log.subject_id` yang bertipe BIGINT, jadi `no`
     * non-angka akan gagal di situ (di luar scope perbaikan menu ini, dicatat
     * terpisah di rencana_pengerjaan.md).
     */
    private function buatAnggotaMentah(array $override = []): Anggota
    {
        $no = (string) random_int(100000000, 999999999);

        return Anggota::create(array_merge([
            'no' => $no,
            'unit' => '001',
            'kode_kel' => 'KELTEST',
            'norek' => $no,
            'tgl_join' => now(),
            'cif' => 'C' . substr(uniqid(), -7),
            'nama' => 'ANGGOTA MENTAH',
            'deal_type' => '1',
            'alamat' => 'ALAMAT',
            'desa' => 'DESA',
            'kecamatan' => 'KECAMATAN',
            'kota' => 'KOTA',
            'rtrw' => '001/001',
            'kode_pos' => '12345',
            'no_hp' => '081200000000',
            'hp_pasangan' => '081200000001',
            'kelamin' => 'L',
            'tgl_lahir' => '1990-01-01',
            'ktp' => '1234567890123456',
            'kewarganegaraan' => 'WNI',
            'status_menikah' => 'KAWIN',
            'agama' => 'ISLAM',
            'ibu_kandung' => 'IBU',
            'npwp' => 0,
            'source_income' => 1,
            'pendidikan' => 'SMA',
            'tempat_lahir' => 'JAKARTA',
            'id_expired' => 0,
            'waris' => 'ANAK',
            'cao' => 'CAO001',
            'cao_promotor' => 'CAO001',
            'userid' => 1,
            'status' => 'ANGGOTA',
            'pekerjaan_pasangan' => 'SWASTA',
        ], $override));
    }

    public function test_no_anggota_sequence_di_scope_per_unit_bukan_dari_record_global(): void
    {
        // Anggota unit '001' (sequence terakhir '005') dibuat lebih dulu, LALU
        // anggota unit LAIN ('999', suffix '999', created_at lebih baru) —
        // kalau bug lama (Anggota::latest() tanpa scope unit) masih ada, admin
        // unit '001' akan salah mewarisi sequence dari unit 999 (999+1=1000)
        // padahal seharusnya lanjut dari 005 -> 006.
        $this->buatAnggotaMentah(['unit' => '001', 'no' => '001' . now()->format('ymd') . '005']);
        $this->buatAnggotaMentah(['unit' => '999', 'no' => '999999999']);

        $response = $this->post(route('anggota.store'), $this->dataAnggotaValid([
            'ktp' => '2222333344445555',
        ]));

        $response->assertRedirect(route('anggota.index'));

        $anggotaBaru = Anggota::where('ktp', '2222333344445555')->first();
        $this->assertNotNull($anggotaBaru);
        $this->assertSame('006', substr($anggotaBaru->no, -3), 'Sequence harus lanjut dari anggota unit 001 terakhir (005 -> 006), tidak boleh terpengaruh suffix 999 milik unit lain.');
    }

    public function test_store_ktp_boleh_duplikat_lintas_unit_berbeda(): void
    {
        $this->buatAnggotaMentah(['unit' => '999', 'ktp' => '3333444455556666']);

        $response = $this->post(route('anggota.store'), $this->dataAnggotaValid([
            'ktp' => '3333444455556666',
        ]));

        $response->assertRedirect(route('anggota.index'));
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_store_ktp_gagal_jika_duplikat_di_unit_yang_sama(): void
    {
        $this->buatAnggotaMentah(['unit' => '001', 'ktp' => '4444555566667777']);

        $response = $this->postJson(route('anggota.store'), $this->dataAnggotaValid([
            'ktp' => '4444555566667777',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ktp']);
        $this->assertSame('NIK sudah terdaftar di unit ini.', $response->json('errors.ktp.0'));
    }

    public function test_update_anggota_gagal_jika_data_tidak_valid(): void
    {
        $anggota = $this->buatAnggotaMentah();

        $response = $this->postJson(route('anggota.update', $anggota->no), array_merge(
            $this->dataAnggotaValid(),
            ['_method' => 'PUT', 'kelamin' => 'X', 'ktp' => $anggota->ktp]
        ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kelamin']);
    }

    public function test_update_anggota_tidak_gagal_karena_ktp_milik_sendiri(): void
    {
        $anggota = $this->buatAnggotaMentah(['ktp' => '5555666677778889']);

        $response = $this->post(route('anggota.update', $anggota->no), array_merge(
            $this->dataAnggotaValid(),
            ['_method' => 'PUT', 'ktp' => '5555666677778889']
        ));

        $response->assertRedirect(route('anggota.index'));
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_update_anggota_gagal_jika_ktp_diganti_jadi_milik_anggota_lain_di_unit_sama(): void
    {
        $this->buatAnggotaMentah(['ktp' => '6666777788889999']);
        $anggotaDiedit = $this->buatAnggotaMentah(['ktp' => '7777888899990000']);

        $response = $this->postJson(route('anggota.update', $anggotaDiedit->no), array_merge(
            $this->dataAnggotaValid(),
            ['_method' => 'PUT', 'ktp' => '6666777788889999']
        ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ktp']);
    }

    public function test_update_anggota_mencatat_cif_di_activity_log(): void
    {
        $anggota = $this->buatAnggotaMentah(['cao' => 'CAO001']);

        $response = $this->post(route('anggota.update', $anggota->no), array_merge(
            $this->dataAnggotaValid(['cao' => 'CAO999', 'cif' => $anggota->cif]),
            ['_method' => 'PUT', 'ktp' => $anggota->ktp]
        ));

        $response->assertRedirect(route('anggota.index'));
        $response->assertSessionDoesntHaveErrors();

        $log = DB::table('activity_log')
            ->where('subject_type', Anggota::class)
            ->where('log_name', 'default')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Activity log untuk update Anggota tidak ditemukan.');
        $properties = json_decode($log->properties, true);
        $this->assertSame(strtoupper($anggota->cif), $properties['cif'] ?? null, 'Properties activity log harus menyertakan CIF anggota yang diubah.');
        $this->assertSame($anggota->no, $properties['no_anggota'] ?? null, 'Properties activity log harus menyertakan no_anggota lengkap (bukan subject_id yang leading zero-nya hilang).');
    }
}
