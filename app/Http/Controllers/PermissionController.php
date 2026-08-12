<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
    
        $query = Permission::query();
    
        if (!empty($search)) {
            $query->where('id', 'LIKE', '%' . $search . '%');
            $query->orWhere('name', 'LIKE', '%' . $search . '%');
            // Agrega más condiciones según tus necesidades
        }
    
        $Permissions = $query->orderBy('created_at', 'DESC')->paginate(10);
    
        return view('permission.index', compact('Permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        // Validar y asignar el campo name
        $validatedData = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);
    
        // Asignar automáticamente el valor 'web' al campo guard_name
        $validatedData['guard_name'] = 'web';
    
        // Crear el nuevo rol con los datos validados
        Permission::create($validatedData);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    
        // Redireccionar con un mensaje de éxito
        return redirect()->route('permissions.index')->with('success', 'Permiso agregado exitosamente');
    }
    

    public function show($id)
    {
        $user = Permission::findOrFail($id);

        return view('permission.show', compact('user'));
    }

    public function edit($id)
    {
        $user = Permission::findOrFail($id);

        return view('permission.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = Permission::findOrFail($id);

        $user->update($request->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado exitosamente');
    }

    public function destroy($id)
    {
        $user = Permission::findOrFail($id);

        $user->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado con éxito');
    }
}
