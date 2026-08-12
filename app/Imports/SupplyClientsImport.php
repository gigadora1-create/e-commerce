<?php

namespace App\Imports;

use App\Models\SupplyClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplyClientsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use Importable;

    private int $processedCount = 0;
    private array $errors = [];

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $name = trim((string) ($row['nombre'] ?? ''));
                $address = trim((string) ($row['direccion'] ?? ''));
                $city = trim((string) ($row['ciudad'] ?? ''));

                if ($name === '' && $address === '' && $city === '') {
                    continue;
                }

                $rowErrors = [];

                if ($name === '') {
                    $rowErrors[] = 'NOMBRE es obligatorio';
                }

                if ($address === '') {
                    $rowErrors[] = 'DIRECCIÓN es obligatoria';
                }

                if ($city === '') {
                    $rowErrors[] = 'CIUDAD es obligatoria';
                }

                if ($rowErrors !== []) {
                    $this->errors[] = 'Fila ' . $rowNumber . ': ' . implode('. ', $rowErrors) . '.';
                    continue;
                }

                $client = SupplyClient::query()->firstOrNew(['name' => $name]);
                $client->address = $address;
                $client->city = $city;

                if (!$client->exists) {
                    $client->is_active = true;
                }

                $client->save();
                $this->processedCount++;
            }
        });

        if ($this->errors !== []) {
            throw new \RuntimeException(implode(' ', $this->errors));
        }
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }
}
