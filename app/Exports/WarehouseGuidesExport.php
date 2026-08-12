<?php

namespace App\Exports;

use App\Models\WarehouseGuide;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehouseGuidesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected array $filters;
    protected ?Collection $cachedCollection = null;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->getCollection();
    }

    protected function getCollection(): Collection
    {
        if ($this->cachedCollection instanceof Collection) {
            return $this->cachedCollection;
        }

        $customers = $this->filters['customers'] ?? null;
        if (empty($customers)) {
            $customers = [$this->filters['customer'] ?? session('selected_customer') ?? 'SKYONE'];
        }

        $warehouse = trim((string) ($this->filters['warehouse'] ?? ''));
        $startDate = !empty($this->filters['start_date'])
            ? Carbon::parse($this->filters['start_date'])->startOfDay()
            : null;
        $endDate = !empty($this->filters['end_date'])
            ? Carbon::parse($this->filters['end_date'])->endOfDay()
            : null;
        $reportType = $this->filters['report_type'] ?? 'all';
        $nationalGuide = trim((string) ($this->filters['national_guide'] ?? ''));

        $query = WarehouseGuide::with(['currentLocation', 'entryUser', 'exitUser'])
            ->withCount('movements')
            ->whereIn('customer', $customers);

        if ($warehouse !== '') {
            $query->where('warehouse', $warehouse);
        }

        if ($startDate && $endDate) {
            if ($reportType === 'entries') {
                $query->whereBetween('entry_at', [$startDate, $endDate]);
            } elseif ($reportType === 'exits') {
                $query->whereNotNull('exit_at')
                    ->whereBetween('exit_at', [$startDate, $endDate]);
            } else {
                $query->where(function ($nestedQuery) use ($startDate, $endDate) {
                    $nestedQuery->whereBetween('entry_at', [$startDate, $endDate])
                        ->orWhereBetween('exit_at', [$startDate, $endDate]);
                });
            }
        }

        if ($nationalGuide !== '') {
            $query->where('national_guide', 'like', '%' . $nationalGuide . '%');
        }

        return $this->cachedCollection = $query->orderByDesc('entry_at')->get();
    }

    public function headings(): array
    {
        return [
            'Guia',
            'Guia nacional',
            'Cliente',
            'Bodega',
            'Estado',
            'Ubicacion actual',
            'Fecha ingreso',
            'Fecha salida',
            'Duracion',
            'Ingreso por',
            'Salida por',
            'Movimientos',
            'Notas',
        ];
    }

    public function map($guide): array
    {
        return [
            $guide->guide,
            $guide->national_guide ?? '',
            $guide->customer,
            $guide->warehouse,
            $guide->status_label,
            $guide->current_location_label,
            optional($guide->entry_at)->format('d/m/Y H:i'),
            optional($guide->exit_at)->format('d/m/Y H:i'),
            $guide->duration_label,
            optional($guide->entryUser)->name ?? 'N/A',
            optional($guide->exitUser)->name ?? 'N/A',
            (int) ($guide->movements_count ?? $guide->movements->count()),
            $guide->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = $this->getCollection()->count() + 1;

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B00020'],
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
            'A2:M' . $rowCount => [
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
            'A2:A' . $rowCount => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'D2:D' . $rowCount => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'G2:I' . $rowCount => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 20,
            'C' => 18,
            'D' => 20,
            'E' => 18,
            'F' => 28,
            'G' => 18,
            'H' => 18,
            'I' => 16,
            'J' => 22,
            'K' => 22,
            'L' => 12,
            'M' => 35,
        ];
    }

    public function title(): string
    {
        return 'Bodega';
    }
}
