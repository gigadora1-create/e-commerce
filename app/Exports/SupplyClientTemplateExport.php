<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplyClientTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'NOMBRE',
            'DIRECCIÓN',
            'CIUDAD',
        ];
    }

    public function array(): array
    {
        return [
            ['DERCO FUNZA', 'Calle 80 # 12-34', 'BOGOTA'],
        ];
    }
}
