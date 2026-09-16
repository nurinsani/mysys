<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\GenericExport;


class ReportMobcolController extends BaseController
{
     public function index(Request $request)
    {
         $title = 'Report Mobcol';
        $menus = $this->getMenus();
      $tanggal = $request->get('tanggal') ?? date('Y-m-d');
        return view('admin.report_mobcol.index', compact('tanggal', 'menus', 'title','tanggal'));
    }

    public function export($type, Request $request)
    {
        $tanggal = $request->get('tanggal');
        $userid = auth()->user()->unit ?? 'default_unit';

        switch ($type) {
            case 'cs':

            $query = "
            SELECT eod_tagihan_cs.tgl_tagih as tgl_tagih,eod_tagihan_cs.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_cs.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_cs.nama as nama, eod_tagihan_cs.cif as cif, eod_tagihan_cs.bulat as bulat, eod_tagihan_cs.bayar as nominal,status_realisasi as status_trans from eod_tagihan_cs
            left join ao on ao.cao = eod_tagihan_cs.cao
            WHERE eod_tagihan_cs.unit = ? and eod_tagihan_cs.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report CS Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_cs.xlsx');

            case 'musyarakah':
                return Excel::download(new MusyarakahExport($tanggal), 'report_musyarakah.xlsx');
            case 'penarikan':
             $query = "
                SELECT eod_tagihan_penarikan.tgl_tagih as tgl_tagih,eod_tagihan_penarikan.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_penarikan.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_penarikan.nama as nama, eod_tagihan_penarikan.cif as cif, eod_tagihan_penarikan.bulat as bulat, eod_tagihan_penarikan.bayar as nominal,status_realisasi as status_trans from eod_tagihan_penarikan
                left join ao on ao.cao = eod_tagihan_penarikan.cao
                WHERE eod_tagihan_penarikan.unit = ? and eod_tagihan_penarikan.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Penarikan Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_penarikan.xlsx');

            case 'lima':
             $query = "
                SELECT eod_tagihan_lima_persen.tgl_tagih as tgl_tagih,eod_tagihan_lima_persen.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_lima_persen.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_lima_persen.nama as nama, eod_tagihan_lima_persen.cif as cif, eod_tagihan_lima_persen.bulat as bulat, eod_tagihan_lima_persen.bayar as nominal,status_realisasi as status_trans from eod_tagihan_lima_persen
                left join ao on ao.cao = eod_tagihan_lima_persen.cao
                WHERE eod_tagihan_lima_persen.unit = ? and eod_tagihan_lima_persen.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Lima % Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_lima_persen.xlsx');

            case 'lebaran':
             $query = "
                SELECT eod_tagihan_lebaran.tgl_tagih as tgl_tagih,eod_tagihan_lebaran.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_lebaran.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_lebaran.nama as nama, eod_tagihan_lebaran.cif as cif, eod_tagihan_lebaran.bulat as bulat, eod_tagihan_lebaran.bayar as nominal,status_realisasi as status_trans from eod_tagihan_lebaran
                left join ao on ao.cao = eod_tagihan_lebaran.cao
                WHERE eod_tagihan_lebaran.unit = ? and eod_tagihan_lebaran.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Setoran Lebaran Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_lebaran.xlsx');

            case 'tunggakan':
             $query = "
                SELECT eod_tagihan_tunggakan.tgl_tagih as tgl_tagih,eod_tagihan_tunggakan.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_tunggakan.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_tunggakan.nama as nama, eod_tagihan_tunggakan.cif as cif, eod_tagihan_tunggakan.bulat as bulat, eod_tagihan_tunggakan.bayar as nominal,status_realisasi as status_trans from eod_tagihan_tunggakan
                left join ao on ao.cao = eod_tagihan_tunggakan.cao
                WHERE eod_tagihan_tunggakan.unit = ? and eod_tagihan_tunggakan.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Setoran TUnggakan Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_tunggakan.xlsx');

             case 'pelunasan':
             $query = "
               SELECT eod_tagihan_pelunasan.tgl_tagih as tgl_tagih,eod_tagihan_pelunasan.nama_kel as nama_kel,nama_ao,pb,ke,eod_tagihan_pelunasan.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, eod_tagihan_pelunasan.nama as nama, eod_tagihan_pelunasan.cif as cif, eod_tagihan_pelunasan.bulat as bulat, eod_tagihan_pelunasan.bayar as nominal,status_realisasi as status_trans from eod_tagihan_pelunasan
                left join ao on ao.cao = eod_tagihan_pelunasan.cao
                WHERE eod_tagihan_pelunasan.unit = ? and eod_tagihan_pelunasan.tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Setoran Pelunasan Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_pelunasan.xlsx');

             case 'wo':
             $query = "
               SELECT tgl_tagih,nama, nama_kel,kode_kel,nama_ao,eod_wo.cif as cif, os as bulat, nominal from eod_wo
                left join ao on ao.cao = eod_wo.cao
                WHERE ao.kode_unit = ? and tgl_tagih = ?
            ";

            $data = DB::connection('cs')->select($query, [$userid,$tanggal]);
            $title = "Report Setoran Pelunasan Tanggal: " . $tanggal;
            return Excel::download(new GenericExport($data,$title), 'report_wo.xlsx');

            default:
                abort(404);
        }
    }
}
