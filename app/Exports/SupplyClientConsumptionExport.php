<?php

namespace App\Exports;

use App\Models\SupplyIssueRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplyClientConsumptionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;
    protected ?Collection $rows = null;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        if ($this->rows instanceof Collection) {
            return $this->rows;
        }

        $requests = SupplyIssueRequest::query()
            ->with([
                'client:id,name',
                'requestedBy:id,name',
                'preparedBy:id,name',
                'closedBy:id,name',
                'items.product:id,catalog_number,name',
            ])
            ->when(!empty($this->filters['client_id']), function ($query) {
                $query->where('supply_client_id', $this->filters['client_id']);
            })
            ->where(function ($query) {
                $query->whereBetween('requested_at', [$this->filters['from'], $this->filters['to']])
                    ->orWhereBetween('closed_at', [$this->filters['from'], $this->filters['to']]);
            })
            ->orderByDesc('id')
            ->get();

        return $this->rows = $requests->flatMap(function (SupplyIssueRequest $request) {
            return $request->items->map(function ($item) use ($request) {
                return (object) [
                    'client' => $request->client?->name ?? 'Sin cliente',
                    'request_number' => $request->request_number,
                    'status' => $request->status_label,
                    'requested_at' => $request->requested_at,
                    'ready_at' => $request->ready_at,
                    'closed_at' => $request->closed_at,
                    'requested_by' => $request->requestedBy?->name ?? 'Sin usuario',
                    'prepared_by' => $request->preparedBy?->name ?? 'Sin usuario',
                    'closed_by' => $request->closedBy?->name ?? 'Sin usuario',
                    'catalog_number' => $item->product?->catalog_number,
                    'product_name' => $item->product?->name ?? 'Sin producto',
                    'requested_quantity' => (int) $item->requested_quantity,
                    'reserved_quantity' => (int) $item->reserved_quantity,
                    'delivered_quantity' => (int) $item->delivered_quantity,
                    'request_notes' => $request->request_notes,
                    'admin_notes' => $request->admin_notes,
                ];
            });
        })->values();
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Solicitud',
            'Estado',
            'Fecha solicitud',
            'Fecha listo',
            'Fecha cierre',
            'Solicitado por',
            'Alistado por',
            'Cerrado por',
            'Catalogo',
            'Producto',
            'Cantidad solicitada',
            'Cantidad reservada',
            'Cantidad entregada',
            'Notas solicitud',
            'Notas admin',
        ];
    }

    public function map($row): array
    {
        return [
            $row->client,
            $row->request_number,
            $row->status,
            optional($row->requested_at)->format('Y-m-d H:i'),
            optional($row->ready_at)->format('Y-m-d H:i'),
            optional($row->closed_at)->format('Y-m-d H:i'),
            $row->requested_by,
            $row->prepared_by,
            $row->closed_by,
            $row->catalog_number,
            $row->product_name,
            $row->requested_quantity,
            $row->reserved_quantity,
            $row->delivered_quantity,
            $row->request_notes,
            $row->admin_notes,
        ];
    }
}
