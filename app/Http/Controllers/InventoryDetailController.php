<?php
namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\ItemLocation;
use App\Models\Item;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InventoryDetailController extends Controller
{
    public function index(Request $request)
    {
        if (!session('selected_customer')) {
            return redirect()->route('inventories.index');
        }
        $user = auth()->user();
        $warehouse = $request->input('warehouse');
        $product = $request->input('product');
        $location = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selectedCustomer = session('selected_customer');
        $detailedInventories = new Collection();
        $cityPermissions = [];
        $uniqueProducts = new Collection();
        $uniqueWarehouses = new Collection();
        $uniqueLocations = new Collection();

        // Cargar inventarios sin relaciones innecesarias
        $detailedQuery = Inventory::where('customer', $selectedCustomer)
            ->where('status', 'INGRESO')
            ->orderBy('entry_date', 'desc');

        if ($warehouse) {
            $detailedQuery->where('warehouse', $warehouse);
        }
        if ($product) {
            $detailedQuery->where('item_description', $product);
        }
        if ($location) {
            $detailedQuery->whereHas('itemLocations.location', function($query) use ($location) {
                $query->where('name', $location);
            });
        }
        if ($startDate) {
            $detailedQuery->where('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $detailedQuery->where('entry_date', '<=', $endDate);
        }

        $detailedInventories = $detailedQuery->get();

        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $cityPermissions = array_filter($userPermissions, function($permission) {
            return !in_array($permission, ['password.create', 'user.management']);
        });

        $uniqueProducts = Item::distinct()->pluck('name');
        $uniqueWarehouses = Inventory::where('customer', $selectedCustomer)->distinct()->pluck('warehouse');

        $uniqueLocations = ItemLocation::whereHas('item', function($query) use ($selectedCustomer) {
            $query->whereHas('inventories', function($query) use ($selectedCustomer) {
                $query->where('customer', $selectedCustomer);
            });
        })
        ->with('location')
        ->get()
        ->pluck('location.name')
        ->unique()
        ->values();

        return view('inventories.details', compact(
            'detailedInventories',
            'cityPermissions',
            'uniqueProducts',
            'uniqueWarehouses',
            'uniqueLocations',
            'warehouse',
            'product',
            'location',
            'startDate',
            'endDate'
        ));
    }

    public function getSkuByDescription($description)
    {
        Log::info('Buscando SKU para name: ' . urldecode($description));
        $item = Item::whereRaw('LOWER(name) = ?', [strtolower(trim(urldecode($description)))])->first();
        if ($item) {
            Log::info('SKU encontrado: ' . $item->sku);
            return response()->json(['sku' => $item->sku]);
        }
        Log::warning('No se encontró SKU para name: ' . urldecode($description));
        return response()->json(['sku' => null], 404);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sku' => 'required',
            'status' => 'required',
            'batch' => 'required',
            'expiry_date' => 'required|date',
            'item_condition' => 'required',
            'entry_date' => 'required|date',
            'warehouse' => 'required',
            'commerce' => 'required',
            'item_description' => 'required',
            'quantity' => 'required|integer',
            'value' => 'required|numeric',
            'type' => 'required',
            'observations' => 'nullable',
            'entry_document' => 'nullable|string|max:255',
            'document' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('documents/ingresos'), $filename);
            $validatedData['document_path'] = 'documents/ingresos/' . $filename;
        }

        $validatedData['item_description'] = trim($validatedData['item_description']);

        $item = Item::where('sku', $validatedData['sku'])
                    ->orWhere('name', $validatedData['item_description'])
                    ->first();

        if (!$item) {
            return redirect()->back()->with('error', 'No se encontró un producto con el SKU o descripción proporcionada.');
        }

        $validatedData['item_id'] = $item->item_id;

        $existingInventory = Inventory::where('warehouse', $validatedData['warehouse'])
                                      ->where('item_description', $validatedData['item_description'])
                                      ->where('sku', $validatedData['sku'])
                                      ->where('customer', session('selected_customer'))
                                      ->first();

        if ($existingInventory) {
            $validatedData['inventory_id'] = $existingInventory->inventory_id;
        } else {
            $validatedData['inventory_id'] = $this->generateNewInventoryId();
        }

        if (session('selected_customer')) {
            $validatedData['customer'] = session('selected_customer');
        }

        Inventory::create($validatedData);

        return redirect()->route('inventory-details.index')->with('success', 'Inventario creado exitosamente.');
    }

    private function generateNewInventoryId()
    {
        $lastInventory = Inventory::whereRaw('inventory_id REGEXP "^[0-9]+$"')
            ->orderByRaw('CAST(inventory_id AS UNSIGNED) DESC')
            ->first();
        return $lastInventory ? strval(intval($lastInventory->inventory_id) + 1) : '1';
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $validatedData = $request->validate([
            'sku' => 'required',
            'status' => 'required',
            'batch' => 'required',
            'expiry_date' => 'required|date',
            'item_condition' => 'required',
            'entry_date' => 'required|date',
            'warehouse' => 'required',
            'commerce' => 'required',
            'item_description' => 'required',
            'quantity' => 'required|integer',
            'value' => 'required|numeric',
            'type' => 'required',
            'observations' => 'nullable',
            'entry_document' => 'nullable|string|max:255',
            'document' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        if ($request->hasFile('document')) {
            if ($inventory->document_path && File::exists(public_path($inventory->document_path))) {
                File::delete(public_path($inventory->document_path));
            }
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('documents/ingresos'), $filename);
            $validatedData['document_path'] = 'documents/ingresos/' . $filename;
        }

        $validatedData['item_description'] = trim($validatedData['item_description']);

        if (session('selected_customer')) {
            $validatedData['customer'] = session('selected_customer');
        }

        $inventory->update($validatedData);

        return redirect()->route('inventory-details.index')->with('success', 'Inventario actualizado correctamente.');
    }

    public function destroy($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);

            if ($inventory->relationLoaded('itemLocations')) {
                $inventory->itemLocations()->delete();
            }

            if ($inventory->document_path && File::exists(public_path($inventory->document_path))) {
                try {
                    File::delete(public_path($inventory->document_path));
                } catch (\Exception $e) {
                    Log::warning("No se pudo eliminar el archivo PDF {$inventory->document_path}: {$e->getMessage()}");
                }
            }

            $inventory->delete();

            return response()->json([
                'success' => 'Registro eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al eliminar inventario ID {$id}: {$e->getMessage()}");
            return response()->json([
                'error' => 'Ocurrió un error al eliminar el inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $product = $request->input('product');
        $warehouse = $request->input('warehouse');
        $location = $request->input('location');
        return Excel::download(
            new InventoryExport($startDate, $endDate, $product, $warehouse, $location, session('selected_customer')),
            'inventario_detalle_' . now()->format('Y_m_d_His') . '.xlsx'
        );
    }

    public function group(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:inventories,id',
            'entry_document' => 'nullable|string|max:255',
            'document' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $data = [];

        if ($request->filled('entry_document')) {
            $data['entry_document'] = $validatedData['entry_document'];
        }

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('documents/ingresos'), $filename);
            $data['document_path'] = 'documents/ingresos/' . $filename;
        }

        if (!empty($data)) {
            Inventory::whereIn('id', $validatedData['ids'])
                ->whereNull('entry_document')
                ->update($data);
        }

        return response()->json(['success' => 'Ingresos agrupados exitosamente.']);
    }
}