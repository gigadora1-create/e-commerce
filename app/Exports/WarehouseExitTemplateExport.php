<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseExitTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'CLIENTE',
            'GUIA',
            'GUIA_NACIONAL',
        ];
    }

    public function array(): array
    {
        return [
            ['EASY GO CARGO', 'GL000024273CO', 'GN000000001CO'],
        ];
    }
}
