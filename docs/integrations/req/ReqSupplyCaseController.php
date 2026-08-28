<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReqSupplyCaseController
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            hash_equals((string) config('services.ecommerce_supply.token'), (string) $request->bearerToken()),
            401,
            'Token de integracion invalido.'
        );

        $payload = $request->validate([
            'source' => ['required', 'in:e-commerce'],
            'source_request_id' => ['required', 'integer', 'min:1'],
            'source_request_number' => ['required', 'string', 'max:80'],
            'requested_at' => ['nullable', 'date'],
            'requester.name' => ['required', 'string', 'max:255'],
            'requester.email' => ['required', 'email', 'max:255'],
            'client.name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.source_item_id' => ['required', 'integer', 'min:1'],
            'items.*.catalog_number' => ['required', 'string', 'max:80'],
            'items.*.description' => ['required', 'string', 'max:1000'],
            'items.*.requested_quantity' => ['required', 'integer', 'min:1'],
        ]);

        $email = mb_strtolower($payload['requester']['email']);
        $userExists = DB::table('users')->where('email', $email)->exists();
        $center = DB::table('centros')->where('mail', $email)->first();

        if (!$userExists || !$center) {
            return response()->json([
                'message' => 'El usuario remitente no esta registrado o no tiene centro asignado en req.',
            ], 422);
        }

        $case = DB::transaction(function () use ($payload, $email, $center) {
            $caseId = DB::table('suministros')->insertGetId([
                'solicitante' => $payload['requester']['name'],
                'email' => $email,
                'fecha_solicitud' => $payload['requested_at'] ? Carbon::parse($payload['requested_at'])->toDateString() : now()->toDateString(),
                'proceso' => $center->proceso,
                'centro_costo' => $center->centro,
                'regional' => $center->regional,
                'tipo' => 'Otros suministros',
                'estado' => 'En proceso',
                'nota' => $payload['notes'],
                'updated_at' => now(),
            ]);

            DB::table('detalles')->insert(collect($payload['items'])->map(fn (array $item) => [
                'id' => $caseId,
                'cantidad' => $item['requested_quantity'],
                'descripcion' => '[' . $item['catalog_number'] . '] ' . $item['description'],
                'estado_item' => 'En proceso',
                'updated_at' => now(),
            ])->all());

            return DB::table('suministros')->where('id', $caseId)->first();
        });

        return response()->json([
            'data' => [
                'case_id' => (int) $case->id,
                'case_number' => (string) $case->id,
                'status' => $case->estado,
            ],
        ], 201);
    }
}
