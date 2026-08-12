<?php

namespace App\Imports;

use App\Models\InventoryOutput;
use App\Models\Item;
use App\Models\Location;
use App\Models\ItemLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;

class InventoryOutputsImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, SkipsEmptyRows
{
    use Importable;

    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;
    protected $currentRow = 0;
    protected $createdOutputs = []; // Para almacenar las salidas creadas en distribución múltiple

    public function model(array $row)
    {
        $this->currentRow++;
        try {
            $row['guia'] = (string) trim($row['guia'] ?? '');
            $selectedCustomer = session('selected_customer');

            // Validación manual de campos requeridos
            $validationErrors = [];
            if (empty(trim($row['producto'] ?? ''))) {
                $validationErrors[] = 'El campo producto es obligatorio.';
            }
            if (empty(trim($row['bodega'] ?? ''))) {
                $validationErrors[] = 'El campo bodega es obligatorio.';
            }
            if (empty($row['guia'])) {
                $validationErrors[] = 'El campo guía es obligatorio.';
            }
            if (!isset($row['cantidad']) || !is_numeric($row['cantidad']) || (int)$row['cantidad'] < 1) {
                $validationErrors[] = 'La cantidad debe ser un número entero mayor a 0.';
            }
            if (empty($row['fecha_salida'])) {
                $validationErrors[] = 'El campo fecha de salida es obligatorio.';
            }
            if (!isset($row['valor_declarado']) || !is_numeric($row['valor_declarado']) || (float)$row['valor_declarado'] < 0) {
                $validationErrors[] = 'El valor declarado debe ser un número mayor o igual a 0.';
            }
            if (empty(trim($row['estado'] ?? ''))) {
                $validationErrors[] = 'El campo estado es obligatorio.';
            }

            if (!empty($validationErrors)) {
                $this->errors[] = "Fila {$this->currentRow}: " . implode(' ', $validationErrors);
                $this->errorCount++;
                return null;
            }

            if (!$selectedCustomer) {
                $this->errors[] = "Fila {$this->currentRow}: No se ha seleccionado un cliente en la sesión.";
                $this->errorCount++;
                return null;
            }

            $item = Item::where('name', trim($row['producto']))->first();

            if (!$item) {
                $this->errors[] = "Fila {$this->currentRow}: El producto '{$row['producto']}' no existe en el catálogo.";
                $this->errorCount++;
                return null;
            }

            $warehouse = trim($row['bodega']);
            $requestedQuantity = (int) $row['cantidad'];

            // Validar stock disponible usando vw_inventory_unified (stock global)
            $currentStockGlobal = DB::table('vw_inventory_unified')
                ->where('item_id', $item->item_id)
                ->where('warehouse', $warehouse)
                ->where('customer', $selectedCustomer)
                ->sum('current_stock');

            if ($requestedQuantity > $currentStockGlobal) {
                $this->errors[] = "Fila {$this->currentRow}: La cantidad solicitada ({$requestedQuantity}) excede el stock disponible total ({$currentStockGlobal}).";
                $this->errorCount++;
                return null;
            }

            // Obtener todas las ubicaciones con stock disponible, ordenadas por mayor stock
            $itemLocations = $this->getAvailableLocationsWithStock($item->item_id, $selectedCustomer, $warehouse);

            if ($itemLocations->isEmpty()) {
                $this->errors[] = "Fila {$this->currentRow}: No hay ubicaciones asignadas con stock para el producto '{$item->name}' en la bodega '{$warehouse}'.";
                $this->errorCount++;
                return null;
            }

            $createdAt = $this->parseDate($row['fecha_salida']);
            $inventoryService = app(\App\Services\InventoryService::class);

            $inventoryService->registerExitGlobal(
                $item->item_id,
                $warehouse,
                $selectedCustomer,
                $requestedQuantity,
                [
                    'guide' => $row['guia'],
                    'customer' => $selectedCustomer,
                    'warehouse' => $warehouse,
                    'type' => 'Import-Exit',
                    'observations' => $row['observaciones'] ?? 'Importación masiva',
                    'output_date' => $createdAt
                ]
            );

            $this->successCount++;
            return null; // For ToModel, returning null is fine if we handled persistence manually

        } catch (\Exception $e) {
            $this->errors[] = "Fila {$this->currentRow}: Error - " . $e->getMessage();
            $this->errorCount++;
            Log::error('Error en importación de salidas: ' . $e->getMessage());
            return null;
        }
    }

    public function prepareForValidation($data, $index)
    {
        $data['guia'] = (string) trim($data['guia'] ?? '');
        return $data;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow($row)
    {
        $this->currentRow = $row->getIndex();
    }

    private function parseDate($date)
    {
        try {
            if (empty($date)) {
                throw new \Exception('La fecha de salida está vacía.');
            }
            if (is_numeric($date)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $date)) {
                return Carbon::createFromFormat('Y-m-d', substr($date, 0, 10));
            }
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $date)) {
                return Carbon::createFromFormat('d/m/Y', $date);
            }
            return Carbon::parse($date);
        } catch (\Exception $e) {
            throw new \Exception("Formato de fecha inválido: {$date}");
        }
    }
    public function getMultiLocationDistributions(): array
    {
        return $this->createdOutputs;
    }
    // En InventoryOutputsImport.php
public function getErrors(): array
{
    return array_unique($this->errors); // Eliminar duplicados
}

public function getSuccessCount(): int
{
    return $this->successCount;
}

public function getErrorCount(): int
{
    return $this->errorCount;
}

public function getStats(): array
{
    return [
        'success' => $this->successCount,
        'errors' => $this->errorCount,
        'total' => $this->successCount + $this->errorCount
    ];
}

}