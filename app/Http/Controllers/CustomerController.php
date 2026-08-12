<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::all();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'is_warehouse_client' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'address']);
        $data['is_warehouse_client'] = $request->boolean('is_warehouse_client');
        Customer::create($data);

        return response()->json(['success' => 'Cliente creado correctamente']);
    }

    public function update(Request $request, $customer_id)
    {
        try {
            $customer = Customer::findOrFail($customer_id);
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:customers,email,' . $customer_id . ',customer_id',
                'phone' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'is_warehouse_client' => 'nullable|boolean',
            ]);

            $data = $request->only(['name', 'email', 'phone', 'address']);
            $data['is_warehouse_client'] = $request->boolean('is_warehouse_client');
            $customer->update($data);

            return response()->json(['success' => 'Cliente actualizado correctamente']);
        } catch (\Exception $e) {
            Log::error("Error actualizando cliente: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo actualizar el cliente: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($customer_id)
    {
        try {
            $customer = Customer::findOrFail($customer_id);
            $customer->inventories()->delete(); // Eliminar registros relacionados en inventories
            $customer->delete();

            return response()->json(['success' => 'Cliente eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error("Error eliminando cliente: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo eliminar el cliente: ' . $e->getMessage()], 500);
        }
    }
}
