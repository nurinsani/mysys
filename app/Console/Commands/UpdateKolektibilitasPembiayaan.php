<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateKolektibilitasPembiayaan extends Command
{
    protected $signature = 'pembiayaan:update-kolektibilitas';

    protected $description = 'Update kolom gol (kolektibilitas) di tabel pembiayaan berdasarkan Frekuensi Tunggakan (ft), rumus sama dengan ReportPpapController. 1=Lancar, 2=Kurang Lancar, 3=Diragukan, 4=Macet.';

    public function handle(): int
    {
        DB::table('pembiayaan')->where('os', '>', 0)->update(['gol' => '1']);

        $rows = DB::table('pembiayaan')
            ->join('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.os', '>', 0)
            ->groupBy('pembiayaan.cif')
            ->select(
                'pembiayaan.cif',
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan'),
                DB::raw('
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END)
                        -
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                ')
            )
            ->havingRaw('total_tunggakan > 0')
            ->get();

        $terupdate = 0;
        foreach ($rows as $row) {
            $gol = match (true) {
                $row->ft >= 12 => '4', // macet
                $row->ft >= 7 => '3',  // diragukan
                $row->ft >= 4 => '2',  // kurang lancar
                default => '1',        // lancar (ft 1-3)
            };

            DB::table('pembiayaan')->where('cif', $row->cif)->update(['gol' => $gol]);
            $terupdate++;
        }

        $this->info("Kolektibilitas diperbarui: {$terupdate} pembiayaan overdue dikategorikan ulang, sisanya di-reset ke Lancar.");

        return self::SUCCESS;
    }
}
