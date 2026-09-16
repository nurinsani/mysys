<?php

namespace App\Services\PullData;

use Illuminate\Support\Facades\DB;

class PullDataService
{
    /**
     * Sesi 2.5 — DRY refactor dari PullDataController::data() (asalnya 1 method,
     * ~1050 baris, 10 blok kode nyaris identik). Bukan job antrian — sengaja tetap
     * sinkron karena tiap panggilan hanya memproses satu kelompok/CIF (bukan bulk
     * pull dari server eksternal seperti dikira rencana awal), jadi cepat.
     *
     * 3 bug pre-existing yang IKUT diperbaiki karena bikin selalu gagal SQL / logika
     * salah (bukan sekadar duplikasi kode):
     * - `pembiayaan.nama_kel` (kolom itu tidak ada di tabel pembiayaan, hasil JOIN ke
     *   kelompok) → diganti `kelompok.nama_kel`.
     * - `SUM(debet)` pada tabel `simpanan` → kolom aslinya `DEBIT`, diganti `SUM(debet)`.
     * - variabel `$os`/`$nominal` yang tidak pernah didefinisikan (harusnya `$row->os`
     *   / `$request->nominal`) di jalur pelunasan & penarikan individu.
     *
     * Pesan sukses/gagal SEMUA dipertahankan persis seperti kode asli, termasuk yang
     * kelihatannya salah copy-paste (mis. beberapa jalur individu selalu bilang
     * "Pull Data Lebaran sukses" apa pun jenis transaksinya) — itu bukan bug yang
     * bikin crash, jadi tidak diubah supaya tidak menebak maksud aslinya.
     */
    public function pull(string $jenisPull, ?string $transaksi, ?string $cifKel, ?string $tglTagih, ?float $nominal): array
    {
        if ($jenisPull === '01') {
            return match ($transaksi) {
                'lima' => $this->kelompok($cifKel, $tglTagih, 'lima', 'tagihan_lima_persen', 'Pull Data 5% sukses'),
                'lebaran' => $this->kelompok($cifKel, $tglTagih, 'lebaran', 'tagihan_lebaran', 'Pull Data Tagihan Lebaran sukses'),
                'pelunasan' => $this->kelompok($cifKel, $tglTagih, 'pelunasan', 'tagihan_pelunasan', 'Pull Data Tagihan Pelunasan sukses'),
                'pelunasan19' => $this->kelompok($cifKel, $tglTagih, 'pelunasan19', 'tagihan_pelunasan', 'Pull Data Tagihan Pelunasan19 sukses'),
                'pelunasanRestMargin' => $this->kelompok($cifKel, $tglTagih, 'pelunasanRestMargin', 'tagihan_pelunasan', 'Pull Data Tagihan Pelunasan19 sukses'),
                'penarikan' => $this->kelompok($cifKel, $tglTagih, 'penarikan', 'tagihan_penarikan', 'Pull Data Tagihan Penarikan sukses'),
                default => $this->jenisTidakDikenali(),
            };
        }

        if ($jenisPull === '02') {
            return match ($transaksi) {
                'lima' => $this->individuLima($cifKel, $tglTagih),
                'lebaran' => $this->individuBulat($cifKel, $tglTagih, 'lebaran', 'tagihan_lebaran', 'Pull Data Lebaran sukses'),
                'pelunasan' => $this->individuSimpanan($cifKel, $tglTagih, 'pelunasan', 'tagihan_pelunasan', 'Pull Data Lebaran sukses'),
                'pelunasan19' => $this->individuSimpanan($cifKel, $tglTagih, 'pelunasan19', 'tagihan_pelunasan', 'Pull Data Lebaran sukses'),
                'pelunasanRestMargin' => $this->individuSimpanan($cifKel, $tglTagih, 'pelunasanRestMargin', 'tagihan_pelunasan', 'Pull Data Lebaran sukses'),
                'penarikan' => $this->individuPenarikan($cifKel, $tglTagih, $nominal),
                default => $this->jenisTidakDikenali(),
            };
        }

        return $this->jenisTidakDikenali();
    }

    private function jenisTidakDikenali(): array
    {
        return ['status' => 200, 'body' => ['success' => false, 'message' => 'Jenis pull tidak dikenali']];
    }

