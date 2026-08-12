<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Item;
use App\Models\ItemLocation;

class ItemController extends Controller
{
    public function index()
    {
        return view('items.index');
    }

    public function getData()
    {
        try {
            $items = Item::with(['locations:location_id,code,name', 'itemLocations'])
                        ->select('item_id', 'name', 'description', 'sku', 'ruta', 'barcode')
                        ->get()
                        ->map(function($item) {
                            $primaryLocation = $item->locations->first();
                            return [
                                'item_id' => $item->item_id,
                                'name' => $item->name,
                                'description' => $item->description,
                                'sku' => $item->sku,
                                'barcode' => $item->barcode,
                                'ruta' => $item->ruta,
                                'image_url' => $item->ruta ? asset('images/' . $item->ruta) : null,
                                'primary_location' => $primaryLocation ? [
                                'location_id' => $primaryLocation->location_id,
                                    'code' => $primaryLocation->code,
                                    'name' => $primaryLocation->name,
                                    'quantity' => $primaryLocation->pivot->current_quantity ?? 0
                                ] : null,
                                'total_in_locations' => $item->getTotalInLocations(),
                                'has_locations' => $item->hasLocations()
                            ];
                        });
            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            Log::error('Error en getData: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => 'Error al obtener datos'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'sku'         => 'required|string|unique:items',
            'barcode'     => 'nullable|string|unique:items',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            $imageName = null;
            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->extension();
                $request->image->move(public_path('images'), $imageName);
            }

            $item = Item::create([
                'name'        => $request->name,
                'description' => $request->description,
                'sku'         => $request->sku,
                'barcode'     => $request->barcode,
                'ruta'        => $imageName
            ]);

            return response()->json([
                'success' => 'Producto creado correctamente',
                'item' => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el producto'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = Item::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:255',
                'description' => 'required|string',
                'sku'         => 'required|string|unique:items,sku,' . $id . ',item_id',
                'barcode'     => 'nullable|string|unique:items,barcode,' . $id . ',item_id',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()]);
            }

            $data = $request->only('name', 'description', 'sku', 'barcode');

            if ($request->hasFile('image')) {
                if ($item->ruta && file_exists(public_path('images/' . $item->ruta))) {
                    unlink(public_path('images/' . $item->ruta));
                }
                $imageName = time() . '.' . $request->image->extension();
                $request->image->move(public_path('images'), $imageName);
                $data['ruta'] = $imageName;
            }

            $item->update($data);

            return response()->json(['success' => 'Producto actualizado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el producto'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $item = Item::findOrFail($id);

            if ($item->hasLocations()) {
                return response()->json([
                    'error' => 'No se puede eliminar el producto porque tiene ubicaciones asignadas. Primero debe remover el producto de todas las ubicaciones.'
                ], 400);
            }

            if ($item->ruta && file_exists(public_path('images/' . $item->ruta))) {
                unlink(public_path('images/' . $item->ruta));
            }

            $item->delete();

            return response()->json(['success' => 'Producto eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo eliminar el producto'], 500);
        }
    }

    public function getItemLocations($id)
    {
        try {
            $item = Item::with(['itemLocations.location:location_id,code,name'])
                       ->findOrFail($id);

            $locations = $item->itemLocations->map(function($itemLocation) {
                return [
                    'location_id' => $itemLocation->location->location_id,
                    'code' => $itemLocation->location->code,
                    'name' => $itemLocation->location->name,
                    'quantity' => $itemLocation->current_quantity,
                    'assigned_at' => $itemLocation->assigned_at
                ];
            });

            return response()->json([
                'item' => [
                    'item_id' => $item->item_id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'image_url' => $item->ruta ? asset('images/' . $item->ruta) : null
                ],
                'locations' => $locations
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener ubicaciones del producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las ubicaciones'], 500);
        }
    }

    public function getItemsWithoutLocation()
    {
        try {
            $items = Item::whereDoesntHave('itemLocations')
                        ->select('item_id', 'name', 'sku', 'ruta', 'barcode')
                        ->get()
                        ->map(function($item) {
                            return [
                                'item_id' => $item->item_id,
                                'name' => $item->name,
                                'sku' => $item->sku,
                                'barcode' => $item->barcode,
                                'image_url' => $item->ruta ? asset('images/' . $item->ruta) : null
                            ];
                        });
            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos sin ubicación: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => 'Error al obtener productos'], 500);
        }
    }

    public function getLocationReport()
    {
        try {
            $report = ItemLocation::with(['item:item_id,name,sku,ruta,barcode', 'location:location_id,code,name'])
                                ->get()
                                ->groupBy('location.code')
                                ->map(function($locationItems, $locationCode) {
                                    $location = $locationItems->first()->location;
                                    $items = $locationItems->map(function($itemLocation) {
                                        return [
                                            'item_id' => $itemLocation->item->item_id,
                                            'name' => $itemLocation->item->name,
                                            'sku' => $itemLocation->item->sku,
                                            'barcode' => $itemLocation->item->barcode,
                                            'image_url' => $itemLocation->item->ruta ? asset('images/' . $itemLocation->item->ruta) : null,
                                            'quantity' => $itemLocation->current_quantity,
                                            'assigned_at' => $itemLocation->assigned_at
                                        ];
                                    });
                                    return [
                                        'location' => [
                                            'location_id' => $location->location_id,
                                            'code' => $location->code,
                                            'name' => $location->name
                                        ],
                                        'items' => $items,
                                        'total_items' => $items->count(),
                                        'total_quantity' => $items->sum('quantity')
                                    ];
                                });
            return response()->json(['data' => $report]);
        } catch (\Exception $e) {
            Log::error('Error al generar reporte: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => 'Error al generar el reporte'], 500);
        }
    }
}
