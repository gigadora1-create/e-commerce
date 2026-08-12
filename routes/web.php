<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InfobipController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SendController;
use App\Http\Controllers\InventoryOutputController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\InventoryDetailController;
use App\Http\Controllers\BarcodeInventoryController;
use App\Http\Controllers\PickingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\CustomerContextController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SupplyIssueController;
use App\Http\Controllers\SupplyClientController;
use App\Http\Controllers\SupplyPurchaseRecipientController;







Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'loginAction')->name('login.action');
    Route::get('logout', 'logout')->middleware('auth')->name('logout');
});
Route::middleware(['auth'])->group(function () {
Route::match(['get', 'post'], '/two-factor/send', [TwoFactorAuthController::class, 'sendCode'])->name('two-factor.send-code');    Route::get('/two-factor/verify', [TwoFactorAuthController::class, 'showCodeForm'])->name('two-factor.show-code-form');
    Route::post('/two-factor/verify', [TwoFactorAuthController::class, 'verifyCode'])->name('two-factor.verify-code');
});


Route::middleware('auth')->group(function () {
    Route::get('/customer-context', [CustomerContextController::class, 'index'])->name('customer.context.index');
    Route::post('/customer-context', [CustomerContextController::class, 'store'])->name('customer.context.store');
    Route::post('/customer-context/clear', [CustomerContextController::class, 'clear'])->name('customer.context.clear');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/entries-data', [DashboardController::class, 'getEntriesData'])->name('dashboard.entries.data');
    Route::get('/dashboard/outputs-data', [DashboardController::class, 'getOutputsData'])->name('dashboard.outputs.data');

    Route::prefix('supplies')->name('supplies.')->group(function () {
        Route::get('/', [SupplyController::class, 'index'])
            ->middleware('can:supplies.admin')
            ->name('index');
        Route::post('/products', [SupplyController::class, 'storeProduct'])
            ->middleware('can:supplies.admin')
            ->name('products.store');
        Route::put('/products/{product}', [SupplyController::class, 'updateProduct'])
            ->middleware('can:supplies.admin')
            ->name('products.update');
        Route::delete('/products/{product}', [SupplyController::class, 'destroyProduct'])
            ->middleware('can:supplies.admin')
            ->name('products.destroy');
        Route::post('/clients', [SupplyClientController::class, 'store'])
            ->middleware('can:supplies.admin')
            ->name('clients.store');
        Route::post('/clients/import', [SupplyClientController::class, 'import'])
            ->middleware('can:supplies.admin')
            ->name('clients.import');
        Route::get('/clients/template', [SupplyClientController::class, 'downloadTemplate'])
            ->middleware('can:supplies.admin')
            ->name('clients.template');
        Route::put('/clients/{client}', [SupplyClientController::class, 'update'])
            ->middleware('can:supplies.admin')
            ->name('clients.update');
        Route::delete('/clients/{client}', [SupplyClientController::class, 'destroy'])
            ->middleware('can:supplies.admin')
            ->name('clients.destroy');
        Route::post('/purchase-recipients', [SupplyPurchaseRecipientController::class, 'store'])
            ->middleware('can:supplies.admin')
            ->name('purchase-recipients.store');
        Route::put('/purchase-recipients/{recipient}', [SupplyPurchaseRecipientController::class, 'update'])
            ->middleware('can:supplies.admin')
            ->name('purchase-recipients.update');
        Route::delete('/purchase-recipients/{recipient}', [SupplyPurchaseRecipientController::class, 'destroy'])
            ->middleware('can:supplies.admin')
            ->name('purchase-recipients.destroy');
        Route::post('/requests', [SupplyController::class, 'storeRequest'])
            ->middleware('can:supplies.admin')
            ->name('requests.store');
        Route::get('/requests/{supplyRequest}', [SupplyController::class, 'show'])
            ->middleware('can:supplies.admin')
            ->name('show');
        Route::put('/requests/{supplyRequest}/audit', [SupplyController::class, 'auditRequest'])
            ->middleware('can:supplies.admin')
            ->name('requests.audit');
        Route::get('/requests/{supplyRequest}/pdf', [SupplyController::class, 'pdf'])
            ->middleware('can:supplies.admin')
            ->name('requests.pdf');

        Route::prefix('issues')->name('issues.')->group(function () {
            Route::get('/', [SupplyIssueController::class, 'index'])
                ->middleware('can:supplies.request')
                ->name('index');
            Route::post('/', [SupplyIssueController::class, 'store'])
                ->middleware('can:supplies.request')
                ->name('store');
            Route::get('/{issueRequest}', [SupplyIssueController::class, 'show'])
                ->middleware('can:supplies.request')
                ->name('show');
            Route::get('/{issueRequest}/pdf', [SupplyIssueController::class, 'pdf'])
                ->middleware('can:supplies.request')
                ->name('pdf');
            Route::put('/{issueRequest}/ready', [SupplyIssueController::class, 'markReady'])
                ->middleware('can:supplies.admin')
                ->name('ready');
            Route::put('/{issueRequest}/close', [SupplyIssueController::class, 'close'])
                ->middleware('can:supplies.admin')
                ->name('close');
            Route::put('/{issueRequest}/reject', [SupplyIssueController::class, 'reject'])
                ->middleware('can:supplies.admin')
                ->name('reject');
        });
    });
});



Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/data', [ItemController::class, 'getData'])->name('items.data');
Route::post('/items', [ItemController::class, 'store'])->name('items.store');
Route::post('/items/{id}', [ItemController::class, 'update'])->name('items.update');
Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
Route::get('/cities', [CityController::class, 'index'])->name('cities.index');

