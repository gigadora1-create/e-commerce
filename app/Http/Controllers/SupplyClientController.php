<?php

namespace App\Http\Controllers;

use App\Exports\SupplyClientTemplateExport;
use App\Imports\SupplyClientsImport;
use App\Models\SupplyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SupplyClientController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('manageClients', SupplyClient::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supply_clients,name'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SupplyClient::create([
            'name' => trim($validated['name']),
            'address' => trim($validated['address']),
            'city' => trim($validated['city']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'clients'])
            ->with('success', 'Cliente de proveeduría creado correctamente.');
    }

    public function update(Request $request, SupplyClient $client)
    {
        $this->authorize('manageClients');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supply_clients,name,' . $client->id],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $client->update([
            'name' => trim($validated['name']),
            'address' => trim($validated['address']),
            'city' => trim($validated['city']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'clients'])
            ->with('success', 'Cliente de proveeduría actualizado correctamente.');
    }

    public function destroy(SupplyClient $client)
    {
        $this->authorize('manageClients');

        if ($client->supplyRequests()->exists() || $client->issueRequests()->exists()) {
            return redirect()
                ->route('supplies.index', ['tab' => 'clients'])
                ->with('error', 'No se puede eliminar el cliente porque ya tiene documentos asociados.');
        }

        $client->delete();

        return redirect()
            ->route('supplies.index', ['tab' => 'clients'])
            ->with('success', 'Cliente de proveeduría eliminado correctamente.');
    }

    public function import(Request $request)
    {
        $this->authorize('manageClients', SupplyClient::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new SupplyClientsImport();
            Excel::import($import, $request->file('file'));

            return redirect()
                ->route('supplies.index', ['tab' => 'clients'])
                ->with('success', 'Clientes de proveeduría importados correctamente. Filas procesadas: ' . $import->getProcessedCount());
        } catch (\Throwable $e) {
            Log::error('Error importando clientes de proveeduría: ' . $e->getMessage());

            return redirect()
                ->route('supplies.index', ['tab' => 'clients'])
                ->with('error', 'No se pudo importar el archivo: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $this->authorize('manageClients', SupplyClient::class);

        return Excel::download(new SupplyClientTemplateExport(), 'plantilla_clientes_proveeduria.xlsx');
    }
}
