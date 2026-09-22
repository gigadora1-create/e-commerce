<?php

namespace App\Imports;

use App\Models\SupplyProduct;
use App\Models\SupplyStockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplyProductsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use Importable;

    private int $createdCount = 0;
    private int $updatedCount = 0;
    private int $stockAdded = 0;
    private array $errors = [];

    public function __construct(private readonly ?int $userId)
    {
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $catalogNumber = $this->integerValue($row['id_catalogo'] ?? null);
                $name = trim((string) ($row['nombre'] ?? ''));
                $description = trim((string) ($row['descripcion'] ?? ''));
                $stockInitial = $this->integerValue($row['stock_inicial'] ?? null);

                if ($catalogNumber === null && $name === '' && $stockInitial === null) {
                    continue;
                }

                $rowErrors = [];

                if ($catalogNumber === null || $catalogNumber < 1) {
                    $rowErrors[] = 'ID_CATALOGO debe ser un entero mayor que cero';
                }

                if ($stockInitial === null || $stockInitial < 0) {
                    $rowErrors[] = 'STOCK_INICIAL debe ser un entero igual o mayor que cero';
                }

                $product = $catalogNumber ? SupplyProduct::query()
                    ->where('catalog_number', $catalogNumber)
                    ->lockForUpdate()
                    ->first() : null;

                if (!$product && $name === '') {
                    $rowErrors[] = 'NOMBRE es obligatorio para un producto nuevo';
                }

                if ($rowErrors !== []) {
                    $this->errors[] = 'Fila ' . $rowNumber . ': ' . implode('. ', $rowErrors) . '.';
                    continue;
                }

                if (!$product) {
                    $product = SupplyProduct::query()->create([
                        'catalog_number' => $catalogNumber,
                        'name' => $name,
                        'description' => $description !== '' ? $description : null,
                        'stock_on_hand' => 0,
                        'reserved_stock' => 0,
                        'minimum_stock' => 0,
                        'medium_stock' => 0,
                        'is_active' => true,
                    ]);
                    $this->createdCount++;
                } else {
                    $payload = [];

                    if ($name !== '') {
                        $payload['name'] = $name;
                    }

                    if ($description !== '') {
                        $payload['description'] = $description;
                    }

                    if ($payload !== []) {
                        $product->update($payload);
                    }

                    $this->updatedCount++;
                }

                if ($stockInitial > 0) {
                    $product->stock_on_hand = (int) $product->stock_on_hand + $stockInitial;
                    $product->save();

                    SupplyStockMovement::query()->create([
                        'supply_product_id' => $product->id,
                        'user_id' => $this->userId,
                        'movement_type' => 'initial_catalog_import',
                        'quantity' => $stockInitial,
                        'stock_on_hand_after' => (int) $product->stock_on_hand,
                        'reserved_stock_after' => (int) $product->reserved_stock,
                        'reference_type' => SupplyProduct::class,
                        'reference_id' => $product->id,
                        'notes' => 'Carga inicial de catalogo de proveeduria. Fila ' . $rowNumber . '.',
                    ]);
                    $this->stockAdded += $stockInitial;
                }
            }

            if ($this->errors !== []) {
                throw new \RuntimeException(implode(' ', $this->errors));
            }
        });
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getStockAdded(): int
    {
        return $this->stockAdded;
    }

    private function integerValue(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) === false ? null : (int) $value;
    }

}
