<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class GenericExport implements FromCollection, WithHeadings, WithEvents
{
    protected $data;
    protected $title;

    public function __construct($data, $title = 'Report Data')
    {
        $this->data  = collect($data);
        $this->title = $title;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->data->isNotEmpty()) {
            return array_keys((array) $this->data->first());
        }
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->insertNewRowBefore(1, 1);

                $sheet->setCellValue('A1', $this->title);

                $lastColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$lastColumn}1");

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
