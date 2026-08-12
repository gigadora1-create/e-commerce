<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->unsignedInteger('stock_on_hand')->default(0)->after('description');
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock_on_hand');
        });

        Schema::create('supply_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->constrained('supply_products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 40)->index();
            $table->integer('quantity');
            $table->unsignedInteger('stock_on_hand_after')->default(0);
            $table->unsignedInteger('reserved_stock_after')->default(0);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'supply_stock_reference_index');
        });

        Schema::create('supply_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prepared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('preparing')->index();
            $table->text('request_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('ready_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('supply_issue_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_issue_request_id')->constrained('supply_issue_requests')->cascadeOnDelete();
            $table->foreignId('supply_product_id')->constrained('supply_products')->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('reserved_quantity');
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->unsignedInteger('available_quantity_at_request')->default(0);
            $table->timestamps();

            $table->unique(['supply_issue_request_id', 'supply_product_id'], 'supply_issue_request_product_unique');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminPermission = Permission::findOrCreate('supplies.admin', 'web');
        $requestPermission = Permission::findOrCreate('supplies.request', 'web');

        $supplyAdminRole = Role::findOrCreate('PROVEEDURIA_ADMIN', 'web');
        $supplyUserRole = Role::findOrCreate('PROVEEDURIA_USUARIO', 'web');

        $supplyAdminRole->givePermissionTo([$adminPermission, $requestPermission]);
        $supplyUserRole->givePermissionTo([$requestPermission]);

        foreach (['SUPERADMIN', 'ADMINISTRADOR'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo([$adminPermission, $requestPermission]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_issue_request_items');
        Schema::dropIfExists('supply_issue_requests');
        Schema::dropIfExists('supply_stock_movements');

        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn(['stock_on_hand', 'reserved_stock']);
        });
    }
};
