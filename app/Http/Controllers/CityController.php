<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::all();
        return view('cities.index', compact('cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'city_name' => 'required|string|max:255',
            'city_store' => 'required|string|max:255',
        ]);

        $city = City::create($request->only(['city_name', 'city_store']));

        return response()->json(['success' => 'Ciudad creada correctamente']);
    }

    public function update(Request $request, $city_id)
    {
        try {
            $city = City::findOrFail($city_id);
            $request->validate([
                'city_name' => 'required|string|max:255',
                'city_store' => 'required|string|max:255',
            ]);

            $city->update($request->only(['city_name', 'city_store']));

            return response()->json(['success' => 'Ciudad actualizada correctamente']);
        } catch (\Exception $e) {
            Log::error("Error actualizando ciudad: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo actualizar la ciudad: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($city_id)
    {
        try {
            $city = City::findOrFail($city_id);
            $city->delete();

            return response()->json(['success' => 'Ciudad eliminada correctamente']);
        } catch (\Exception $e) {
            Log::error("Error eliminando ciudad: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo eliminar la ciudad: ' . $e->getMessage()], 500);
        }
    }
}
