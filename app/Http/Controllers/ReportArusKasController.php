<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportArusKasController extends BaseController
{
    private function getKategori(): array
    {
        return DB::table('arus_kas_kategori')
            ->orderBy('urutan')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->code_arus_kas => [
                'line' => $row->line,
                'nama' => $row->nama,
                'group_heading' => $row->group_heading,
            ]])
            ->all();
    }

    private const EXCLUDED_GR_HEAD = ['1100000', '1300000'];
    private const EXCLUDED_KODE = [
        '1481000', '1421000', '1423000', '1512000', '1913000', '1962000', '1963000', '2472000', '9141000', '9910000',
        '1621000', '1672000', '52911', '52915', '1949000',
    ];

    public function index()
    {
        $title = 'Laporan Arus Kas';
        $menus = $this->getMenus();
        $unit = Auth::user()->unit;

        $rows = DB::table('tabel_master_kas')
            ->where('unit', $unit)
            ->orderBy('gl_arus_kas')
            ->get()
            ->keyBy('code_arus_kas');

        $periode = $rows->first();

        return view('admin.report_arus_kas.index', [
            'title' => $title,
            'menus' => $menus,
            'kategori' => $this->getKategori(),
            'rows' => $rows,
            'periodeAwal' => $periode->periode_awal ?? null,
            'periodeAkhir' => $periode->periode_akhir ?? null,
            'rekonsiliasi' => null,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
        ]);

        $unit = Auth::user()->unit;
        $tanggalAwal = $request->tanggal_awal;
        $tanggalAkhir = $request->tanggal_akhir;

        $aggregat = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->whereBetween('tanggal_transaksi', ["{$tanggalAwal} 00:00:00", "{$tanggalAkhir} 23:59:59"])
            ->select('kode_rekening', DB::raw('SUM(debet) as tot_debet'), DB::raw('SUM(kredit) as tot_kredit'))
            ->groupBy('kode_rekening')
            ->get();

        $mappings = DB::table('coa_arus_kas_mappings')->get();
        $kodeRules = $mappings->where('match_type', 'kode_rekening')->groupBy('match_value');
        $prefixRules = $mappings->where('match_type', 'prefix');
        $grHeadRules = $mappings->where('match_type', 'gr_head')->groupBy('match_value');
        $coaGrHead = DB::table('coa')->whereNotNull('gr_head')->where('gr_head', '!=', '')->pluck('gr_head', 'kode_rek');

        $kategoriTotals = [];
        $unclassifiedDebet = 0;
        $unclassifiedKredit = 0;
        $kasDebet = 0;
        $kasKredit = 0;

        foreach ($aggregat as $row) {
            $kode = $row->kode_rekening;
            $grHead = $coaGrHead[$kode] ?? null;

            if (in_array($grHead, self::EXCLUDED_GR_HEAD, true)) {
                $kasDebet += $row->tot_debet;
                $kasKredit += $row->tot_kredit;
                continue;
            }

            if (in_array($kode, self::EXCLUDED_KODE, true)) {
                continue;
            }

            $rules = $kodeRules->get($kode);

            if (!$rules) {
                foreach ($prefixRules as $prefixRule) {
                    if (str_starts_with($kode, $prefixRule->match_value)) {
                        $rules = collect([$prefixRule]);
                        break;
                    }
                }
            }

            if (!$rules && $grHead && $grHeadRules->has($grHead)) {
                $rules = $grHeadRules->get($grHead);
            }

            if (!$rules) {
                $unclassifiedDebet += $row->tot_debet;
                $unclassifiedKredit += $row->tot_kredit;
                continue;
            }

            foreach ($rules as $rule) {
                $code = $rule->code_arus_kas;
                $kategoriTotals[$code] ??= ['debet' => 0, 'kredit' => 0];

                if ($rule->arah === 'debet' || $rule->arah === 'both') {
                    $kategoriTotals[$code]['debet'] += $row->tot_debet;
                }
                if ($rule->arah === 'kredit' || $rule->arah === 'both') {
                    $kategoriTotals[$code]['kredit'] += $row->tot_kredit;
                }
            }
        }

        $kategori = $this->getKategori();
        $totalNettoKategori = 0;

        foreach ($kategori as $code => $meta) {
            if ($meta['line'] !== 'DETAIL') {
                continue;
            }

            $mutDebet = $kategoriTotals[$code]['debet'] ?? 0;
            $mutKredit = $kategoriTotals[$code]['kredit'] ?? 0;
            $totalNettoKategori += $mutKredit - $mutDebet;

            $existing = DB::table('tabel_master_kas')->where('unit', $unit)->where('code_arus_kas', $code)->first();
            $saldoAwal = $existing->saldo_akhir ?? 0;
            $saldoAkhir = $saldoAwal + ($mutKredit - $mutDebet);

            DB::table('tabel_master_kas')->updateOrInsert(
                ['unit' => $unit, 'code_arus_kas' => $code],
                [
                    'gl_arus_kas' => $unit . '-' . $code,
                    'line' => $meta['line'],
                    'nama_arus_kas' => $meta['nama'],
                    'group_heading' => $meta['group_heading'],
                    'mut_debet' => $mutDebet,
                    'mut_kredit' => $mutKredit,
                    'saldo_awal' => $saldoAwal,
                    'saldo_akhir' => $saldoAkhir,
                    'saldo_tahun' => $saldoAkhir,
                    'periode_awal' => $tanggalAwal,
                    'periode_akhir' => $tanggalAkhir,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $perubahanKasRiil = $kasDebet - $kasKredit;
        $selisihRekonsiliasi = $totalNettoKategori - $perubahanKasRiil;

        $rows = DB::table('tabel_master_kas')
            ->where('unit', $unit)
            ->orderBy('gl_arus_kas')
            ->get()
            ->keyBy('code_arus_kas');

        $title = 'Laporan Arus Kas';
        $menus = $this->getMenus();

        return view('admin.report_arus_kas.index', [
            'title' => $title,
            'menus' => $menus,
            'kategori' => $kategori,
            'rows' => $rows,
            'periodeAwal' => $tanggalAwal,
            'periodeAkhir' => $tanggalAkhir,
            'rekonsiliasi' => [
                'total_netto_kategori' => $totalNettoKategori,
                'perubahan_kas_riil' => $perubahanKasRiil,
                'selisih' => $selisihRekonsiliasi,
                'unclassified_debet' => $unclassifiedDebet,
                'unclassified_kredit' => $unclassifiedKredit,
            ],
        ]);
    }
}
