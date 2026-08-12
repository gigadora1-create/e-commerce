<?php

namespace App\Exports;

use App\Models\PickingOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;

class PickingReportExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithTitle, 
    WithStyles, 
    WithColumnWidths, 
    ShouldAutoSize
{
    protected $pickingOrderId;

    public function __construct($pickingOrderId)
    {
        $this->pickingOrderId = $pickingOrderId;
    }

    public function collection()
    {
        $pickingOrder = PickingOrder::with('details')->find($this->pickingOrderId);
        $details = $pickingOrder->details;

        // Obtener todos los inventory_id de los detalles
        $inventoryIds = $details->pluck('inventory_id')->unique()->toArray();

        // Cargar datos de inventario con subconsultas de stock
        $inventoryData = DB::table('inventories as i')
            ->leftJoin('locations as l', 'i.location_id', '=', 'l.location_id')
            ->select(
                'i.id',
                'i.quantity as quantity_original',
                'i.batch',
                'i.expiry_date',
                'i.localizacion as location_code',
                'i.warehouse',
                'i.customer',
                'l.name as location_name',
                DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM inventory_outputs WHERE inventory_id = i.id AND status IN ("completado", "devolucion")) as total_salidas'),
                DB::raw('(SELECT COALESCE(SUM(pr.quantity_reserved), 0) 
                          FROM picking_reservations pr 
                          JOIN picking_orders po ON pr.picking_order_id = po.id 
                          WHERE pr.inventory_id = i.id AND po.status IN ("pending", "in_progress")
                         ) as quantity_reserved')
            )
            ->whereIn('i.id', $inventoryIds)
            ->get()
            ->map(function($item) {
                $item->quantity_current = (int)$item->quantity_original - (int)$item->total_salidas;
                $item->quantity_net_available = $item->quantity_current - (int)$item->quantity_reserved;
                return $item;
            })
            ->keyBy('id');

        // Asignar los datos de inventory al objeto details
        foreach ($details as $detail) {
            $detail->inventoryData = $inventoryData[$detail->inventory_id] ?? null;
        }

        return $details;
    }

    public function map($detail): array
    {
        $inventoryData = $detail->inventoryData;
        $locationDisplay = $detail->location_code;
        if ($inventoryData && $inventoryData->location_name) {
            $locationDisplay = $detail->location_code . ' - ' . $inventoryData->location_name;
        }

        $expiryDate = '';
        if ($detail->expiry_date) {
            if (is_string($detail->expiry_date)) {
                $expiryDate = \Carbon\Carbon::parse($detail->expiry_date)->format('d/m/Y');
            } else {
                $expiryDate = $detail->expiry_date->format('d/m/Y');
            }
        }

        $estadosTraducidos = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado'
        ];

        $estado = $estadosTraducidos[$detail->pickingOrder->status] ?? $detail->pickingOrder->status;

        return [
            $detail->pickingOrder->picking_code,
            $detail->order_number ?? 'N/A',
            $detail->sku,
            $detail->item_description,
            $locationDisplay,
            $detail->warehouse,
            $detail->customer ?? 'N/A',
            $detail->batch ?? 'N/A',
            $expiryDate,
            $detail->quantity_requested,
            $detail->quantity_picked,
            $estado,
        ];
    }

    public function headings(): array
    {
        return [
            'Código Salida',
            'Nro. Pedido',
            'SKU',
            'Descripción',
            'Ubicación',
            'Bodega',
            'Cliente',
            'Lote',
            'Fecha Vencimiento',
            'Cantidad Solicitada',
            'Cantidad Recogida',
            'Estado',
        ];
    }

    public function title(): string
    {
        return 'Reporte Salidas';
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = $this->collection()->count() + 1;

        return [
            // Encabezados (fila 1) - ROJO CORPORATIVO
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
            // Datos: A2:L (ahora solo hasta L)
            'A2:L' . $rowCount => [
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
            // Columnas centradas
            'A2:A' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'B2:B' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'C2:C' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'E2:E' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]], // Ubicación
            'F2:F' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'G2:G' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'H2:H' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'I2:I' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'J2:J' . $rowCount => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'numberFormat' => ['formatCode' => '#,##0'],
            ],
            'K2:K' . $rowCount => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'numberFormat' => ['formatCode' => '#,##0'],
            ],
            'L2:L' . $rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], // Estado
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Código Salida
            'B' => 15,  // Nro. Pedido
            'C' => 15,  // SKU
            'D' => 35,  // Descripción
            'E' => 35,  // Ubicación
            'F' => 15,  // Bodega
            'G' => 20,  // Cliente
            'H' => 15,  // Lote
            'I' => 18,  // Fecha Vencimiento
            'J' => 15,  // Cantidad Solicitada
            'K' => 15,  // Cantidad Recogida
            'L' => 15,  // Estado
        ];
    }
}