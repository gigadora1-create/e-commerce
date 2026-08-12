<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = User::query();

        if (!empty($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('email', 'LIKE', '%' . $search . '%');
        }

        $profiles = $query->orderBy('created_at', 'DESC')->paginate(10);

        return view('profile.index', compact('profiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'address' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'user_type' => 'required|in:Administrador,Usuario',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'telephone' => $validated['telephone'],
            'password' => bcrypt($validated['password']),
            'user_type' => $validated['user_type'],
        ]);

        return response()->json(['message' => 'Usuario agregado exitosamente'], 200);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'address' => $user->address,
            'user_type' => $user->user_type,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'address' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'user_type' => 'required|in:Administrador,Usuario',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'telephone' => $validated['telephone'],
            'user_type' => $validated['user_type'],
            'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password,
        ]);

        return response()->json(['message' => 'Usuario actualizado exitosamente'], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado con éxito'], 200);
    }
}