Route::put('/cities/{city_id}', [CityController::class, 'update'])->name('cities.update');
Route::delete('/cities/{city_id}', [CityController::class, 'destroy'])->name('cities.destroy');

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
Route::put('/customers/{customer_id}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('/customers/{customer_id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

// Agregar en routes/web.php
Route::get('/search-products', [InventoryController::class, 'searchProducts'])->name('search.products');

Route::get('/get-product-image/{productName}', [InventoryController::class, 'getProductImage'])->name('get.product.image');




Route::get('/send-sms', function () {
    return view('send-sms');
})->name('send-sms.form');


Route::post('/send-sms', [InfobipController::class, 'sendSMS'])->name('send-sms');
Route::post('/send-bulk-sms', [InfobipController::class, 'sendBulkSMS'])->name('send-bulk-sms');


Route::resource('send', SendController::class);


Route::post('/inventories/store', [InventoryController::class, 'store']);
Route::post('/cities', [InventoryController::class, 'storeCity'])->name('cities.store');
Route::get('/inventories', [InventoryController::class, 'index'])->name('inventories.index');
Route::post('/inventories/reconcile', [InventoryController::class, 'reconcile'])->name('inventories.reconcile');
Route::put('/inventories/{inventory}', [InventoryController::class, 'update'])->name('inventories.update');
Route::delete('/inventories/{inventory}', [InventoryController::class, 'destroy'])->name('inventories.destroy');
Route::post('/customers', [InventoryController::class, 'storeCustomer'])->name('customers.store');
Route::get('/inventories/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventories.edit');
Route::get('/inventory-outputs', [InventoryOutputController::class, 'index'])->name('inventory-outputs.index');
Route::get('/inventory-outputs/create', [InventoryOutputController::class, 'create'])->name('inventory-outputs.create');
Route::post('/inventory-outputs', [InventoryOutputController::class, 'store'])->name('inventory-outputs.store');
Route::post('/inventory-outputs/generate-report', [InventoryOutputController::class, 'generateReport'])->name('inventory-outputs.generate-report');
Route::get('/inventory-outputs/export-excel', [InventoryOutputController::class, 'exportExcel'])->name('inventory-outputs.export-excel');
Route::put('inventory-outputs/{inventory_output}', [InventoryOutputController::class, 'update'])->name('inventory-outputs.update');
Route::get('/get-item-image/{itemDescription}', [InventoryOutputController::class, 'getItemImage']);
Route::post('/inventory-outputs/import-massive', [InventoryOutputController::class, 'importMassive'])->name('inventory-outputs.import-massive');

Route::group(['prefix' => 'inventory-outputs', 'as' => 'inventory-outputs.'], function() {
    Route::get('/show-import-form', [InventoryOutputController::class, 'showImportForm'])->name('show-import-form');
    Route::post('/import-massive', [InventoryOutputController::class, 'importMassive'])->name('import-massive');
    Route::get('/download-template', [InventoryOutputController::class, 'downloadTemplate'])->name('download-template');
});
Route::get('/get-item-image/{itemDescription}', [InventoryOutputController::class, 'getItemImage'])->name('get.item.image');
Route::get('/check-availability', [InventoryOutputController::class, 'checkAvailability'])->name('inventory-outputs.check-availability');

Route::get('/inventory-details', [InventoryDetailController::class, 'index'])->name('inventory-details.index');
Route::post('/inventory-details', [InventoryDetailController::class, 'store'])->name('inventory-details.store');
Route::put('/inventory-details/{id}', [InventoryDetailController::class, 'update'])->name('inventory-details.update');
Route::delete('/inventory-details/{id}', [InventoryDetailController::class, 'destroy'])->name('inventory-details.destroy');
Route::post('/inventory-details/export', [InventoryDetailController::class, 'export'])->name('inventory-details.export');
Route::post('/inventory-details/assign-documento', [InventoryDetailController::class, 'assignDocumento'])->name('inventory-details.assign-documento');
Route::get('inventory-details/get-sku/{description}', [InventoryDetailController::class, 'getSkuByDescription'])->name('inventory-details.get-sku');
Route::post('/inventories/export', [InventoryController::class, 'export'])->name('inventories.export');
Route::post('/inventories/import', [InventoryController::class, 'import'])->name('inventories.import');
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
Route::post('/inventories/select-customer', [CustomerContextController::class, 'store'])->name('inventories.select-customer');
Route::post('/inventories/exit-customer', [CustomerContextController::class, 'clear'])->name('inventories.exit-customer');
Route::post('inventory-details/group', [InventoryDetailController::class, 'group'])->name('inventory-details.group');
Route::get('/inventories/create', [InventoryController::class, 'create'])->name('inventories.create');
Route::post('/inventories', [InventoryController::class, 'store'])->name('inventories.store');
Route::post('/inventories/release-retention', [InventoryController::class, 'releaseFromRetention'])->name('inventories.release-retention');
Route::middleware(['auth'])->group(function () {
    Route::middleware(['super.admin'])->group(function () {
        Route::resource('permissions', PermissionController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('profiles', ProfileController::class);
        Route::resource('admin', AdminController::class);
        Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role_permissions.index');
        Route::post('/role-permissions/{roleId}/assign', [RolePermissionController::class, 'assignPermissions'])->name('role_permissions.assign');
    });

    // Listado y datos de ubicaciones
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/data', [LocationController::class, 'getData'])->name('locations.data');

    // Crear, actualizar y eliminar ubicaciones
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::put('/locations/{id}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{id}', [LocationController::class, 'destroy'])->name('locations.destroy');

    // Asignar, mover y eliminar productos
    Route::post('/locations/assign-item', [LocationController::class, 'assignItem'])->name('locations.assignItem');
    Route::post('/locations/move-item', [LocationController::class, 'moveItem'])->name('locations.moveItem');
    Route::post('/locations/move-to-storage', [LocationController::class, 'moveToStorage'])->name('locations.moveToStorage');
    Route::post('/locations/move-to-pending', [LocationController::class, 'moveToPending'])->name('locations.moveToPending');
    Route::post('/locations/remove-item', [LocationController::class, 'removeItem'])->name('locations.removeItem');

    // Editar capacidades
    Route::put('/locations/{locationId}/update-location-capacity', [LocationController::class, 'updateLocationCapacity'])
        ->name('locations.updateLocationCapacity');

    Route::put('/locations/{locationId}/update-item-capacity/{itemId}', [LocationController::class, 'updateItemCapacity'])
        ->name('locations.updateItemCapacity');
    Route::get('/locations/debug-storage', [LocationController::class, 'debugStorage']);
    Route::get('/locations/validate-data', [LocationController::class, 'validateLocationData']);
    Route::post('/locations/sync-corrected', [LocationController::class, 'syncItemLocations']);
    Route::post('/locations/move-from-storage', [LocationController::class, 'moveFromStorage'])
    ->name('locations.moveFromStorage');

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])
            ->middleware('can:warehouse.view')
            ->name('index');

        Route::post('/customer/select', [WarehouseController::class, 'selectCustomer'])
            ->middleware('can:warehouse.view')
            ->name('customer.select');

        Route::post('/customer/clear', [WarehouseController::class, 'exitCustomer'])
            ->middleware('can:warehouse.view')
            ->name('customer.clear');

        Route::get('/guides/{guide}', [WarehouseController::class, 'showGuide'])
            ->middleware('can:warehouse.view')
            ->name('guides.show');

        Route::get('/export', [WarehouseController::class, 'export'])
            ->middleware('can:warehouse.export')
            ->name('export');

        Route::get('/templates/entries', [WarehouseController::class, 'downloadEntryTemplate'])
            ->middleware('can:warehouse.manage')
            ->name('templates.entries');

        Route::get('/templates/exits', [WarehouseController::class, 'downloadExitTemplate'])
            ->middleware('can:warehouse.manage')
            ->name('templates.exits');

        Route::post('/import/entries', [WarehouseController::class, 'importEntries'])
            ->middleware('can:warehouse.manage')
            ->name('import.entries');

        Route::post('/import/exits', [WarehouseController::class, 'importExits'])
            ->middleware('can:warehouse.manage')
            ->name('import.exits');

        Route::post('/guides', [WarehouseController::class, 'storeGuide'])
            ->middleware('can:warehouse.manage')
            ->name('guides.store');

        Route::post('/guides/move', [WarehouseController::class, 'moveGuide'])
            ->middleware('can:warehouse.manage')
            ->name('guides.move');

        Route::post('/guides/exit', [WarehouseController::class, 'exitGuide'])
            ->middleware('can:warehouse.manage')
            ->name('guides.exit');

        Route::post('/guides/exit-grouped', [WarehouseController::class, 'exitGuidesGrouped'])
            ->middleware('can:warehouse.manage')
            ->name('guides.exit-grouped');

        Route::put('/guides/{guide}', [WarehouseController::class, 'updateGuide'])
            ->name('guides.update');

        Route::delete('/guides/{guide}', [WarehouseController::class, 'destroyGuide'])
            ->name('guides.destroy');

        Route::post('/locations', [WarehouseController::class, 'storeLocation'])
            ->middleware('can:warehouse.manage')
            ->name('locations.store');

        Route::put('/locations/{locationId}', [WarehouseController::class, 'updateLocation'])
            ->middleware('can:warehouse.manage')
            ->name('locations.update');

        Route::delete('/locations/{locationId}', [WarehouseController::class, 'destroyLocation'])
            ->middleware('can:warehouse.manage')
            ->name('locations.destroy');
    });

});
    Route::get('/inventories/manual-entry/data', [InventoryController::class, 'createManualEntry'])->name('inventories.manual-entry.data');
    Route::post('/inventories/manual-entry', [InventoryController::class, 'storeManualEntry'])->name('inventories.manual-entry.store');
    Route::post('/inventories/{inventory}/upload-document', [InventoryController::class, 'uploadDocument'])->name('inventories.upload-document');
    Route::get('/inventories/{inventory}/download-document', [InventoryController::class, 'downloadDocument'])->name('inventories.download-document');
    Route::delete('/inventories/{inventory}/delete-document', [InventoryController::class, 'deleteDocument'])->name('inventories.delete-document');
    Route::post('inventories/{inventory}/upload-document', [InventoryController::class, 'uploadDocument'])->name('inventories.upload-document');




