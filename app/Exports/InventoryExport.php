<?php
namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventoryExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize
{
    protected $startDate, $endDate, $product, $warehouse, $location, $customer;

    public function __construct($startDate = null, $endDate = null, $product = null, $warehouse = null, $location = null, $customer = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->product = $product;
        $this->warehouse = $warehouse;
        $this->location = $location;
        $this->customer = $customer;
    }

    public function collection()
    {
        // 1. Pre-agregado: Resumen de salidas, devoluciones y últimas modificaciones por ítem/loc
        $outputsSummary = DB::table('inventory_outputs')
            ->select(
                'item_id',
                'localizacion',
                DB::raw('SUM(CASE WHEN status = "devolucion" THEN ABS(quantity) ELSE 0 END) as total_returns'),
                DB::raw('SUM(CASE WHEN status <> "devolucion" THEN quantity ELSE 0 END) as total_outputs'),
                DB::raw('MAX(updated_at) as last_output_modified')
            )
            ->groupBy('item_id', 'localizacion');

        // 2. Query principal optimizada
        $query = DB::table('inventories as i')
            ->join('items as it', 'i.item_id', '=', 'it.item_id')
            ->leftJoin('locations as l', function($join) {
                $join->on('i.localizacion', '=', 'l.code')
                     ->on('i.warehouse', '=', 'l.warehouse')
                     ->on('i.customer', '=', 'l.customer');
            })
            ->leftJoin('item_locations as il', function($join) {
                $join->on('it.item_id', '=', 'il.item_id')
                     ->on('l.location_id', '=', 'il.location_id');
            })
            ->leftJoinSub($outputsSummary, 'os', function($join) {
                $join->on('i.item_id', '=', 'os.item_id')
                     ->on(DB::raw('COALESCE(l.code, i.localizacion, "")'), '=', 'os.localizacion');
            })
            ->select(
                'it.sku',
                'it.name as item_description',
                'it.item_id',
                'i.warehouse',
                'i.customer',
                'i.entry_date',
                DB::raw('COALESCE(l.code, i.localizacion, "") as location_code'),
                'l.name as location_name',
                'l.location_id',
                'il.max_capacity',
                DB::raw('SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) as original_entries'),
                DB::raw('COALESCE(os.total_returns, 0) as total_returns'),
                DB::raw('SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END) as total_retention'),
                DB::raw('COALESCE(os.total_outputs, 0) as total_outputs'),
                DB::raw('GREATEST(0, 
                    SUM(CASE WHEN i.status IN ("INGRESO", "SALIDA") THEN i.quantity ELSE 0 END) - 
                    COALESCE(os.total_outputs, 0) + 
                    COALESCE(os.total_returns, 0) - 
                    SUM(CASE WHEN i.status = "RETENCION" THEN i.quantity ELSE 0 END)
                ) as current_stock'),
                DB::raw('MAX(i.expiry_date) as expiry_date'),
                DB::raw('GREATEST(
                    COALESCE(MAX(i.updated_at), "1970-01-01"), 
                    COALESCE(os.last_output_modified, "1970-01-01")
                ) as last_modified_date')
            )
            ->whereNotNull('it.item_id')
            ->whereNotNull('it.sku');

        // FILTRO: Cliente (Obligatorio en este sistema)
        if ($this->customer) {
            $query->where('i.customer', 'like', "%{$this->customer}%");
        }

        // Filtro por rango de fechas (fecha de entrada)
        if ($this->startDate) {
            $query->whereDate('i.entry_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('i.entry_date', '<=', $this->endDate);
        }

        // Filtro por producto (búsqueda en descripción y SKU)
        if ($this->product) {
            $query->where(function($q) {
                $q->where('it.name', 'like', "%{$this->product}%")
                  ->orWhere('it.sku', 'like', "%{$this->product}%");
            });
        }

        // Filtro por bodega
        if ($this->warehouse) {
            $query->where('i.warehouse', 'like', "%{$this->warehouse}%");
        }

        // Filtro por ubicación (código o nombre)
        if ($this->location) {
            $query->where(function($q) {
                $q->where('l.code', 'like', "%{$this->location}%")
                  ->orWhere('l.name', 'like', "%{$this->location}%")
                  ->orWhere('i.localizacion', 'like', "%{$this->location}%");
            });
        }

        $inventories = $query->groupBy(
                'it.item_id', 'it.sku', 'it.name', 'i.warehouse', 'i.customer', 'i.entry_date',
                'location_code', 'l.name', 'l.location_id', 'il.max_capacity',
                'os.total_returns', 'os.total_outputs', 'os.last_output_modified'
            )
            ->havingRaw('original_entries > 0 OR current_stock > 0')
            ->orderBy('i.warehouse', 'asc')
            ->orderBy('location_code', 'asc')
            ->get();

        return $inventories;
    }

    public function headings(): array
    {
        return [
            'Código Ubicación',
            'Ubicación',
            'Bodega',
            'Cliente',
            'SKU',
            'Producto',
            'Capacidad Máxima',
            'Fecha de Ingreso',
            'Ingreso',
            'Salidas',
            'Stock Disponible',
            'Fecha de Vencimiento',
            'Fecha Última Modificación',
        ];
    }

    public function map($inventory): array
    {
        return [
            $inventory->location_code ?? 'N/A',
            $inventory->location_name ?? 'N/A',
            $inventory->warehouse ?? 'N/A',
            $inventory->customer ?? 'N/A',
            $inventory->sku ?? 'N/A',
            $inventory->item_description ?? 'N/A',
            (int) $inventory->max_capacity,
            $inventory->entry_date ? date('d/m/Y', strtotime($inventory->entry_date)) : 'N/A',
            (int) $inventory->original_entries, 
            (int) $inventory->total_outputs,
            (int) $inventory->current_stock,
            $inventory->expiry_date ? date('d/m/Y', strtotime($inventory->expiry_date)) : 'N/A',
            $inventory->last_modified_date ? date('d/m/Y', strtotime($inventory->last_modified_date)) : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = $this->collection()->count() + 1;

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545'],
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
            "A2:M{$rowCount}" => [
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
            "G2:K{$rowCount}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'numberFormat' => [
                    'formatCode' => '#,##0',
                ],
            ],
            "L2:M{$rowCount}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Código Ubicación
            'B' => 20, // Ubicación
            'C' => 20, // Bodega
            'D' => 20, // Cliente
            'E' => 15, // SKU
            'F' => 40, // Producto
            'G' => 15, // Capacidad Máxima
            'H' => 15, // Fecha de Ingreso
            'I' => 15, // Ingreso
            'J' => 15, // Salidas
            'K' => 15, // Stock Disponible
            'L' => 20, // Fecha de Vencimiento
            'M' => 20, // Fecha Última Modificación
        ];
    }

    public function title(): string
    {
        $title = 'Reporte de Inventario Unificado';

        if ($this->startDate || $this->endDate) {
            $title .= ' - Filtrado';
        }

        return $title;
    }
}