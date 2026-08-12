<?php

namespace App\Http\Controllers;

use App\Exports\WarehouseGuidesExport;
use App\Exports\WarehouseEntryTemplateExport;
use App\Exports\WarehouseExitTemplateExport;
use App\Models\Customer;
use App\Models\WarehouseLocation;
use App\Models\WarehouseGuide;
use App\Models\WarehouseGuideMovement;
use App\Services\CustomerAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $customers = $this->getCustomers();

        if (empty($customers) && !$this->isSuperAdmin()) {
            session()->forget(['selected_customer', 'selected_customers']);

            return redirect()
                ->route('customer.context.index')
                ->with('warning', 'No tienes clientes de bodegaje permitidos para este rol.');
        }

        $primaryCustomer = $customers[0] ?? 'SKYONE';
        $filters = $this->extractFilters($request);
        $warehouseOptions = $this->getWarehouseOptions($primaryCustomer, $customers);
        $requestedWarehouse = $this->normalizeWarehouse($filters['warehouse'] ?? null);
        $activeWarehouse = in_array($requestedWarehouse, $warehouseOptions, true)
            ? $requestedWarehouse
            : $primaryCustomer;
        $activeCustomers = $activeWarehouse ? [$activeWarehouse] : $customers;
        $this->ensureStorageLocationExists($activeWarehouse, $activeWarehouse);

        $activeTab = in_array($request->input('tab'), ['guides', 'locations', 'reports'], true)
            ? $request->input('tab')
            : ($this->isSuperAdmin() ? 'guides' : 'locations');
        
        $selectedCustomers = $activeCustomers;
        $selectedCustomerDisplay = $activeWarehouse;
        
        $locations = $this->getLocationsForCustomers($activeCustomers, $activeWarehouse);
        $locationOptions = $this->buildLocationOptions($locations);
        $customerOptions = $this->getCustomerOptions();
        $activeGuides = WarehouseGuide::whereIn('customer', $activeCustomers)
            ->active()
            ->with(['currentLocation'])
            ->withCount('movements')
            ->when($activeWarehouse !== null, fn ($query) => $query->where('warehouse', $activeWarehouse))
            ->orderByDesc('entry_at')
            ->get();
        $guidesByLocation = $activeGuides->groupBy(function ($guide) {
            return (string) ($guide->current_location_id ?? 'unassigned');
        });

        $guides = $this->buildGuideQuery($activeCustomers, $filters)
            ->with(['currentLocation', 'entryUser', 'exitUser'])
            ->withCount('movements')
            ->orderByDesc('entry_at')
            ->paginate(10)
            ->withQueryString();

        $locationCards = $locations->map(function ($location) use ($guidesByLocation) {
            $locationGuides = $guidesByLocation->get((string) $location->location_id, collect());

            return array_merge($this->formatLocation($location), [
                'active_guides_count' => (int) $locationGuides->count(),
                'guides' => $locationGuides->map(function ($guide) {
                    return $this->formatLocationGuide($guide);
                })->values()->all(),
            ]);
        })->values();

        $locationBoards = $locationCards->groupBy('warehouse')->map(function ($warehouseLocations, $warehouse) {
            return [
                'warehouse' => $warehouse,
                'locations_count' => $warehouseLocations->count(),
                'guides_count' => $warehouseLocations->sum(function ($location) {
                    return count($location['guides'] ?? []);
                }),
                'locations' => $warehouseLocations->values()->all(),
            ];
        })->values();

        $stats = $this->buildStats($activeCustomers, $activeWarehouse);

        return view('warehouse.index', [
            'customer' => $activeWarehouse,
            'customers' => $activeCustomers,
            'activeTab' => $activeTab,
            'filters' => $filters,
            'guides' => $guides,
            'locations' => $locations,
            'locationCards' => $locationCards,
            'locationBoards' => $locationBoards,
            'locationOptions' => $locationOptions,
            'warehouseOptions' => $warehouseOptions,
            'customerOptions' => $customerOptions,
            'selectedCustomer' => $selectedCustomerDisplay,
            'selectedCustomers' => $selectedCustomers,
            'stats' => $stats,
            'activeWarehouse' => $activeWarehouse,
        ]);
    }

    public function selectCustomer(Request $request)
    {
        if (!$this->isSuperAdmin()) {
            abort(403, 'Acceso denegado.');
        }

        $validated = $request->validate([
            'customer' => [
                'required',
                Rule::exists('customers', 'name')->where(fn ($query) => $query->where('is_warehouse_client', true)),
            ],
        ]);

        session([
            'selected_customer' => $validated['customer'],
            'selected_customers' => [$validated['customer']],
        ]);

        return redirect()
            ->route('warehouse.index', ['tab' => 'guides'])
            ->with('success', 'Cliente de bodega seleccionado correctamente.');
    }

    public function exitCustomer(Request $request)
    {
        if (!$this->isSuperAdmin()) {
            abort(403, 'Acceso denegado.');
        }

        $request->session()->forget(['selected_customer', 'selected_customers']);

        return redirect()
            ->route('warehouse.index', ['tab' => 'guides'])
            ->with('success', 'Contexto de cliente restablecido.');
    }

    public function showGuide(string $guide)
    {
        $customers = $this->getCustomers();

        $warehouseGuide = WarehouseGuide::with(['currentLocation', 'entryUser', 'exitUser', 'movements.user', 'movements.fromLocation', 'movements.toLocation'])
            ->whereIn('customer', $customers)
            ->where('guide', strtoupper(trim($guide)))
            ->firstOrFail();

        return response()->json($this->formatGuidePayload($warehouseGuide));
    }

    public function updateGuide(Request $request, string $guide)
    {
        if (!$this->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo SUPER_ADMIN puede editar ingresos.',
            ], 403);
        }

        $customers = $this->getCustomers();
        $primaryCustomer = $customers[0] ?? 'SKYONE';
        $originalGuide = strtoupper(trim($guide));

        $warehouseGuide = WarehouseGuide::with(['currentLocation', 'entryUser', 'exitUser', 'movements.user', 'movements.fromLocation', 'movements.toLocation'])
            ->whereIn('customer', $customers)
            ->where('guide', $originalGuide)
            ->firstOrFail();

        $validated = $request->validate([
            'guide' => [
                'required',
                'string',
                'max:30',
                'regex:/^GL[0-9]{9}CO$/i',
                Rule::unique('warehouse_guides', 'guide')->where(function ($query) use ($primaryCustomer) {
                    return $query->where('customer', $primaryCustomer);
                })->ignore($warehouseGuide->id),
            ],
            'location_id' => ['nullable', 'integer', 'exists:warehouse_locations,location_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'entry_source' => ['nullable', Rule::in(['manual', 'barcode'])],
        ], [
            'guide.regex' => 'La guía debe tener el formato GL000024273CO.',
        ]);

        $newGuideCode = strtoupper(trim($validated['guide']));
        $movementCount = (int) $warehouseGuide->movements()->count();
        $targetLocation = null;

        if ($request->filled('location_id')) {
            $requestedLocationId = (int) $validated['location_id'];
            $currentLocationId = (int) ($warehouseGuide->current_location_id ?? 0);

            if ($requestedLocationId !== $currentLocationId) {
                if ($warehouseGuide->exit_at) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La guía ya tiene salida registrada y no se puede cambiar su ubicación.',
                    ], 422);
                }

                if ($movementCount > 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La ubicación inicial solo se puede corregir antes de mover la guía.',
                    ], 422);
                }

                $targetLocation = $this->resolveLocation($requestedLocationId, $warehouseGuide->customer);
            }
        }

        $notes = trim((string) ($validated['notes'] ?? ''));
        $notes = $notes === '' ? null : $notes;
        $entrySource = $validated['entry_source'] ?? $warehouseGuide->entry_source;

        $updatedGuide = DB::transaction(function () use ($warehouseGuide, $newGuideCode, $notes, $entrySource, $targetLocation, $movementCount) {
            $updates = [
                'guide' => $newGuideCode,
                'entry_source' => $entrySource,
                'notes' => $notes,
            ];

            if ($targetLocation) {
                $updates['warehouse'] = $targetLocation->warehouse;
                $updates['current_location_id'] = $targetLocation->location_id;
                $updates['current_location_code'] = $targetLocation->code;
                $updates['current_location_name'] = $targetLocation->name;
            }

            $warehouseGuide->update($updates);

            if ($targetLocation && $movementCount <= 1) {
                $entryMovement = $warehouseGuide->movements()->orderBy('performed_at')->first();

                if ($entryMovement && $entryMovement->action === 'ENTRY') {
                    $entryMovement->update([
                        'to_location_id' => $targetLocation->location_id,
                        'to_location_code' => $targetLocation->code,
                        'to_location_name' => $targetLocation->name,
                    ]);
                }
            }

            return $warehouseGuide->fresh(['currentLocation', 'entryUser', 'exitUser', 'movements.user', 'movements.fromLocation', 'movements.toLocation']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ingreso actualizado correctamente.',
            'guide' => $this->formatGuidePayload($updatedGuide),
        ]);
    }

    public function destroyGuide(string $guide)
    {
        if (!$this->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo SUPER_ADMIN puede eliminar ingresos.',
            ], 403);
        }

        $customers = $this->getCustomers();
        $guideCode = strtoupper(trim($guide));

        $warehouseGuide = WarehouseGuide::whereIn('customer', $customers)
            ->where('guide', $guideCode)
            ->firstOrFail();

        $warehouseGuide->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ingreso eliminado correctamente.',
        ]);
    }

    public function storeGuide(Request $request)
    {
        $primaryCustomer = $this->resolveWarehouseCustomerFromRequest($request);
        $validated = $request->validate([
            'guide' => [
                'required',
                'string',
                'max:30',
                'regex:/^GL[0-9]{9}CO$/i',
                Rule::unique('warehouse_guides', 'guide')->where(function ($query) use ($primaryCustomer) {
                    return $query->where('customer', $primaryCustomer);
                }),
            ],
            'location_id' => ['required', 'integer', 'exists:warehouse_locations,location_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'entry_source' => ['nullable', Rule::in(['manual', 'barcode'])],
        ], [
            'guide.regex' => 'La guía debe tener el formato GL000024273CO.',
        ]);

        $guideCode = strtoupper(trim($validated['guide']));
        $location = $this->resolveLocation((int) $validated['location_id'], $primaryCustomer);
        $entrySource = $validated['entry_source'] ?? 'manual';
        $guide = $this->createGuideEntry(
            $primaryCustomer,
            $guideCode,
            $location,
            $validated['notes'] ?? null,
            $entrySource
        );

        return response()->json([
            'success' => true,
            'message' => 'Guía registrada correctamente.',
            'guide' => $this->formatGuidePayload($guide),
        ], 201);
    }

    public function moveGuide(Request $request)
    {
        $customer = $this->getCustomer();
        $validated = $request->validate([
            'guide' => ['required', 'string', 'max:30'],
            'location_id' => ['required', 'integer', 'exists:warehouse_locations,location_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $guideCode = strtoupper(trim($validated['guide']));
        $guide = WarehouseGuide::where('customer', $customer)->where('guide', $guideCode)->firstOrFail();

        if ($guide->exit_at) {
            return response()->json([
                'success' => false,
                'message' => 'La guía ya tiene salida registrada y no puede moverse.',
            ], 422);
        }

        $targetLocation = $this->resolveLocation((int) $validated['location_id'], $guide->customer);

        if ((int) $guide->current_location_id === (int) $targetLocation->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'La guía ya se encuentra en esa ubicación.',
            ], 422);
        }

        $updatedGuide = DB::transaction(function () use ($guide, $targetLocation, $validated) {
            $fromLocation = $guide->currentLocation;
            $now = now();

            WarehouseGuideMovement::create([
                'warehouse_guide_id' => $guide->id,
                'action' => 'MOVE',
                'from_location_id' => $fromLocation?->location_id,
                'from_location_code' => $fromLocation?->code ?? $guide->current_location_code,
                'from_location_name' => $fromLocation?->name ?? $guide->current_location_name,
                'to_location_id' => $targetLocation->location_id,
                'to_location_code' => $targetLocation->code,
                'to_location_name' => $targetLocation->name,
                'performed_by' => Auth::id(),
                'performed_at' => $now,
                'notes' => $validated['notes'] ?? null,
            ]);

            $guide->update([
                'warehouse' => $targetLocation->warehouse,
                'current_location_id' => $targetLocation->location_id,
                'current_location_code' => $targetLocation->code,
                'current_location_name' => $targetLocation->name,
            ]);

            return $guide->fresh(['currentLocation', 'entryUser', 'exitUser', 'movements.user', 'movements.fromLocation', 'movements.toLocation']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ubicacion actualizada correctamente.',
            'guide' => $this->formatGuidePayload($updatedGuide),
        ]);
    }

    public function exitGuide(Request $request)
    {
        $customer = $this->getCustomer();
        $validated = $request->validate([
            'guide' => ['required', 'string', 'max:30'],
            'national_guide' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $guideCode = strtoupper(trim($validated['guide']));
        $nationalGuide = strtoupper(trim($validated['national_guide']));
        $guide = WarehouseGuide::where('customer', $customer)->where('guide', $guideCode)->firstOrFail();

        if ($guide->exit_at) {
            return response()->json([
                'success' => false,
                'message' => 'La guia ya tiene salida registrada.',
            ], 422);
        }
        $updatedGuide = $this->registerGuideExit($guide, $nationalGuide, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Salida registrada correctamente.',
            'guide' => $this->formatGuidePayload($updatedGuide),
        ]);
    }

    public function exitGuidesGrouped(Request $request)
    {
        $customers = $this->getCustomers();
        $validated = $request->validate([
            'guides' => ['required', 'array', 'min:1'],
            'guides.*' => ['required', 'string', 'max:30'],
            'national_guide' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $nationalGuide = strtoupper(trim($validated['national_guide']));
        $guideCodes = collect($validated['guides'])
            ->map(fn ($guide) => strtoupper(trim((string) $guide)))
            ->filter()
            ->unique()
            ->values();

        $guides = WarehouseGuide::whereIn('customer', $customers)
            ->whereIn('guide', $guideCodes)
            ->get()
            ->keyBy(fn (WarehouseGuide $guide) => strtoupper(trim((string) $guide->guide)));

        $missingGuide = $guideCodes->first(fn (string $guideCode) => !$guides->has($guideCode));
        if ($missingGuide) {
            return response()->json([
                'success' => false,
                'message' => "La guia {$missingGuide} no existe para el cliente actual.",
            ], 422);
        }

        $exitedGuide = $guides->first(fn (WarehouseGuide $guide) => (bool) $guide->exit_at);
        if ($exitedGuide) {
            return response()->json([
                'success' => false,
                'message' => "La guia {$exitedGuide->guide} ya tiene salida registrada.",
            ], 422);
        }

        DB::transaction(function () use ($guides, $nationalGuide, $validated) {
            foreach ($guides as $guide) {
                $this->registerGuideExit($guide, $nationalGuide, $validated['notes'] ?? null);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Salida agrupada registrada correctamente.',
            'processed' => $guideCodes->count(),
            'national_guide' => $nationalGuide,
        ]);
    }

    public function importEntries(Request $request)
    {
        $importContext = $this->resolveImportContext($request);
        $primaryCustomer = $importContext['customer'];
        $warehouse = $importContext['warehouse'];
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
            'customer' => ['nullable', 'exists:customers,name'],
            'warehouse' => ['nullable', 'string', 'max:100'],
        ]);

        $locations = $this->getLocationsForCustomer($primaryCustomer, $warehouse);
        $locationLookup = $this->buildImportLocationLookup($locations);
        $rows = $this->readImportRows($validated['file']);
        $processed = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isImportRowEmpty($row)) {
                continue;
            }

            $guideCode = $this->extractImportValue($row, ['guia', 'guide']);
            $locationValue = $this->extractImportValue($row, ['localizacion', 'ubicacion', 'location']);
            $rowCustomer = $this->extractImportValue($row, ['cliente', 'customer']);

            if ($rowCustomer !== '' && !$this->sameImportValue($rowCustomer, $primaryCustomer)) {
                $errors[] = "Fila {$rowNumber}: El cliente {$rowCustomer} no coincide con el cliente seleccionado {$primaryCustomer}.";
                continue;
            }

            if ($guideCode === '' || $locationValue === '') {
                $errors[] = "Fila {$rowNumber}: Debe incluir GUIA y LOCALIZACION.";
                continue;
            }

            $guideCode = Str::upper(trim($guideCode));

            if (!preg_match('/^GL[0-9]{9}CO$/', $guideCode)) {
                $errors[] = "Fila {$rowNumber}: La guia {$guideCode} no tiene el formato GL000024273CO.";
                continue;
            }

            $location = $this->resolveImportLocation($locationValue, $locationLookup);

            if (!$location) {
                $errors[] = "Fila {$rowNumber}: La localizacion {$locationValue} no existe para el cliente y bodega seleccionados.";
                continue;
            }

            try {
                $this->createGuideEntry(
                    $primaryCustomer,
                    $guideCode,
                    $location,
                    'IMPORTACION MASIVA DE INGRESO',
                    'manual'
                );
                $processed++;
            } catch (\Throwable $exception) {
                $errors[] = "Fila {$rowNumber}: {$exception->getMessage()}";
            }
        }

        return redirect()
            ->route('warehouse.index', ['tab' => 'guides'])
            ->with($processed > 0 ? 'success' : 'warning', $this->buildImportFlashMessage('ingresos', $processed, $errors))
            ->with('warehouse_import_summary', [
                'type' => 'entries',
                'processed' => $processed,
                'errors' => $errors,
            ]);
    }

    public function importExits(Request $request)
    {
        $importContext = $this->resolveImportContext($request);
        $customer = $importContext['customer'];
        $warehouse = $importContext['warehouse'];
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
            'customer' => ['nullable', 'exists:customers,name'],
            'warehouse' => ['nullable', 'string', 'max:100'],
        ]);

        $rows = $this->readImportRows($validated['file']);
        $activeGuides = WarehouseGuide::where('customer', $customer)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->active()
            ->with(['currentLocation'])
            ->get()
            ->keyBy(fn (WarehouseGuide $guide) => Str::upper(trim((string) $guide->guide)));

        $processed = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            if ($this->isImportRowEmpty($row)) {
                continue;
            }

            $guideCode = $this->extractImportValue($row, ['guia', 'guide']);
            $nationalGuide = $this->extractImportValue($row, ['guia_nacional', 'guia_nal', 'national_guide', 'nationalguide']);
            $rowCustomer = $this->extractImportValue($row, ['cliente', 'customer']);

            if ($rowCustomer !== '' && !$this->sameImportValue($rowCustomer, $customer)) {
                $errors[] = "Fila {$rowNumber}: El cliente {$rowCustomer} no coincide con el cliente seleccionado {$customer}.";
                continue;
            }

            if ($guideCode === '' || $nationalGuide === '') {
                $errors[] = "Fila {$rowNumber}: Debe incluir las columnas GUIA y GUIA_NACIONAL.";
                continue;
            }

            $guideCode = Str::upper(trim($guideCode));
            $nationalGuide = Str::upper(trim($nationalGuide));
            $guide = $activeGuides->get($guideCode);

            if (!$guide) {
                $errors[] = "Fila {$rowNumber}: La guia {$guideCode} no esta activa o no existe para el cliente y bodega seleccionados.";
                continue;
            }

            try {
                $this->registerGuideExit($guide, $nationalGuide, 'IMPORTACION MASIVA DE SALIDA');
                $activeGuides->forget($guideCode);
                $processed++;
            } catch (\Throwable $exception) {
                $errors[] = "Fila {$rowNumber}: {$exception->getMessage()}";
            }
        }

        return redirect()
            ->route('warehouse.index', ['tab' => 'guides'])
            ->with($processed > 0 ? 'success' : 'warning', $this->buildImportFlashMessage('salidas', $processed, $errors))
            ->with('warehouse_import_summary', [
                'type' => 'exits',
                'processed' => $processed,
                'errors' => $errors,
            ]);
    }

    public function downloadEntryTemplate()
    {
        return Excel::download(
            new WarehouseEntryTemplateExport(),
            'plantilla_ingresos_bodega.xlsx'
        );
    }

    public function downloadExitTemplate()
    {
        return Excel::download(
            new WarehouseExitTemplateExport(),
            'plantilla_salidas_bodega.xlsx'
        );
    }

    public function storeLocation(Request $request)
    {
        $primaryCustomer = $this->resolveWarehouseCustomerFromRequest($request);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('warehouse_locations', 'code')->where(function ($query) use ($primaryCustomer) {
                    return $query
                        ->where('customer', $primaryCustomer)
                        ->where('warehouse', $primaryCustomer);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'warehouse' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'is_storage' => ['nullable', 'boolean'],
        ]);

        $code = Str::upper(trim($validated['code']));
        $isStorage = !empty($validated['is_storage']) || $code === 'ALMACENAMIENTO';
        if ($isStorage) {
            $code = 'ALMACENAMIENTO';
        }

        $location = DB::transaction(function () use ($primaryCustomer, $validated, $code, $isStorage) {
            return WarehouseLocation::create([
                'code' => $code,
                'customer' => $primaryCustomer,
                'name' => $validated['name'],
                'warehouse' => $primaryCustomer,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'is_storage' => $isStorage,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ubicacion creada correctamente.',
            'location' => $this->formatLocation($location),
        ], 201);
    }

    public function updateLocation(Request $request, int $locationId)
    {
        $customer = $this->getCustomer();
        $location = $this->resolveLocation($locationId, $customer, false);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('warehouse_locations', 'code')
                    ->ignore($location->location_id, 'location_id')
                    ->where(function ($query) use ($customer, $location) {
                        return $query
                            ->where('customer', $customer)
                            ->where('warehouse', $location->warehouse);
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'warehouse' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'is_storage' => ['nullable', 'boolean'],
        ]);

        if (($location->is_storage || $location->code === 'ALMACENAMIENTO') && Str::upper(trim($validated['code'])) !== 'ALMACENAMIENTO') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el codigo de la ubicacion de almacenamiento.',
            ], 422);
        }

        $isStorage = $location->is_storage || Str::upper(trim($validated['code'])) === 'ALMACENAMIENTO' || !empty($validated['is_storage']);
        $newCode = $isStorage ? 'ALMACENAMIENTO' : Str::upper(trim($validated['code']));

        if (($validated['is_active'] ?? true) === false && WarehouseGuide::where('current_location_id', $location->location_id)->whereNull('exit_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar una ubicacion que tiene guias activas.',
            ], 422);
        }

        $location->update([
            'code' => $newCode,
            'name' => $validated['name'],
            'warehouse' => $location->customer,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_storage' => $isStorage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ubicacion actualizada correctamente.',
            'location' => $this->formatLocation($location->fresh()),
        ]);
    }

    public function destroyLocation(int $locationId)
    {
        $customer = $this->getCustomer();
        $location = $this->resolveLocation($locationId, $customer, false);

        if ($location->is_storage || $location->code === 'ALMACENAMIENTO') {
            return response()->json([
                'success' => false,
                'message' => 'La ubicacion de almacenamiento no se puede eliminar.',
            ], 422);
        }

        if (WarehouseGuide::where('current_location_id', $location->location_id)->whereNull('exit_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una ubicacion con guias activas.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ubicacion eliminada correctamente.',
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'report_type' => ['nullable', Rule::in(['all', 'entries', 'exits'])],
            'national_guide' => ['nullable', 'string', 'max:60'],
            'warehouse' => ['nullable', 'string', 'max:100'],
        ]);

        $filters = $this->extractFilters($request);
        $filters['customers'] = $this->getCustomers();
        $filters['start_date'] = $validated['start_date'];
        $filters['end_date'] = $validated['end_date'];
        $filters['report_type'] = $validated['report_type'] ?? 'all';
        $filters['national_guide'] = $validated['national_guide'] ?? null;
        $filters['warehouse'] = $this->normalizeWarehouse($validated['warehouse'] ?? null);

        $fileName = 'bodega_guias_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new WarehouseGuidesExport($filters), $fileName);
    }

    private function getCustomer(): string
    {
        $customers = $this->getCustomers();
        return $customers[0] ?? 'SKYONE';
    }

    private function getCustomers(): array
    {
        $selectedCustomers = session('selected_customers', []);
        $allowedWarehouseCustomers = $this->allowedWarehouseCustomers();

        if (!empty($selectedCustomers)) {
            $warehouseCustomers = $this->filterWarehouseCustomers($selectedCustomers, $allowedWarehouseCustomers);
            if (!empty($warehouseCustomers)) {
                return $warehouseCustomers;
            }

            return $this->isSuperAdmin() ? [$this->resolveDefaultWarehouseCustomer()] : $allowedWarehouseCustomers;
        }

        // Fallback to legacy single selection if present
        $selectedCustomer = session('selected_customer');
        if (!empty($selectedCustomer)) {
            $warehouseCustomers = $this->filterWarehouseCustomers([$selectedCustomer], $allowedWarehouseCustomers);
            if (!empty($warehouseCustomers)) {
                return $warehouseCustomers;
            }

            return $this->isSuperAdmin() ? [$this->resolveDefaultWarehouseCustomer()] : $allowedWarehouseCustomers;
        }

        if ($this->isSuperAdmin()) {
            return [$this->resolveDefaultWarehouseCustomer()];
        }

        return $allowedWarehouseCustomers;
    }

    private function isSuperAdmin(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }

    private function resolveDefaultWarehouseCustomer(): string
    {
        $warehouseCustomer = Customer::query()
            ->where('is_warehouse_client', true)
            ->orderBy('name')
            ->value('name');

        if (!empty($warehouseCustomer)) {
            return (string) $warehouseCustomer;
        }

        $guideCustomer = WarehouseGuide::query()
            ->select('customer', DB::raw('COUNT(*) as total'))
            ->groupBy('customer')
            ->get()
            ->sortByDesc('total')
            ->first()?->customer;

        if (!empty($guideCustomer)) {
            return (string) $guideCustomer;
        }

        $locationCustomer = WarehouseLocation::query()
            ->select('customer', DB::raw('COUNT(*) as total'))
            ->groupBy('customer')
            ->get()
            ->sortByDesc('total')
            ->first()?->customer;

        if (!empty($locationCustomer)) {
            return (string) $locationCustomer;
        }

        return Customer::query()->orderBy('name')->value('name') ?? 'SKYONE';
    }

    private function getCustomerOptions(): array
    {
        if (!$this->isSuperAdmin()) {
            return $this->allowedWarehouseCustomers();
        }

        return Customer::query()
            ->where('is_warehouse_client', true)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function filterWarehouseCustomers(array $customers, array $allowedWarehouseCustomers = []): array
    {
        $customers = array_values(array_filter($customers));

        if (empty($customers)) {
            return [];
        }

        $warehouseCustomers = Customer::query()
            ->whereIn('name', $customers)
            ->where('is_warehouse_client', true)
            ->pluck('name')
            ->all();

        $warehouseCustomers = array_values(array_intersect($customers, $warehouseCustomers));

        if ($this->isSuperAdmin()) {
            return $warehouseCustomers;
        }

        return array_values(array_intersect($warehouseCustomers, $allowedWarehouseCustomers));
    }

    private function allowedWarehouseCustomers(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return app(CustomerAccessService::class)
            ->availableWarehouseCustomers($user)
            ->pluck('name')
            ->all();
    }

    private function resolveWarehouseCustomerFromRequest(Request $request): string
    {
        $requestedCustomer = trim((string) $request->input('customer'));
        $customers = $this->getCustomers();

        if ($requestedCustomer !== '' && in_array($requestedCustomer, $customers, true)) {
            return $requestedCustomer;
        }

        return $this->getCustomer();
    }

    private function normalizeWarehouse(?string $warehouse): ?string
    {
        $normalized = trim((string) $warehouse);

        return $normalized === '' ? null : $normalized;
    }

    private function resolveImportContext(Request $request): array
    {
        $requestedCustomer = trim((string) $request->input('customer'));
        $customer = $requestedCustomer !== '' ? $requestedCustomer : $this->getCustomer();
        $allowedCustomers = $this->getCustomers();

        if (!in_array($customer, $allowedCustomers, true) && !$this->isSuperAdmin()) {
            abort(403, 'No tienes permisos para importar datos de este cliente.');
        }

        return [
            'customer' => $customer,
            'warehouse' => $customer,
        ];
    }

    private function ensureStorageLocationExists(string $customer, ?string $warehouse = null): WarehouseLocation
    {
        $warehouse = $this->normalizeWarehouse($warehouse) ?? 'ALMACENAMIENTO';

        return WarehouseLocation::updateOrCreate(
            [
                'code' => 'ALMACENAMIENTO',
                'customer' => $customer,
                'warehouse' => $warehouse,
            ],
            [
                'name' => 'Almacenamiento',
                'description' => 'Ubicacion temporal para recepcion y transito de guias.',
                'is_active' => true,
                'is_storage' => true,
            ]
        );
    }

    private function getLocationsForCustomers(array $customers, ?string $warehouse = null)
    {
        return WarehouseLocation::whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->withCount([
                'warehouseGuides as active_guides_count' => function ($query) {
                    $query->whereNull('exit_at');
                },
            ])
            ->orderByRaw("CASE WHEN code = 'ALMACENAMIENTO' THEN 0 ELSE 1 END")
            ->orderBy('warehouse')
            ->orderBy('code')
            ->get();
    }

    private function getLocationsForCustomer(string $customer, ?string $warehouse = null)
    {
        return $this->getLocationsForCustomers([$customer], $warehouse);
    }

    private function getWarehouseOptions(string $primaryCustomer, array $customers = [])
    {
        $customers = !empty($customers) ? $customers : [$primaryCustomer];

        return Customer::query()
            ->whereIn('name', $customers)
            ->where('is_warehouse_client', true)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all() ?: $customers;
    }

    private function buildLocationOptions($locations): array
    {
        return $locations->map(function ($location) {
            return [
                'location_id' => $location->location_id,
                'code' => $location->code,
                'name' => $location->name,
                'warehouse' => $location->warehouse,
                'is_active' => (bool) $location->is_active,
                'is_storage' => (bool) $location->is_storage,
                'label' => trim(($location->code ?? 'N/A') . ' - ' . ($location->name ?? 'Sin nombre')),
            ];
        })->values()->all();
    }

    private function buildGuideQuery(array $customers, array $filters)
    {
        $query = WarehouseGuide::query()->whereIn('customer', $customers);

        $warehouse = $this->normalizeWarehouse($filters['warehouse'] ?? null);
        if ($warehouse !== null) {
            $query->where('warehouse', $warehouse);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($nestedQuery) use ($search) {
                $nestedQuery->where('guide', 'like', '%' . $search . '%')
                    ->orWhere('national_guide', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function extractFilters(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'report_type' => $request->input('report_type'),
            'national_guide' => $request->input('national_guide'),
            'warehouse' => $request->input('warehouse'),
        ];
    }

    private function buildStats(array $customers, ?string $warehouse = null): array
    {
        $today = now()->toDateString();

        $guideScope = fn ($query) => $query
            ->whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($nestedQuery) => $nestedQuery->where('warehouse', $warehouse));

        $totalGuides = WarehouseGuide::where($guideScope)->count();
        $activeGuides = WarehouseGuide::where($guideScope)->whereNull('exit_at')->count();
        $storageGuides = WarehouseGuide::whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->whereNull('exit_at')
            ->where('current_location_code', 'ALMACENAMIENTO')
            ->count();
        $todayEntries = WarehouseGuide::where($guideScope)->whereDate('entry_at', $today)->count();
        $todayExits = WarehouseGuide::where($guideScope)->whereDate('exit_at', $today)->count();
        $activeLocations = WarehouseLocation::whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->where('is_active', true)
            ->count();

        $averageMinutes = (float) (DB::table('warehouse_guides')
            ->whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->whereNotNull('exit_at')
            ->selectRaw('COALESCE(AVG(TIMESTAMPDIFF(MINUTE, entry_at, exit_at)), 0) as average_minutes')
            ->value('average_minutes') ?? 0);

        $durationSeries = collect(range(6, 0))
            ->map(function (int $daysAgo) {
                return now()->subDays($daysAgo)->toDateString();
            })
            ->mapWithKeys(fn (string $date) => [$date => [
                'date' => $date,
                'label' => \Carbon\Carbon::parse($date)->format('d/m'),
                'average_minutes' => 0,
                'average_label' => '00m',
                'guides' => 0,
            ]]);

        $durationRows = DB::table('warehouse_guides')
            ->whereIn('customer', $customers)
            ->when($warehouse !== null, fn ($query) => $query->where('warehouse', $warehouse))
            ->whereNotNull('exit_at')
            ->whereDate('exit_at', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(exit_at) as exit_date')
            ->selectRaw('COALESCE(AVG(TIMESTAMPDIFF(MINUTE, entry_at, exit_at)), 0) as average_minutes')
            ->selectRaw('COUNT(*) as guides')
            ->groupBy('exit_date')
            ->orderBy('exit_date')
            ->get();

        foreach ($durationRows as $row) {
            $date = (string) $row->exit_date;

            if ($durationSeries->has($date)) {
                $minutes = (int) round((float) $row->average_minutes);
                $durationSeries[$date] = [
                    'date' => $date,
                    'label' => \Carbon\Carbon::parse($date)->format('d/m'),
                    'average_minutes' => $minutes,
                    'average_label' => $this->formatMinutes($minutes),
                    'guides' => (int) $row->guides,
                ];
            }
        }

        $maxDurationMinutes = max(1, (int) $durationSeries->max('average_minutes'));
        $exitedGuides = WarehouseGuide::where($guideScope)->whereNotNull('exit_at')->count();

        return [
            'total_guides' => $totalGuides,
            'active_guides' => $activeGuides,
            'storage_guides' => $storageGuides,
            'today_entries' => $todayEntries,
            'today_exits' => $todayExits,
            'active_locations' => $activeLocations,
            'exited_guides' => $exitedGuides,
            'average_minutes' => (int) round($averageMinutes),
            'average_label' => $this->formatMinutes((int) round($averageMinutes)),
            'duration_series' => $durationSeries->values()->all(),
            'duration_series_max' => $maxDurationMinutes,
        ];
    }

    private function formatGuidePayload(WarehouseGuide $guide): array
    {
        return [
            'guide' => [
                'guide' => $guide->guide,
                'national_guide' => $guide->national_guide,
                'customer' => $guide->customer,
                'warehouse' => $guide->warehouse,
                'status' => $guide->status,
                'status_label' => $guide->status_label,
                'status_badge_class' => $guide->status_badge_class,
                'current_location_id' => $guide->current_location_id,
                'current_location_code' => $guide->current_location_code,
                'current_location_name' => $guide->current_location_name,
                'current_location_label' => $guide->current_location_label,
                'entry_at' => optional($guide->entry_at)->format('d/m/Y H:i'),
                'exit_at' => optional($guide->exit_at)->format('d/m/Y H:i'),
                'duration_label' => $guide->duration_label,
                'entry_source' => $guide->entry_source,
                'notes' => $guide->notes,
                'entry_user' => optional($guide->entryUser)->name,
                'exit_user' => optional($guide->exitUser)->name,
                'movements_count' => $guide->movements->count(),
            ],
            'movements' => $guide->movements->map(function ($movement) {
                return [
                    'action' => $movement->action,
                    'national_guide' => $movement->national_guide,
                    'action_label' => $movement->action_label,
                    'action_badge_class' => $movement->action_badge_class,
                    'from_location_label' => $movement->from_location_label,
                    'to_location_label' => $movement->to_location_label,
                    'performed_at' => optional($movement->performed_at)->format('d/m/Y H:i'),
                    'notes' => $movement->notes,
                    'user' => optional($movement->user)->name,
                ];
            })->values(),
        ];
    }

    private function formatLocation(WarehouseLocation $location): array
    {
        return [
            'location_id' => $location->location_id,
            'code' => $location->code,
            'name' => $location->name,
            'warehouse' => $location->warehouse,
            'description' => $location->description,
            'is_active' => (bool) $location->is_active,
            'is_storage' => (bool) $location->is_storage,
        ];
    }

    private function formatLocationGuide(WarehouseGuide $guide): array
    {
        return [
            'guide' => $guide->guide,
            'national_guide' => $guide->national_guide,
            'status_label' => $guide->status_label,
            'status_badge_class' => $guide->status_badge_class,
            'duration_label' => $guide->duration_label,
            'entry_at' => optional($guide->entry_at)->format('d/m/Y H:i'),
            'entry_source' => $guide->entry_source,
            'movements_count' => (int) ($guide->movements_count ?? 0),
        ];
    }

    private function createGuideEntry(
        string $customer,
        string $guideCode,
        WarehouseLocation $location,
        ?string $notes = null,
        string $entrySource = 'manual'
    ): WarehouseGuide {
        if (WarehouseGuide::where('customer', $customer)->where('guide', $guideCode)->exists()) {
            throw new \RuntimeException('La guia ya fue registrada para este cliente.');
        }

        $entrySource = in_array($entrySource, ['manual', 'barcode'], true) ? $entrySource : 'manual';
        $notes = $this->normalizeOptionalText($notes);

        return DB::transaction(function () use ($customer, $guideCode, $location, $notes, $entrySource) {
            $now = now();

            $warehouseGuide = WarehouseGuide::create([
                'guide' => $guideCode,
                'customer' => $customer,
                'warehouse' => $location->warehouse,
                'status' => WarehouseGuide::STATUS_ACTIVE,
                'entry_at' => $now,
                'entry_source' => $entrySource,
                'entry_user_id' => Auth::id(),
                'current_location_id' => $location->location_id,
                'current_location_code' => $location->code,
                'current_location_name' => $location->name,
                'notes' => $notes,
            ]);

            WarehouseGuideMovement::create([
                'warehouse_guide_id' => $warehouseGuide->id,
                'action' => 'ENTRY',
                'to_location_id' => $location->location_id,
                'to_location_code' => $location->code,
                'to_location_name' => $location->name,
                'performed_by' => Auth::id(),
                'performed_at' => $now,
                'notes' => $notes,
            ]);

            return $warehouseGuide->load(['currentLocation', 'entryUser', 'movements.user', 'movements.toLocation']);
        });
    }

    private function registerGuideExit(WarehouseGuide $guide, string $nationalGuide, ?string $notes = null): WarehouseGuide
    {
        if ($guide->exit_at) {
            throw new \RuntimeException('La guia ya tiene salida registrada.');
        }

        $nationalGuide = strtoupper(trim($nationalGuide));
        $notes = $this->normalizeOptionalText($notes);

        return DB::transaction(function () use ($guide, $nationalGuide, $notes) {
            $fromLocation = $guide->currentLocation;
            $entryAt = $guide->entry_at;
            $now = now();

            WarehouseGuideMovement::create([
                'warehouse_guide_id' => $guide->id,
                'action' => 'EXIT',
                'national_guide' => $nationalGuide,
                'from_location_id' => $fromLocation?->location_id,
                'from_location_code' => $fromLocation?->code ?? $guide->current_location_code,
                'from_location_name' => $fromLocation?->name ?? $guide->current_location_name,
                'performed_by' => Auth::id(),
                'performed_at' => $now,
                'notes' => $notes,
            ]);

            $guide->update([
                'status' => WarehouseGuide::STATUS_EXITED,
                'national_guide' => $nationalGuide,
                'entry_at' => $entryAt,
                'exit_at' => $now,
                'exit_user_id' => Auth::id(),
            ]);

            return $guide->fresh(['currentLocation', 'entryUser', 'exitUser', 'movements.user', 'movements.fromLocation', 'movements.toLocation']);
        });
    }

    private function readImportRows(UploadedFile $file): array
    {
        $sheets = Excel::toArray([], $file);
        $sheet = $sheets[0] ?? [];

        if (count($sheet) < 2) {
            return [];
        }

        $headerRow = array_shift($sheet);
        $headers = collect($headerRow)
            ->map(fn ($header) => $this->normalizeImportHeading($header))
            ->values()
            ->all();

        $rows = [];

        foreach ($sheet as $index => $values) {
            $row = [];

            foreach ($headers as $columnIndex => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = trim((string) ($values[$columnIndex] ?? ''));
            }

            $rows[$index + 2] = $row;
        }

        return $rows;
    }

    private function normalizeImportHeading($value): string
    {
        $normalized = Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return match ($normalized) {
            'guia', 'guide' => 'guia',
            'guia_nacional', 'guia_nal', 'national_guide', 'nationalguide' => 'guia_nacional',
            'localizacion', 'ubicacion', 'location' => 'localizacion',
            'salida', 'exit' => 'salida',
            'notas', 'nota', 'observaciones', 'observacion' => 'notas',
            default => $normalized,
        };
    }

    private function extractImportValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function buildImportLocationLookup($locations): array
    {
        $lookup = [];

        foreach ($locations as $location) {
            $keys = array_filter([
                $this->normalizeImportLookupValue($location->code),
                $this->normalizeImportLookupValue($location->name),
                $this->normalizeImportLookupValue(trim(($location->code ?? '') . ' - ' . ($location->name ?? ''))),
            ]);

            if ($location->is_storage || Str::upper((string) $location->code) === 'ALMACENAMIENTO') {
                $keys[] = 'almacenamiento';
                $keys[] = 'storage';
            }

            foreach (array_unique($keys) as $key) {
                $lookup[$key] = $location;
            }
        }

        return $lookup;
    }

    private function resolveImportLocation(string $value, array $lookup): ?WarehouseLocation
    {
        $normalized = $this->normalizeImportLookupValue($value);

        if ($normalized === '') {
            return null;
        }

        return $lookup[$normalized] ?? null;
    }

    private function normalizeImportLookupValue(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->trim()
            ->toString();
    }

    private function sameImportValue(?string $left, ?string $right): bool
    {
        return $this->normalizeImportLookupValue($left) === $this->normalizeImportLookupValue($right);
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function buildImportFlashMessage(string $type, int $processed, array $errors): string
    {
        $base = "Importacion de {$type}: {$processed} fila(s) procesadas.";

        if (empty($errors)) {
            return $base;
        }

        return $base . ' ' . count($errors) . ' fila(s) con error.';
    }

    private function resolveLocation(int $locationId, string $customer, bool $mustBeActive = true): WarehouseLocation
    {
        $query = WarehouseLocation::where('location_id', $locationId)
            ->where('customer', $customer);

        if ($mustBeActive) {
            $query->where('is_active', true);
        }

        return $query->firstOrFail();
    }

    private function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }

        if ($hours > 0 || $days > 0) {
            $parts[] = str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . 'h';
        }

        $parts[] = str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'm';

        return implode(' ', $parts);
    }
}
