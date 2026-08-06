<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithColumnWidths;

class FinancialReportExport implements FromView, WithStyles, WithColumnWidths
{
    protected $viewName;
    protected $data;

    public function __construct($viewName, $data)
    {
        $this->viewName = $viewName;
        $this->data = $data;
    }

    public function view(): View
    {
        return view($this->viewName, $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Global styles
        $sheet->getStyle('A1:G1000')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:G1000')->getAlignment()->setWrapText(true);
        
        return [
            1    => ['font' => ['bold' => true]],
            2    => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45, // Uraian / Akun / Tanggal
            'B' => 25, // Saldo / Referensi
            'C' => 45, // Deskripsi / Akun Pasiva
            'D' => 25, // Debit / Saldo Pasiva
            'E' => 25, // Credit
            'F' => 25, // Balance
            'G' => 25,
        ];
    }
}
