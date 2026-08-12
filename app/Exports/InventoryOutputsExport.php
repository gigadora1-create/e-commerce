<?php
namespace App\Exports;

use App\Models\InventoryOutput;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventoryOutputsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize
{
    public function collection()
    {
        return InventoryOutput::with('item')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'SKU',          // Columna A
            'Codigo',         // Columna B
            'Bodega',       // Columna C
            'Ubicación',    // Columna D
            'Producto',     // Columna E
            'Cantidad',     // Columna F
            'Fecha de Salida', // Columna G
            'Estado',       // Columna H
        ];
    }

    public function map($output): array
    {
        return [
            $output->item->sku ?? 'N/A',      // SKU
            $output->guide,                   // Guía
            $output->warehouse,               // Bodega
            $output->localizacion ?? 'N/A',   // Ubicación
            $output->item_name ?? 'N/A',      // Producto
            $output->quantity,                // Cantidad
            $output->created_at->format('d/m/Y'), // Fecha de Salida
            $output->status,                  // Estado
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = $this->collection()->count() + 1;
        return [
            // Estilo para encabezados (fila 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545'], // Rojo Bootstrap
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Estilo para datos (A2:H$rowCount)
            'A2:H' . $rowCount => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Estilos específicos por columna
            'A2:A' . $rowCount => [ // SKU (centrado)
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'F2:F' . $rowCount => [ // Cantidad (centrado)
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'G2:G' . $rowCount => [ // Fecha (centrado)
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'H2:H' . $rowCount => [ // Estado (centrado)
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // SKU
            'B' => 15, // Guía
            'C' => 20, // Bodega
            'D' => 25, // Ubicación
            'E' => 35, // Producto
            'F' => 12, // Cantidad
            'G' => 15, // Fecha de Salida
            'H' => 15, // Estado
        ];
    }

    public function title(): string
    {
        return 'Salidas de Inventario';
    }
}