Route::get('/search-products', [InventoryController::class, 'searchProducts']);

Route::get('/get-product-image/{productName}', [InventoryController::class, 'getProductImage']);
Route::get('/inventories/retention-report', [InventoryController::class, 'retentionReport'])
    ->name('inventories.retention_report');
    
    Route::get('/inventories/send-to-retention/{id}', [InventoryController::class, 'sendToRetention'])->name('inventories.send_to_retention');
Route::post('/inventory-outputs/release-retention', [InventoryOutputController::class, 'releaseFromRetention'])
    ->name('inventory-outputs.release-retention');
Route::post('/barcode/get-locations-by-item-and-warehouse', [BarcodeInventoryController::class, 'getLocationsByItemAndWarehouse'])->name('barcode.getLocationsByItemAndWarehouse');
    Route::get('/barcode', [BarcodeInventoryController::class, 'index'])->name('barcode.index');
    Route::post('/barcode/search-by-barcode', [BarcodeInventoryController::class, 'searchByBarcode'])->name('barcode.searchByBarcode');
    Route::post('/barcode/search-by-sku-or-barcode', [BarcodeInventoryController::class, 'searchBySkuOrBarcode'])->name('barcode.searchBySkuOrBarcode');
    Route::post('/barcode/get-locations-by-item-and-warehouse', [BarcodeInventoryController::class, 'getLocationsByItemAndWarehouse'])->name('barcode.getLocationsByItemAndWarehouse');
    Route::post('/barcode/get-locations-with-stock', [BarcodeInventoryController::class, 'getLocationsWithStock'])->name('barcode.getLocationsWithStock');
    Route::post('/barcode/store-entry', [BarcodeInventoryController::class, 'storeEntry'])->name('barcode.storeEntry');
    Route::post('/barcode/store-output', [BarcodeInventoryController::class, 'storeOutput'])->name('barcode.storeOutput');
    Route::get('/barcode/get-warehouses', [BarcodeInventoryController::class, 'getWarehouses'])->name('barcode.getWarehouses');
    Route::get('/barcode/get-item-conditions', [BarcodeInventoryController::class, 'getItemConditions'])->name('barcode.getItemConditions');
    Route::post('/barcode/search-products', [BarcodeInventoryController::class, 'searchProducts'])->name('barcode.searchProducts');
    Route::post('/barcode/update-barcode/{itemId}', [BarcodeInventoryController::class, 'updateBarcode'])->name('barcode.updateBarcode');

