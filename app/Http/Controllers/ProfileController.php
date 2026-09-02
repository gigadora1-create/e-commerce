<?php

namespace App\Http\Controllers;

use App\Services\HrEmployeeSyncService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index(Request $request)
    {
        // DataTables receives the full, small user directory so its filter can
        // search every user instead of only the current server-side page.
        $profiles = User::query()
            ->orderBy('name')
            ->get();

        return view('profile.index', compact('profiles'));
    }

    public function syncHr(HrEmployeeSyncService $syncService)
    {
        try {
            $result = $syncService->sync();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudieron sincronizar los usuarios desde Recursos Humanos.',
            ], 422);
        }

        return response()->json([
            'message' => sprintf(
                'Sincronización completada. Nuevos: %d, actualizados: %d, omitidos: %d.',
                $result['created'],
                $result['updated'],
                $result['skipped'],
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'address' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'process' => 'nullable|string|max:255',
            'regional' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? '',
            'telephone' => $validated['telephone'] ?? '',
            'position' => $validated['position'] ?? null,
            'process' => $validated['process'] ?? null,
            'regional' => $validated['regional'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            // Kept only for legacy database compatibility; permissions use roles.
            'user_type' => 'Usuario',
            'password' => Hash::make(Str::password(40)),
        ]);

        return response()->json(['message' => 'Usuario agregado con contraseña inicial autogenerada'], 200);
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
            'hr_employee_id' => $user->hr_employee_id,
            'position' => $user->position,
            'process' => $user->process,
            'regional' => $user->regional,
            'is_active' => $user->is_active,
            'synced_from_hr_at' => optional($user->synced_from_hr_at)->toDateTimeString(),
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
            'position' => 'nullable|string|max:255',
            'process' => 'nullable|string|max:255',
            'regional' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? '',
            'telephone' => $validated['telephone'] ?? '',
            'position' => $validated['position'] ?? null,
            'process' => $validated['process'] ?? null,
            'regional' => $validated['regional'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
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
