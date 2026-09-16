<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;

class ExportMobcolController extends BaseController
{
    public function index()
    {
        $title = 'Transaksi CS Mobile';
        $menus = $this->getMenus();

        return view('admin.transaksi_cs.index', compact('title', 'menus'));
    }

    public function getData(Request $request)
{
    $userid = auth()->user()->unit; // ambil unit dari user login

    $data = DB::connection('cs')->select("
        SELECT
            tagihan_cs.unit,
            tagihan_cs.tgl_tagih as tgl_tagih,
            cs.nama_kel as nama_kel,
            tagihan_cs.cif as cif,
            cs.nama as nama,
            tagihan_cs.bulat as bulat,
            cs.nominal as nominal,
            tagihan_cs.status_trans
        FROM cs
        LEFT JOIN tagihan_cs ON cs.cif = tagihan_cs.cif
        WHERE tagihan_cs.unit = ?
    ", [$userid]);

    return response()->json([
        'data' => $data
    ]);
}


       public function exportCs()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

    $query = "
        SELECT tagihan_cs.tgl_tagih as tgl_tagih,
        tagihan_cs.nama_kel as nama_kel,
        nama_ao,
        pb,
        ke,
        tagihan_cs.code_kel as kode_kel,
        twm,simpanan_pokok,
        simpanan_wajib,
        tagihan_cs.nama as nama,
        tagihan_cs.cif as cif,
        tagihan_cs.bulat as bulat,
        cs.nominal as nominal,
        status_trans,
        status_download
        from tagihan_cs
        left join cs on cs.cif = tagihan_cs.cif
        left join ao on ao.cao = tagihan_cs.cao
        WHERE tagihan_cs.unit = ?
    ";

    $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_cs.xlsx');
    }

    public function exportPenarikan()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tagihan_penarikan.tgl_tagih as tgl_tagih,
        tagihan_penarikan.nama_kel as nama_kel,
        nama_ao,
        pb,
        ke,
        tagihan_penarikan.code_kel as kode_kel,
        twm,
        simpanan_pokok,
        simpanan_wajib,
        tagihan_penarikan.nama as nama,
        tagihan_penarikan.cif as cif,
        tagihan_penarikan.bulat as bulat,
        penarikan.nominal as nominal,
        status_trans from tagihan_penarikan
        left join penarikan on penarikan.cif = tagihan_penarikan.cif
        left join ao on ao.cao = tagihan_penarikan.cao
        WHERE tagihan_penarikan.unit = ?
        ";

        $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_penarikan.xlsx');
    }

    public function exportLebaran()

    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tagihan_lebaran.tgl_tagih as tgl_tagih,
        tagihan_lebaran.nama_kel as nama_kel,
        nama_ao,
        pb,
        ke,
        tagihan_lebaran.code_kel as kode_kel,
        twm,
        simpanan_pokok,
        simpanan_wajib,
        tagihan_lebaran.nama as nama,
        tagihan_lebaran.cif as cif,
        tagihan_lebaran.bulat as bulat,
        lebaran.nominal as nominal,
        status_trans
        from tagihan_lebaran
        left join lebaran on lebaran.cif = tagihan_lebaran.cif
        left join ao on ao.cao = tagihan_lebaran.cao
        WHERE tagihan_lebaran.unit = ?";

        $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_lebaran.xlsx');
    }

    public function exportLima()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tagihan_lima_persen.tgl_tagih as tgl_tagih,
        tagihan_lima_persen.nama_kel as nama_kel,
        ,nama_ao,
        pb,
        ke,
        tagihan_lima_persen.code_kel as kode_kel,
        twm,
        simpanan_pokok,
        simpanan_wajib,
        tagihan_lima_persen.nama as nama,
        tagihan_lima_persen.cif as cif,
        tagihan_lima_persen.bulat as bulat,
        lima.nominal as nominal,
        status_trans
        from tagihan_lima_persen
        left join lima on lima.cif = tagihan_lima_persen.cif
        left join ao on ao.cao = tagihan_lima_persen.cao
        WHERE tagihan_lima_persen.unit = ?
         ";

        $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_lima_persen.xlsx');
    }

    public function exportPelunasan()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tagihan_pelunasan.tgl_tagih as tgl_tagih,tagihan_pelunasan.nama_kel as nama_kel,nama_ao,pb,ke,tagihan_pelunasan.code_kel as kode_kel,twm,simpanan_pokok, simpanan_wajib, tagihan_pelunasan.nama as nama, tagihan_pelunasan.cif as cif, tagihan_pelunasan.bulat as bulat, pelunasan.nominal as nominal, status_trans from tagihan_pelunasan left join pelunasan on pelunasan.cif = tagihan_pelunasan.cif
        left join ao on ao.cao = tagihan_pelunasan.cao
         WHERE tagihan_pelunasan.unit = ?";

        $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_pelunasan.xlsx');
    }

    public function exportTunggakan()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tagihan_tunggakan.tgl_tagih as tgl_tagih,tagihan_tunggakan.nama_kel as nama_kel,nama_ao, tagihan_tunggakan.nama as nama, tagihan_tunggakan.cif as cif, tagihan_tunggakan.bulat as bulat, tunggakan.nominal as nominal, status_trans from tagihan_tunggakan left join tunggakan on tunggakan.cif = tagihan_tunggakan.cif
        left join ao on ao.cao = tagihan_tunggakan.cao
        WHERE tagihan_tunggakan.unit = ?";

         $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_tunggakan.xlsx');
    }

    public function exportWo()
    {
        $userid = auth()->user()->unit ?? 'default_unit';

        $query = "
        SELECT tgl_tagih,nama, nama_kel,kode_kel,nama_ao,wo.cif as cif, os, nominal from wo
        left join ao on ao.cao = wo.cao
        WHERE wo.unit = ?";

         $data = DB::connection('cs')->select($query, [$userid]);

    return Excel::download(new \App\Exports\GenericExport($data), 'data_wo.xlsx');
    }
}
