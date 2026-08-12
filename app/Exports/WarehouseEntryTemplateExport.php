<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseEntryTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'CLIENTE',
            'GUIA',
            'LOCALIZACION',
        ];
    }

    public function array(): array
    {
        return [
            ['EASY GO CARGO', 'GL000024273CO', 'ALMACENAMIENTO'],
        ];
    }
}
