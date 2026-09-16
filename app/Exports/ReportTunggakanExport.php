<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportTunggakanExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $unit = Auth::user()->unit;
        return DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
            ->leftJoin('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.unit', $unit)
            ->select(
                'pembiayaan.unit',
                'kelompok.nama_kel as nama_kelompok',
                'ao.nama_ao',
                'pembiayaan.cif',
                'pembiayaan.nama as nama_anggota',
                DB::raw("
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END) 
                        - 
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                "),
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan')
            )
            ->groupBy(
                'pembiayaan.unit',
                'pembiayaan.nama',
                'pembiayaan.cao',
                'pembiayaan.cif',
                'kelompok.nama_kel')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Unit',
            'Nama Kelompok',
            'Nama AO',
            'CIF',
            'Nama',
            'ft',
            'Nominal',
        ];
    }
}
