<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'idsku' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive,pending',
            'batch' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'item_condition' => 'nullable|string|in:new,used,damaged',
            'entry_date' => 'nullable|date',
            'warehouse' => 'required|string|max:255',
            'localizacion' => 'required|string|max:255', // Código de ubicación
            'commerce' => 'nullable|string|max:255',
            'item_description' => 'required|string|max:500',
            'quantity' => 'required|integer|min:1',
            'value' => 'nullable|numeric|min:0',
            'type' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'document_path' => 'nullable|string|max:500',
            'customer' => 'required|string|max:255',
            'customer_id' => 'required|integer',
            'item_id' => 'required|integer|exists:items,id',
            'city_id' => 'nullable|integer',
            'max_capacity' => 'nullable|integer|min:1' // Para nuevas ubicaciones
        ];
    }

    public function messages()
    {
        return [
            'localizacion.required' => 'El código de ubicación es obligatorio',
            'quantity.min' => 'La cantidad debe ser mayor a 0',
            'item_id.exists' => 'El item especificado no existe'
        ];
    }
}
