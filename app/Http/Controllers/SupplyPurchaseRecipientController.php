<?php

namespace App\Http\Controllers;

use App\Models\SupplyPurchaseRecipient;
use Illuminate\Http\Request;

class SupplyPurchaseRecipientController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->can('supplies.admin'), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:supply_purchase_recipients,email'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SupplyPurchaseRecipient::create([
            'name' => trim((string) ($validated['name'] ?? '')),
            'email' => mb_strtolower(trim($validated['email'])),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'requests'])
            ->with('success', 'Destinatario de compras creado correctamente.');
    }

    public function update(Request $request, SupplyPurchaseRecipient $recipient)
    {
        abort_unless($request->user()?->can('supplies.admin'), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:supply_purchase_recipients,email,' . $recipient->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $recipient->update([
            'name' => trim((string) ($validated['name'] ?? '')),
            'email' => mb_strtolower(trim($validated['email'])),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('supplies.index', ['tab' => 'requests'])
            ->with('success', 'Destinatario de compras actualizado correctamente.');
    }

    public function destroy(Request $request, SupplyPurchaseRecipient $recipient)
    {
        abort_unless($request->user()?->can('supplies.admin'), 403);

        $recipient->delete();

        return redirect()
            ->route('supplies.index', ['tab' => 'requests'])
            ->with('success', 'Destinatario de compras eliminado correctamente.');
    }
}