Route::prefix('inventories')->group(function () {
    Route::get('/search-products', [BarcodeInventoryController::class, 'searchProducts'])
        ->name('inventories.searchProducts');
});

Route::post('/items/{itemId}/barcode', [BarcodeInventoryController::class, 'updateBarcode'])
    ->name('items.updateBarcode');

Route::post('/inventories/release-from-retention', [InventoryController::class, 'releaseFromRetention'])->name('inventories.releaseFromRetention');
Route::get('/get-sku-from-inventory', [InventoryController::class, 'getSkuFromInventory'])->name('get.sku.from.inventory');
Route::get('/validate-sku', [InventoryController::class, 'validateSku'])->name('validate.sku');
Route::get('/search-items', [InventoryController::class, 'searchItems'])->name('search.items');
Route::get('/get-locations-by-item/{itemId}', [InventoryController::class, 'getLocationsByItem'])->name('get.locations.by.item');
Route::get('/search-warehouses', [InventoryController::class, 'searchWarehouses'])->name('search.warehouses');

Route::middleware(['auth'])->group(function () {
    

    Route::prefix('picking')->name('picking.')->group(function () {
        Route::get('/', [PickingController::class, 'index'])->name('index');
        Route::post('/import', [PickingController::class, 'import'])->name('import');
        Route::get('/{id}', [PickingController::class, 'show'])->name('show');
        Route::get('/{id}/export', [PickingController::class, 'exportReport'])->name('export');

    });
    
});     