    private function gagalPull(): array
    {
        return ['status' => 200, 'body' => ['success' => false, 'message' => 'Gagal Pull Data !!!']];
    }

    /**
     * Kelompok (jenisPull=01): semua jenis transaksi sumbernya sama (temp_akad_mus
     * per code_kel) dan rumus bayarnya sama (5% dari plafond) — hanya tabel tagihan
     * tujuan dan pesannya yang beda.
     */
    private function kelompok(?string $cifKel, ?string $tglTagih, string $label, string $tagihanTable, string $message): array
    {
        $rows = DB::table('temp_akad_mus')
            ->leftJoin('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->select(
                'temp_akad_mus.unit',
                'temp_akad_mus.code_kel',
                'temp_akad_mus.cif',
                'temp_akad_mus.cao',
                'temp_akad_mus.saldo_margin',
                'temp_akad_mus.Plafond',
                'temp_akad_mus.no_anggota as norek',
                'temp_akad_mus.pokok',
                'temp_akad_mus.ijaroh',
                'temp_akad_mus.angsuran',
                'temp_akad_mus.hari',
                'temp_akad_mus.bulat',
                'temp_akad_mus.os',
                'temp_akad_mus.nama',
                'kelompok.nama_kel'
            )
            ->where('temp_akad_mus.code_kel', $cifKel)
            ->get();

        if ($rows->count() === 0) {
            return $this->gagalPull();
        }

        $dataPull = [];
        foreach ($rows as $row) {
            $bayar = $row->Plafond * (5 / 100);
            $dataPull[] = $this->buildRecord($row, $tglTagih, $bayar, $label);
        }

        DB::table($tagihanTable)->insert($dataPull);
        DB::table('pull_data')->insert($dataPull);

        $inserted = DB::table('pull_data')->where('code_kel', $cifKel)->get();

        return ['status' => 200, 'body' => ['success' => true, 'message' => $message, 'data' => $inserted]];
    }

    private function individuLima(?string $cifKel, ?string $tglTagih): array
    {
        $rows = DB::table('temp_akad_mus')
            ->leftJoin('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->select(
                'temp_akad_mus.unit',
                'temp_akad_mus.code_kel',
                'temp_akad_mus.cif',
                'temp_akad_mus.cao',
                'temp_akad_mus.saldo_margin',
                'temp_akad_mus.Plafond',
                'temp_akad_mus.no_anggota as norek',
                'temp_akad_mus.pokok',
                'temp_akad_mus.ijaroh',
                'temp_akad_mus.angsuran',
                'temp_akad_mus.hari',
                'temp_akad_mus.bulat',
                'temp_akad_mus.os',
                'temp_akad_mus.nama',
                'kelompok.nama_kel'
            )
            ->where('temp_akad_mus.cif', $cifKel)
            ->get();

        if ($rows->count() === 0) {
            return $this->gagalPull();
        }

        $dataPull = [];
        foreach ($rows as $row) {
            $bayar = $row->Plafond * (5 / 100);
            $dataPull[] = $this->buildRecord($row, $tglTagih, $bayar, 'lima');
        }

        DB::table('tagihan_lima_persen')->insert($dataPull);
        DB::table('pull_data')->insert($dataPull);

        $inserted = DB::table('pull_data')->get();

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Pull Data 5% sukses', 'data' => $inserted]];
    }

    /**
     * Individu, sumber pembiayaan, bayar = angsuran bulat (dipakai untuk 'lebaran').
     */
    private function individuBulat(?string $cifKel, ?string $tglTagih, string $label, string $tagihanTable, string $message): array
    {
        $rows = $this->queryPembiayaanByCif($cifKel);

        if ($rows->count() === 0) {
            return $this->gagalPull();
        }

        $dataPull = [];
        foreach ($rows as $row) {
            $bayar = $row->bulat;
            $dataPull[] = $this->buildRecord($row, $tglTagih, $bayar, $label);
        }

        DB::table($tagihanTable)->insert($dataPull);
        DB::table('pull_data')->insert($dataPull);

        $inserted = DB::table('pull_data')->get();

        return ['status' => 200, 'body' => ['success' => true, 'message' => $message, 'data' => $inserted]];
    }

