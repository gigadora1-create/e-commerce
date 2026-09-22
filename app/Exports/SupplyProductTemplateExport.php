<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplyProductTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'ID_CATALOGO',
            'NOMBRE',
            'DESCRIPCION',
            'STOCK_INICIAL',
        ];
    }

    public function array(): array
    {
        return [
            [1, 'BOLSA DEPRISA 360 CARTA *100', 'Presentacion por 100 unidades', 100],
        ];
    }
}