Route::get('/get-inventory-by-location', [InventoryController::class, 'getInventoryByLocation'])->name('inventory.by-location');

Route::post('/picking/{id}/complete', [PickingController::class, 'complete'])->name('picking.complete');
Route::post('/picking/{id}/cancel', [PickingController::class, 'cancel'])->name('picking.cancel');
Route::get('/inventories/retention-report', [InventoryController::class, 'retentionReport'])
    ->name('inventories.retention_report');


Route::post('/get-expiry-dates-from-outputs', [InventoryController::class, 'getExpiryDatesFromOutputs'])
    ->name('inventories.getExpiryDatesFromOutputs');

Route::post('/get-expiry-dates-from-inventories', [InventoryController::class, 'getExpiryDatesFromInventories'])
    ->name('inventories.getExpiryDatesFromInventories');


Route::post('/get-output-record-data', [InventoryController::class, 'getOutputRecordData'])
    ->name('inventories.getOutputRecordData');

Route::post('/get-inventory-record-data', [InventoryController::class, 'getInventoryRecordData'])
    ->name('inventories.getInventoryRecordData');

Route::post('/inventories/adjust-storage', [InventoryController::class, 'adjustStorageStock'])
    ->name('inventories.adjust-storage');


Route::post('/inventories/process-devolution', [InventoryController::class, 'processDevolution'])
    ->name('inventories.processDevolution');

Route::post('/inventories/process-retention', [InventoryController::class, 'processRetention'])
    ->name('inventories.processRetention');


Route::post('/picking/import-progress', [App\Http\Controllers\PickingController::class, 'importProgress'])
    ->middleware('auth')
    ->name('picking.import.progress');


Route::get('/picking/{id}/pdf', [PickingController::class, 'pdf'])->name('picking.pdf');
Route::get('/picking/keep-alive', function (Request $request) {return response()->json(['status' => 'alive']);})->name('picking.keep_alive');
Route::get('/session-expired-demo', function () {
    return response()->view('errors.session-expired', [], 401);
});
