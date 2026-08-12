<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\City;
use App\Models\Location;
use App\Models\ItemLocation;

use App\Helpers\ItemLocationStockHelper;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private $rowCount = 0;
    private $inventoryIds = [];
    private $maxRows = 500;
    private $itemsCache = [];
    private $userWarehousePermissions = [];
    private $errors = [];
    private $warnings = [];
    private $validatedRows = [];

    public function __construct()
    {
        // Cachear productos
        $items = Item::all();
        foreach ($items as $item) {
            $this->itemsCache[$item->sku] = [
                'item_id' => $item->item_id,
                'name' => trim(strtolower($item->name)),
                'sku' => trim($item->sku),
                'original_name' => $item->name,
            ];
        }

        // Permisos de bodegas del usuario
        $user = auth()->user();
        if ($user) {
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            $this->userWarehousePermissions = array_filter($permissions, function ($perm) {
                return !in_array($perm, ['password.create', 'user.management']);
            });
        }
    }

    public function collection(Collection $rows)
    {
        try {
            // Fase 1: Validar todas las filas
            $this->validateAllRows($rows);

            if (!empty($this->errors)) {
                $this->generateErrorReport();
                throw new \Exception($this->formatErrorReport());
            }

            // Fase 2: Procesar en transacción
            DB::beginTransaction();

            try {
                $this->processAllRows();
                DB::commit();

                Log::info('✅ Importación completada', [
                    'rows_processed' => count($this->validatedRows),
                    'warnings_count' => count($this->warnings),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('❌ Error crítico durante el procesamiento', [
                    'error' => $e->getMessage()
                ]);
                throw new \Exception("Error crítico: {$e->getMessage()}");
            }

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    private function validateAllRows(Collection $rows)
    {
        $currentRowNumber = 1;

        foreach ($rows as $row) {
            $currentRowNumber++;

            $row = collect($row)->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->toArray();

            $cleanRow = array_filter($row, function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            });

            if (empty($cleanRow)) {
                $this->addWarning($currentRowNumber, 'general', 'Fila vacía omitida');
                continue;
            }

            if ($this->rowCount >= $this->maxRows) {
                $this->addWarning($currentRowNumber, 'general', "Límite de {$this->maxRows} filas alcanzado");
                continue;
            }

            if (!$this->validateBasicRowStructure($row, $currentRowNumber)) {
                continue;
            }

            $rowData = $this->mapRowData($row);

            if (!$this->validateRequiredFields($rowData, $currentRowNumber)) {
                continue;
            }

            if (!$this->validateUserWarehousePermissionSafe($rowData['warehouse'], $currentRowNumber)) {
                continue;
            }

            if (!$this->validateWarehouseExistsSafe($rowData['warehouse'], $currentRowNumber)) {
                continue;
            }

            $validItem = $this->validateItemExistsSafe($rowData['sku'], $currentRowNumber);
            if (!$validItem) {
                continue;
            }

            $this->validateSkuInWarehouseSafe($rowData['sku'], $rowData['itemDescription'], $rowData['warehouse'], $currentRowNumber);

            // ✅ VALIDACIÓN CON VISTA OPTIMIZADA
            if (!$this->validateLocationOptimized($validItem['item_id'], $rowData, $currentRowNumber)) {
                continue;
            }

            if (!$this->validateDatesSafe($rowData, $currentRowNumber)) {
                continue;
            }

            if (!$this->validateNumericValues($rowData, $currentRowNumber)) {
                continue;
            }

            $rowData['item_id'] = $validItem['item_id'];
            $rowData['row_number'] = $currentRowNumber;
            $this->validatedRows[] = $rowData;
            $this->rowCount++;
        }
    }
    private function validateLocationOptimized($itemId, &$rowData, $rowNumber)
    {
        $locationCode = strtoupper(trim($rowData['localizacion']));

        // CASO 1: ALMACENAMIENTO (sin validación de capacidad)
        if ($locationCode === 'ALMACENAMIENTO') {
            $location = Location::where('code', 'ALMACENAMIENTO')
                ->where('customer', $rowData['customer'])
                ->where('is_active', true)
                ->first();

            if (!$location) {
                $this->addError(
                    $rowNumber,
                    'Ubicación',
                    "La ubicación 'ALMACENAMIENTO' no está registrada para el cliente '{$rowData['customer']}'",
                    "Debe crear la ubicación ALMACENAMIENTO primero"
                );
                return false;
            }

            $rowData['location_id'] = $location->location_id;
            return true;
        }

        // CASO 2: UBICACIÓN NORMAL - Usar vista optimizada
        $location = Location::where('code', $locationCode)
            ->where('warehouse', $rowData['warehouse'])
            ->where('customer', $rowData['customer'])
            ->where('is_active', true)
            ->first();

        if (!$location) {
            $availableLocations = Location::where('warehouse', $rowData['warehouse'])
                ->where('customer', $rowData['customer'])
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();

            $this->addError(
                $rowNumber,
                'Ubicación',
                "La ubicación '{$rowData['localizacion']}' no existe",
                "Ubicaciones disponibles: [" . implode(', ', $availableLocations) . "]"
            );
            return false;
        }

        $rowData['location_id'] = $location->location_id;

        // ✅ VALIDACIÓN SIN VISTAS (Optimizado)
        // 1. Verificar asignación en ItemLocation
        $itemLocation = ItemLocation::where('item_id', $itemId)
            ->where('location_id', $location->location_id)
            ->first();

        if (!$itemLocation) {
            $this->addError(
                $rowNumber,
                'Ubicación',
                "El producto no está asignado a la ubicación '{$rowData['localizacion']}'",
                "Debe asignar el producto a esta ubicación primero"
            );
            return false;
        }

        // 2. Calcular stock real y capacidad
        $currentStock = ItemLocationStockHelper::calculateCurrentStock($itemId, $location->code);
        $maxCapacity = $itemLocation->max_capacity ?? 1000;
        $availableCapacity = max(0, $maxCapacity - $currentStock);

        // Validar capacidad disponible
        if ($rowData['quantity'] > $availableCapacity) {
            $this->addError(
                $rowNumber,
                'Capacidad',
                "No hay espacio suficiente en ubicación '{$rowData['localizacion']}'",
                "Stock actual: {$currentStock}/{$maxCapacity} | Disponible: {$availableCapacity} | Intentando agregar: {$rowData['quantity']}"
            );
            return false;
        }

        return true;
    }



    private function mapRowData($row)
    {
        return [
            'sku' => trim($row['sku'] ?? ''),
            'itemDescription' => trim($row['nombre_del_producto'] ?? $row['descripcion_del_articulo'] ?? ''),
            'warehouse' => trim($row['bodega'] ?? ''),
            'customer' => trim($row['cliente'] ?? ''),
            'quantity' => floatval($row['cantidad'] ?? 0),
            'localizacion' => trim($row['ubicacion'] ?? ''),
            'location_id' => null,
            'batch' => $row['lote'] ?? '',
            'expiry_date' => $row['fecha_de_vencimiento'] ?? null,
            'item_condition' => $row['condicion_del_articulo'] ?? '',
            'entry_date' => $row['fecha_de_ingreso'] ?? null,
            'commerce' => $row['comercio'] ?? '',
            'value' => $row['valor'] ?? 0,
            'type' => $row['tipo'] ?? '',
            'observations' => $row['observacion'] ?? null,
        ];
    }


    private function validateBasicRowStructure($row, $rowNumber)
    {
        $requiredColumns = ['sku', 'bodega', 'cliente', 'cantidad', 'ubicacion'];
        $hasRequiredColumns = false;

        foreach ($requiredColumns as $column) {
            if (isset($row[$column]) && trim($row[$column]) !== '') {
                $hasRequiredColumns = true;
                break;
            }
        }

        if (!$hasRequiredColumns) {
            $this->addWarning($rowNumber, 'estructura', 'Fila sin datos relevantes');
            return false;
        }

        return true;
    }

    private function validateRequiredFields($rowData, $rowNumber)
    {
        $isValid = true;

        if (empty($rowData['sku'])) {
            $this->addError($rowNumber, 'SKU', 'El SKU es requerido', '');
            $isValid = false;
        }

        if (empty($rowData['itemDescription'])) {
            $this->addError($rowNumber, 'Descripción', 'La descripción es requerida', '');
            $isValid = false;
        }

        if (empty($rowData['warehouse'])) {
            $this->addError($rowNumber, 'Bodega', 'La bodega es requerida', '');
            $isValid = false;
        }

        if (empty($rowData['customer'])) {
            $this->addError($rowNumber, 'Cliente', 'El cliente es requerido', '');
            $isValid = false;
        }

        if ($rowData['quantity'] <= 0) {
            $this->addError(
                $rowNumber,
                'Cantidad',
                'La cantidad debe ser mayor a 0',
                "Valor: '{$rowData['quantity']}'"
            );
            $isValid = false;
        }

        if (empty($rowData['localizacion'])) {
            $this->addError($rowNumber, 'Ubicación', 'La ubicación es requerida', '');
            $isValid = false;
        }

        return $isValid;
    }

    private function validateUserWarehousePermissionSafe($warehouse, $rowNumber)
    {
        $user = auth()->user();
        $rolesUsuario = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();

        if (in_array('superadmin', $rolesUsuario) || in_array('administrador', $rolesUsuario)) {
            return true;
        }

        if (!in_array($warehouse, $this->userWarehousePermissions)) {
            $this->addError(
                $rowNumber,
                'Permisos',
                "No tiene permisos para la bodega '{$warehouse}'",
                "Contacte al administrador"
            );
            return false;
        }

        return true;
    }

    private function validateWarehouseExistsSafe($warehouse, $rowNumber)
    {
        $exists = City::whereRaw('LOWER(city_name) = ?', [strtolower($warehouse)])->exists();
        if (!$exists) {
            $this->addError(
                $rowNumber,
                'Bodega',
                "La bodega '{$warehouse}' no existe"
            );
            return false;
        }
        return true;
    }

    private function validateItemExistsSafe($sku, $rowNumber)
    {
        if (isset($this->itemsCache[$sku])) {
            return $this->itemsCache[$sku];
        }

        $this->addError(
            $rowNumber,
            'SKU',
            "El SKU '{$sku}' no existe"
        );
        return false;
    }
    private function validateSkuInWarehouseSafe($sku, $itemDescription, $warehouse, $rowNumber)
    {
        $existing = Inventory::where('warehouse', $warehouse)
            ->where('sku', $sku)
            ->first();

        if ($existing && strtolower(trim($existing->item_description)) !== strtolower(trim($itemDescription))) {
            $this->addWarning(
                $rowNumber,
                'coherencia',
                "SKU '{$sku}' ya registrado como '{$existing->item_description}' en bodega '{$warehouse}' (la descripción actual es '{$itemDescription}')"
            );
        }
    }
    private function validateDatesSafe($rowData, $rowNumber)
    {
        $isValid = true;

        if ($rowData['expiry_date']) {
            try {
                $this->parseDate($rowData['expiry_date']);
            } catch (\Exception $e) {
                $this->addError(
                    $rowNumber,
                    'Fecha Vencimiento',
                    "Formato inválido: '{$rowData['expiry_date']}'"
                );
                $isValid = false;
            }
        }

        if ($rowData['entry_date']) {
            try {
                $this->parseDate($rowData['entry_date']);
            } catch (\Exception $e) {
                $this->addError(
                    $rowNumber,
                    'Fecha Ingreso',
                    "Formato inválido: '{$rowData['entry_date']}'"
                );
                $isValid = false;
            }
        }

        return $isValid;
    }
    private function validateLocationSafe($itemId, &$rowData, $rowNumber)
    {
        $locationCode = strtoupper(trim($rowData['localizacion']));

        // CASO 1: ALMACENAMIENTO (ubicación especial)
        if ($locationCode === 'ALMACENAMIENTO') {
            $location = Location::where('code', 'ALMACENAMIENTO')
                ->where('customer', $rowData['customer'])
                ->where('is_active', true)
                ->first();

            if (!$location) {
                $this->addError(
                    $rowNumber,
                    'Ubicación',
                    "La ubicación 'ALMACENAMIENTO' no está registrada para el cliente '{$rowData['customer']}'",
                    "Debe crear la ubicación ALMACENAMIENTO primero"
                );
                return false;
            }

            $rowData['location_id'] = $location->location_id;
            return true; // No validamos capacidad para ALMACENAMIENTO
        }

        // CASO 2: UBICACIÓN NORMAL (cualquier otra ubicación)
        $location = Location::where('code', $locationCode)
            ->where('warehouse', $rowData['warehouse'])
            ->where('customer', $rowData['customer'])
            ->where('is_active', true)
            ->first();

        if (!$location) {
            // Obtener ubicaciones disponibles para mostrar en el error
            $availableLocations = Location::where('warehouse', $rowData['warehouse'])
                ->where('customer', $rowData['customer'])
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();

            $this->addError(
                $rowNumber,
                'Ubicación',
                "La ubicación '{$rowData['localizacion']}' no existe para bodega '{$rowData['warehouse']}' y cliente '{$rowData['customer']}'",
                "Ubicaciones disponibles: [" . implode(', ', $availableLocations) . "]"
            );
            return false;
        }

        $rowData['location_id'] = $location->location_id;

        // ✅ CAMBIO CRÍTICO: Ya NO bloqueamos si el producto no está previamente asignado
        // Solo verificamos capacidad disponible

        // Calcular stock actual y capacidad (Optimizado: Sin vista)
        // Calculamos stock real usando helper para mantener validación estricta
        $currentStock = ItemLocationStockHelper::calculateCurrentStock($itemId, $location->code);
        $maxCapacity = 1000; // Capacidad por defecto

        $availableCapacity = $maxCapacity - $currentStock;

        // Validar capacidad disponible
        if ($rowData['quantity'] > $availableCapacity) {
            $this->addError(
                $rowNumber,
                'Capacidad',
                "No hay espacio suficiente en ubicación '{$rowData['localizacion']}'",
                "Stock actual: {$currentStock}/{$maxCapacity} | Disponible: {$availableCapacity} | Intentando agregar: {$rowData['quantity']}"
            );
            return false;
        }

        return true;
    }



    private function validateNumericValues($rowData, $rowNumber)
    {
        if (!is_numeric($rowData['value']) || $rowData['value'] < 0) {
            $this->addError(
                $rowNumber,
                'Valor',
                "El valor debe ser numérico >= 0"
            );
            return false;
        }
        return true;
    }

    private function processAllRows()
    {
        foreach ($this->validatedRows as $rowData) {
            $existingInventory = Inventory::where('warehouse', $rowData['warehouse'])
                ->where('sku', $rowData['sku'])
                ->where('customer', $rowData['customer'])
                ->where('localizacion', $rowData['localizacion'])
                ->first();

            $inventoryId = $existingInventory ? $existingInventory->inventory_id : $this->generateNewInventoryId();

            $inventory = new Inventory([
                'inventory_id' => $inventoryId,
                'item_id' => $rowData['item_id'],
                'warehouse' => $rowData['warehouse'],
                'localizacion' => $rowData['localizacion'],
                'location_id' => $rowData['location_id'],
                'item_description' => $rowData['itemDescription'],
                'sku' => $rowData['sku'],
                'status' => 'INGRESO',
                'batch' => $rowData['batch'],
                'expiry_date' => $this->parseDate($rowData['expiry_date']),
                'item_condition' => $rowData['item_condition'],
                'entry_date' => $this->parseDate($rowData['entry_date']),
                'commerce' => $rowData['commerce'],
                'quantity' => $rowData['quantity'],
                'value' => $rowData['value'],
                'type' => $rowData['type'],
                'customer' => $rowData['customer'],
                'observations' => $rowData['observations'],
            ]);

            $inventory->save();

            // ✅ ACTUALIZAR item_locations (excepto ALMACENAMIENTO)
            if (strtoupper($rowData['localizacion']) !== 'ALMACENAMIENTO') {
                $location = Location::find($rowData['location_id']);

                if ($location) {
                    $itemLocation = ItemLocation::where('item_id', $rowData['item_id'])
                        ->where('location_id', $location->location_id)
                        ->first();

                    if ($itemLocation) {
                        // ✅ USAR HELPER PARA ACTUALIZAR current_quantity
                        ItemLocationStockHelper::updateCurrentQuantity(
                            $rowData['item_id'],
                            $location->location_id
                        );
                    } else {
                        // Crear nueva entrada
                        ItemLocation::create([
                            'item_id' => $rowData['item_id'],
                            'location_id' => $location->location_id,
                            'inventory_id' => $inventory->id,
                            'quantity' => $rowData['quantity'],
                            'current_quantity' => $rowData['quantity'],
                            'max_capacity' => 1000,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            Log::info('✅ Ítem procesado', [
                'row' => $rowData['row_number'],
                'sku' => $rowData['sku'],
                'quantity' => $rowData['quantity'],
                'location' => $rowData['localizacion'],
            ]);
        }
    }


    private function addError($row, $field, $message, $detail = '')
    {
        $this->errors[] = [
            'row' => $row,
            'field' => $field,
            'message' => $message,
            'detail' => $detail,
            'type' => $this->categorizeError($field)
        ];
    }
    private function addWarning($row, $category, $message)
    {
        $this->warnings[] = [
            'row' => $row,
            'category' => $category,
            'message' => $message
        ];
    }

    private function categorizeError($field)
    {
        $categories = [
            'estructura' => ['SKU', 'Descripción', 'Bodega', 'Cliente', 'Cantidad', 'Ubicación'],
            'permisos' => ['Permisos'],
            'validacion' => ['Fecha Vencimiento', 'Fecha Ingreso', 'Valor'],
            'capacidad' => ['Capacidad'],
        ];

        foreach ($categories as $category => $fields) {
            if (in_array($field, $fields)) {
                return $category;
            }
        }

        return 'general';
    }

    private function generateErrorReport()
    {
        Log::error('❌ Errores en importación', [
            'total_errors' => count($this->errors),
            'errors' => $this->errors
        ]);
    }

    private function groupErrorsByType()
    {
        $grouped = [];
        foreach ($this->errors as $error) {
            $type = $error['type'] ?? 'general';
            $grouped[$type] = ($grouped[$type] ?? 0) + 1;
        }
        return $grouped;
    }

    private function formatErrorReport()
    {
        $report = "\n";
        $report .= "╔═══════════════════════════════════════════════════════════════════╗\n";
        $report .= "║          ❌ REPORTE DE ERRORES DE IMPORTACIÓN ❌                 ║\n";
        $report .= "╚═══════════════════════════════════════════════════════════════════╝\n\n";

        $report .= "🔴 SE ENCONTRARON " . count($this->errors) . " ERRORES CRÍTICOS\n";
        $report .= "⚠️  LA IMPORTACIÓN HA SIDO BLOQUEADA - NO SE REALIZARON CAMBIOS\n\n";

        // Agrupar errores por tipo
        $errorsByType = [];
        foreach ($this->errors as $error) {
            $type = $error['type'] ?? 'general';
            $errorsByType[$type][] = $error;
        }

        $typeLabels = [
            'estructura' => '📋 ERRORES DE ESTRUCTURA/DATOS REQUERIDOS',
            'permisos' => '🔒 ERRORES DE PERMISOS',
            'validacion' => '✏️  ERRORES DE VALIDACIÓN DE DATOS',
            'capacidad' => '📦 ERRORES DE CAPACIDAD',
            'general' => '⚠️  OTROS ERRORES'
        ];

        foreach ($errorsByType as $type => $errors) {
            $label = $typeLabels[$type] ?? '⚠️  OTROS ERRORES';
            $report .= "\n{$label} (" . count($errors) . "):\n";
            $report .= str_repeat("─", 70) . "\n";

            // Agrupar por fila
            $errorsByRow = [];
            foreach ($errors as $error) {
                $errorsByRow[$error['row']][] = $error;
            }

            foreach ($errorsByRow as $row => $rowErrors) {
                $report .= "\n  🔸 FILA {$row}:\n";
                foreach ($rowErrors as $error) {
                    $report .= "     • {$error['field']}: {$error['message']}\n";
                    if (!empty($error['detail'])) {
                        $report .= "       ↳ {$error['detail']}\n";
                    }
                }
            }
        }

        // Advertencias
        if (!empty($this->warnings)) {
            $report .= "\n\n";
            $report .= "╔═══════════════════════════════════════════════════════════════════╗\n";
            $report .= "║                    ⚠️  ADVERTENCIAS                              ║\n";
            $report .= "╚═══════════════════════════════════════════════════════════════════╝\n";
            $report .= "(Las advertencias son informativas y no bloquean la importación)\n\n";

            foreach ($this->warnings as $warning) {
                $report .= "  ⚠️  Fila {$warning['row']}: {$warning['message']}\n";
            }
        }

        // Resumen
        $report .= "\n\n";
        $report .= "╔═══════════════════════════════════════════════════════════════════╗\n";
        $report .= "║                        📊 RESUMEN                                 ║\n";
        $report .= "╚═══════════════════════════════════════════════════════════════════╝\n";
        $report .= "  🔴 Errores críticos:        " . count($this->errors) . "\n";
        $report .= "  ⚠️  Advertencias:            " . count($this->warnings) . "\n";
        $report .= "  ✅ Filas válidas:            " . count($this->validatedRows) . "\n";
        $report .= "  📄 Filas totales analizadas: " . (count($this->errors) + count($this->validatedRows)) . "\n";

        // Instrucciones
        $report .= "\n";
        $report .= "╔═══════════════════════════════════════════════════════════════════╗\n";
        $report .= "║                    📝 INSTRUCCIONES                               ║\n";
        $report .= "╚═══════════════════════════════════════════════════════════════════╝\n";
        $report .= "  1. ✏️  Corrija TODOS los errores marcados con 🔴\n";
        $report .= "  2. ℹ️  Las advertencias ⚠️ son informativas (no bloquean)\n";
        $report .= "  3. 🔄 Vuelva a subir el archivo una vez corregidos los errores\n";
        $report .= "  4. ✅ Si todas las filas son válidas, la importación se completará\n\n";

        return $report;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    private function generateNewInventoryId()
    {
        $lastInventory = Inventory::whereRaw('inventory_id REGEXP "^[0-9]+$"')
            ->orderByRaw('CAST(inventory_id AS UNSIGNED) DESC')
            ->first();
        return $lastInventory ? strval(intval($lastInventory->inventory_id) + 1) : '1';
    }

    private function parseDate($date)
    {
        if (!$date)
            return null;

        if (is_numeric($date)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date))->format('Y-m-d');
        }

        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Formato de fecha no reconocido: '$date'");
    }
}