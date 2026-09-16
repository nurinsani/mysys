<?php

namespace App\Exports;

use App\Models\simpanan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SimpananSheet implements FromCollection, WithTitle, WithHeadings, WithStyles
{
    protected $status;
    protected $bulan;
    protected $tahun;

    public function __construct($status, $bulan, $tahun)
    {
        $this->status = $status;
        $this->bulan = $bulan;
        $this->tahun = $tahun;

        logger('EXPORT PARAMS', [
            'status' => $status,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function collection()
    {
        $bulanAngka = match (strtolower($this->bulan)) {
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
            default     => Carbon::now()->format('m'),
        };

        $tahun = $this->tahun ?? Carbon::now()->year;

        $startDate = Carbon::createFromDate($tahun, $bulanAngka, 1)->startOfMonth()->toDateString();
        $endDate   = Carbon::createFromDate($tahun, $bulanAngka, 1)->endOfMonth()->toDateString();

        $startThisMonth = Carbon::now()->startOfMonth()->toDateString();
        $endThisMonth   = Carbon::now()->endOfMonth()->toDateString();

        $saldoAwal = DB::table('simpanan')
            ->selectRaw("
                SUM(simpanan.kredit - simpanan.debet) as saldo_awal
            ")
            ->whereNotBetween('simpanan.buss_date', [$startThisMonth, $endThisMonth])
            ->value('saldo_awal');

        $query = DB::table('simpanan')
            ->leftJoin('anggota', 'simpanan.norek', '=', 'anggota.no')
            ->select([
                'simpanan.unit',
                'simpanan.norek as no_rekening',
                'anggota.nama',
                'anggota.alamat',

                // saldo awal = saldo akhir bulan lalu
                DB::raw("$saldoAwal as saldo_awal"),

                // mutasi bulan ini
                DB::raw("SUM(CASE WHEN simpanan.buss_date BETWEEN '$startDate' AND '$endDate' THEN simpanan.debet ELSE 0 END) as mutasi_debet"),
                DB::raw("SUM(CASE WHEN simpanan.buss_date BETWEEN '$startDate' AND '$endDate' THEN simpanan.kredit ELSE 0 END) as mutasi_kredit"),

                // saldo akhir = saldo_awal - mutasi_debet + mutasi_kredit
                DB::raw("(
                    $saldoAwal - SUM(CASE WHEN simpanan.buss_date BETWEEN '$startDate' AND '$endDate' THEN simpanan.debet ELSE 0 END) 
                    + SUM(CASE WHEN simpanan.buss_date BETWEEN '$startDate' AND '$endDate' THEN simpanan.kredit ELSE 0 END))
                as saldo_akhir")
            ])
            ->groupBy(
                'simpanan.unit',
                'simpanan.norek',
                'anggota.nama',
                'anggota.alamat'
            );

        if ($this->status === 'current') {
            $query->whereDate('simpanan.buss_date', Carbon::now()->format('Y-m-d'));
        } elseif ($this->status === 'eom') {
            $bulanAngka = match (strtolower($this->bulan)) {
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

            $start = Carbon::createFromDate($this->tahun, $bulanAngka, 1)->startOfMonth();
            $end   = Carbon::createFromDate($this->tahun, $bulanAngka, 1)->endOfMonth();
            $query->whereBetween('simpanan.buss_date', [$start, $end]);
        }

        return $query->get();
    }



    public function headings(): array
    {
        return [
            'UNIT',
            'NO. REKENING',
            'NAMA',
            'ALAMAT',
            'SALDO AWAL',
            'MUTASI DEBET',
            'MUTASI KREDIT',
            'SALDO AKHIR'
        ];
    }

    public function title(): string
    {
        return 'SIMPANAN';
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