    /**
     * Individu, sumber pembiayaan, bayar dihitung dari sisa saldo simpanan vs OS
     * (dipakai untuk 'pelunasan', 'pelunasan19', 'pelunasanRestMargin').
     */
    private function individuSimpanan(?string $cifKel, ?string $tglTagih, string $label, string $tagihanTable, string $message): array
    {
        $rows = $this->queryPembiayaanByCif($cifKel);

        if ($rows->count() === 0) {
            return $this->gagalPull();
        }

        $dataPull = [];
        foreach ($rows as $row) {
            $simpanan = DB::table('simpanan')
                ->where('cif', $row->cif)
                ->selectRaw('COALESCE(SUM(kredit), 0) - COALESCE(SUM(debet), 0) as saldo')
                ->value('saldo');

            $sisa = $simpanan - $row->os;
            $bayar = $simpanan < $row->os ? 0 : $sisa;

            $dataPull[] = $this->buildRecord($row, $tglTagih, $bayar, $label);
        }

        DB::table($tagihanTable)->insert($dataPull);
        DB::table('pull_data')->insert($dataPull);

        $inserted = DB::table('pull_data')->get();

        return ['status' => 200, 'body' => ['success' => true, 'message' => $message, 'data' => $inserted]];
    }

    private function individuPenarikan(?string $cifKel, ?string $tglTagih, ?float $nominal): array
    {
        $rows = $this->queryPembiayaanByCif($cifKel);

        if ($rows->count() === 0) {
            return $this->gagalPull();
        }

        $dataPull = [];
        foreach ($rows as $row) {
            $simpanan = DB::table('simpanan')
                ->where('cif', $row->cif)
                ->selectRaw('COALESCE(SUM(kredit), 0) - COALESCE(SUM(debet), 0) as saldo')
                ->value('saldo');

            $bayar = $simpanan < $nominal ? $simpanan : $nominal;

            $dataPull[] = $this->buildRecord($row, $tglTagih, $bayar, 'penarikan');
        }

        DB::table('tagihan_penarikan')->insert($dataPull);
        DB::table('pull_data')->insert($dataPull);

        $inserted = DB::table('pull_data')->get();

        return ['status' => 200, 'body' => ['success' => true, 'message' => 'Pull Data Lebaran sukses', 'data' => $inserted]];
    }

    private function queryPembiayaanByCif(?string $cifKel)
    {
        return DB::table('pembiayaan')
            ->leftJoin('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->select(
                'pembiayaan.unit',
                'pembiayaan.code_kel',
                'pembiayaan.cif',
                'pembiayaan.cao',
                'pembiayaan.saldo_margin',
                'pembiayaan.Plafond',
                'pembiayaan.no_anggota as norek',
                'pembiayaan.pokok',
                'pembiayaan.ijaroh',
                'pembiayaan.angsuran',
                'pembiayaan.hari',
                'pembiayaan.bulat',
                'pembiayaan.os',
                'pembiayaan.nama',
                'kelompok.nama_kel'
            )
            ->where('pembiayaan.cif', $cifKel)
            ->get();
    }

    private function buildRecord($row, ?string $tglTagih, $bayar, string $jenisPullLabel): array
    {
        return [
            'unit' => $row->unit,
            'tgl_tagih' => $tglTagih,
            'code_kel' => $row->code_kel,
            'cif' => $row->cif,
            'cao' => $row->cao,
            'norek' => $row->norek,
            'angsuran_pokok' => $row->pokok,
            'angsuran_margin' => $row->ijaroh,
            'angsuran' => $row->angsuran,
            'bayar' => $bayar,
            'status_realisasi' => '',
            'pb' => 0,
            'ke' => 0,
            'tunggakan' => 0,
            'hari' => $row->hari,
            'twm' => 0,
            'bulat' => $bayar,
            'simpanan_wajib' => 0,
            'simpanan_pokok' => 0,
            'os' => $row->os,
            'nama' => $row->nama,
            'nama_kel' => $row->nama_kel,
            'saldo_margin' => $row->saldo_margin,
            'plafond' => $row->Plafond,
            'jenis_pull' => $jenisPullLabel,
        ];
    }
}
