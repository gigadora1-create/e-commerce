<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryEntryTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'SKU',
            'BODEGA',
            'CLIENTE',
            'CANTIDAD',
            'UBICACION',
            'LOTE',
            'FECHA_DE_VENCIMIENTO',
            'CONDICION_DEL_ARTICULO',
            'FECHA_DE_INGRESO',
            'OBSERVACION',
        ];
    }

    public function array(): array
    {
        return [];
    }
}
