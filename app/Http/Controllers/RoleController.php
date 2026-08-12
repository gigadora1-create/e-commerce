<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Role::query();

        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('id', 'LIKE', '%' . $search . '%')
                    ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }

        $Roles = $query->orderBy('created_at', 'DESC')->paginate(10);

        return view('roles.index', compact('Roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $validatedData['guard_name'] = 'web';

        Role::create($validatedData);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Rol agregado exitosamente');
    }

    public function show($id)
    {
        $user = Role::findOrFail($id);

        return view('roles.show', compact('user'));
    }

    public function edit($id)
    {
        $user = Role::findOrFail($id);

        return view('roles.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = Role::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $user->id,
            'guard_name' => 'nullable|string|max:255',
        ]);

        $validatedData['guard_name'] = $validatedData['guard_name'] ?? $user->guard_name ?? 'web';

        $user->update($validatedData);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente');
    }

    public function destroy($id)
    {
        $user = Role::findOrFail($id);

        $user->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado con éxito');
    }
}
