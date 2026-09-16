<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SimpananExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            new SimpananSheet($this->status, $this->bulan, $this->tahun),
            new SimpananWajibSheet($this->status, $this->bulan, $this->tahun),
            new SimpananPokokSheet($this->status, $this->bulan, $this->tahun),
        ];
    }
}
