<?php

// app/Http/Controllers/CategoryController.php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos de la categoría
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Crear la categoría
        Category::create([
            'name' => $request->name,
        ]);

        // Redirigir o devolver una respuesta
        return redirect()->back()->with('success', 'Categoría creada exitosamente.');
    }
}
