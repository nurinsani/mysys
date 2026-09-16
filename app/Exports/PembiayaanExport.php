<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembiayaanExport implements FromCollection, WithTitle, WithHeadings, WithStyles
{
    protected $status;
    protected $bulan;
    protected $tahun;

    public function __construct($status, $bulan, $tahun)
    {
        $this->status = $status;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection()
    {
        if ($this->status === 'current') {
            $query = DB::table('pembiayaan')
                ->leftJoin('anggota', 'data_loan.cif', '=', 'anggota.cif')
                ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
                ->leftJoin('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
                ->leftJoin('tunggakan', 'pembiayaan.no_anggota', '=', 'tunggakan.norek')
                ->where('pembiayaan.unit', Auth::user()->unit)
                ->whereDate('pembiayaan.buss_date', Carbon::now()->format('Y-m-d'))
                ->where('pembiayaan.os', '>', 0)
                ->select([
                    DB::raw('MAX(pembiayaan.unit) as unit'),
                    DB::raw('MAX(data_loan.no_anggota) as no_rekening'),
                    DB::raw('MAX(pembiayaan.nama) as nama'),
                    DB::raw('MAX(pembiayaan.code_kel) as code_kel'),
                    DB::raw('MAX(pembiayaan.tgl_murab) as tgl_murab'),
                    DB::raw('MAX(pembiayaan.tenor) as tenor'),
                    DB::raw('MAX(pembiayaan.maturity_date) as maturity_date'),
                    DB::raw('MAX(pembiayaan.plafond) as plafond'),
                    DB::raw('MAX(pembiayaan.os - pembiayaan.saldo_margin) as saldo_pinjaman'),
                    DB::raw('MAX(pembiayaan.os) as os'),
                    DB::raw('MAX(pembiayaan.saldo_margin) as saldo_margin'),
                    DB::raw('MAX(pembiayaan.ke) as ke'),
                    DB::raw("MAX(CASE WHEN tunggakan.type = '01' THEN COALESCE(tunggakan.debet, 0) - COALESCE(tunggakan.kredit, 0) ELSE 0 END) as tunggakan_pokok"),
                    DB::raw("MAX(CASE WHEN tunggakan.type = '02' THEN COALESCE(tunggakan.debet, 0) - COALESCE(tunggakan.kredit, 0) ELSE 0 END) as tunggakan_margin"),
                    DB::raw('MAX(pembiayaan.gol) as gol'),
                    DB::raw('MAX(pembiayaan.cao) as cao'),
                    DB::raw('MAX(ao.nama_ao) as nama_ao'),
                    DB::raw('MAX(kelompok.nama_kel) as nama_kelompok'),
                    DB::raw('MAX(pembiayaan.cif) as cif'),
                    DB::raw('MAX(anggota.haddress1) as alamat'),
                    DB::raw('MAX(pembiayaan.nama_usaha) as nama_usaha'),
                    DB::raw('MAX(anggota.id_number) as no_ktp'),
                    DB::raw('MAX(pembiayaan.status_app) as status'),
                    DB::raw('MAX(pembiayaan.bulat - pembiayaan.angsuran) as twm'),
                    DB::raw('MAX((pembiayaan.bulat - pembiayaan.angsuran) * pembiayaan.run_tenor) as saldo_twm'),
                ])
                ->groupBy('pembiayaan.no_anggota');
                
        } elseif ($this->status === 'eom') {
            $bulanAngka = match(strtolower($this->bulan)) {
                'januari' => '01',
                'februari' => '02',
                'maret' => '03',
                'april' => '04',
                'mei' => '05',
                'juni' => '06',
                'juli' => '07',
                'agustus' => '08',
                'september' => '09',
                'oktober' => '10',
                'november' => '11',
                'desember' => '12',
                default => null,
            };

            if (!$bulanAngka) {
                throw new \Exception('Bulan tidak valid');
            }

            $query = DB::table('data_loan')
                ->leftJoin('anggota', 'data_loan.cif', '=', 'anggota.cif')
                ->leftJoin('ao', 'data_loan.cao', '=', 'ao.cao')
                ->leftJoin('kelompok', 'data_loan.code_kel', '=', 'kelompok.code_kel')
                ->leftJoin('tunggakan', 'data_loan.no_anggota', '=', 'tunggakan.norek')
                ->select([
                    DB::raw('MAX(data_loan.unit) as unit'),
                    DB::raw('MAX(data_loan.no_anggota) as no_rekening'),
                    DB::raw('MAX(data_loan.Cust_Short_name) as nama'),
                    DB::raw('MAX(data_loan.code_kel) as code_kel'),
                    DB::raw('MAX(data_loan.tgl_murab) as tgl_murab'),
                    DB::raw('MAX(data_loan.tenor) as tenor'),
                    DB::raw('MAX(data_loan.maturity_date) as maturity_date'),
                    DB::raw('MAX(data_loan.plafond) as plafond'),
                    DB::raw('MAX(data_loan.os - data_loan.saldo_margin) as saldo_pinjaman'),
                    DB::raw('MAX(data_loan.os) as os'),
                    DB::raw('MAX(data_loan.saldo_margin) as saldo_margin'),
                    DB::raw('MAX(data_loan.ke) as ke'),
                    DB::raw("MAX(CASE WHEN tunggakan.type = '01' THEN COALESCE(tunggakan.debet, 0) - COALESCE(tunggakan.kredit, 0) ELSE 0 END) as tunggakan_pokok"),
                    DB::raw("MAX(CASE WHEN tunggakan.type = '02' THEN COALESCE(tunggakan.debet, 0) - COALESCE(tunggakan.kredit, 0) ELSE 0 END) as tunggakan_margin"),
                    DB::raw('MAX(data_loan.gol) as gol'),
                    DB::raw('MAX(data_loan.cao) as cao'),
                    DB::raw('MAX(ao.nama_ao) as nama_ao'),
                    DB::raw('MAX(kelompok.nama_kel) as nama_kelompok'),
                    DB::raw('MAX(data_loan.cif) as cif'),
                    DB::raw('MAX(anggota.haddress1) as alamat'),
                    DB::raw('MAX(data_loan.nama_usaha) as nama_usaha'),
                    DB::raw("CONCAT('\'', MAX(anggota.id_number), '\'') as no_ktp"),
                    DB::raw('MAX(data_loan.status_app) as status'),
                    DB::raw('COALESCE(MAX(data_loan.bulat - data_loan.angsuran), 0) as twm'),
                    DB::raw('MAX((data_loan.bulat - data_loan.angsuran) * data_loan.run_tenor) as saldo_twm'),
                ])
                ->where('data_loan.unit', Auth::user()->unit)
                ->whereYear('data_loan.buss_date', $this->tahun)
                ->whereMonth('data_loan.buss_date', $bulanAngka)
                ->groupBy('data_loan.buss_date', 'data_loan.no_anggota')
                ->orderBy('data_loan.buss_date', 'asc');
        }

        
        return $query->get();
    }


    public function headings(): array
    {
        return [
            'UNIT',
            'NO. REKENING',
            'NAMA',
            'KELOMPOK',
            'TANGGAL PK',
            'TENOR',
            'JATUH TEMPO',
            'PLAFOND',
            'SALDO PINJAMAN',
            'SALDO OS',
            'SALDO MARGIN',
            'KE',
            'TUNGGAKAN POKOK',
            'TUNGGAKAN MARGIN',
            'GOL',
            'AO',
            'NAMA AO',
            'NAMA KELOMPOK',
            'CIF',
            'ALAMAT',
            'BIDANG USAHA',
            'NO KTP',
            'STATUS',
            'TWM',
            'SALDO TWM',
            'SISA TWM'
        ];
    }

    public function title(): string
    {
        return 'Nominative';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF008000']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ]
            ]
        ];
    }

}

