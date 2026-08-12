<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerAccessService
{
    public function availableCustomers($user, ?string $search = null): Collection
    {
        $customers = $this->allCustomers($search);

        if (!$user || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return $this->normalCustomers($customers);
        }

        if (method_exists($user, 'isWarehouseOnly') && $user->isWarehouseOnly()) {
            return $this->warehouseCustomers($this->authorizedCustomers($user, $customers));
        }

        $authorizedCustomers = $this->authorizedCustomers($user, $customers);

        if ($authorizedCustomers->isEmpty() && !$this->usesWarehouseContext($user)) {
            return $this->normalCustomers($customers);
        }

        return $this->normalCustomers($authorizedCustomers);
    }

    public function availableWarehouseCustomers($user): Collection
    {
        $customers = $this->allCustomers();

        if (!$user || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return $this->warehouseCustomers($customers);
        }

        return $this->warehouseCustomers($this->authorizedCustomers($user, $customers));
    }

    public function assignedCustomers($user, ?Collection $customers = null): Collection
    {
        if (!$user || !method_exists($user, 'customerAccesses')) {
            return collect();
        }

        $assignedIds = $user->customerAccesses()
            ->pluck('customers.customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($assignedIds)) {
            return collect();
        }

        $customers = $customers ?: Customer::query()->orderBy('name')->get();

        return $customers->filter(function ($customer) use ($assignedIds) {
            return in_array((int) $customer->customer_id, $assignedIds, true);
        })->values();
    }

    private function legacyPermissionCustomers($user, Collection $customers): Collection
    {
        $permissionNames = $user->getAllPermissions()->pluck('name')->all();
        $permissionMap = collect($permissionNames)
            ->mapWithKeys(fn ($permission) => [$this->normalize((string) $permission) => true]);

        return $customers->filter(function ($customer) use ($permissionMap) {
            return $permissionMap->has($this->normalize((string) $customer->name));
        })->values();
    }

    private function allCustomers(?string $search = null): Collection
    {
        $customers = Customer::query()->orderBy('name')->get();

        if ($search !== null && $search !== '') {
            $searchValue = $this->normalize($search);
            $customers = $customers->filter(function ($customer) use ($searchValue) {
                return str_contains($this->normalize((string) $customer->name), $searchValue);
            });
        }

        return $customers->values();
    }

    private function authorizedCustomers($user, Collection $customers): Collection
    {
        $assignedCustomers = $this->assignedCustomers($user, $customers);

        if ($assignedCustomers->isNotEmpty()) {
            return $assignedCustomers->values();
        }

        return collect();
    }

    private function normalCustomers(Collection $customers): Collection
    {
        return $customers
            ->filter(fn ($customer) => !(bool) $customer->is_warehouse_client)
            ->values();
    }

    private function warehouseCustomers(Collection $customers): Collection
    {
        return $customers
            ->filter(fn ($customer) => (bool) $customer->is_warehouse_client)
            ->values();
    }

    public function isCustomerAllowed($user, string $customerName): bool
    {
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $this->availableCustomers($user)->contains(function ($customer) use ($customerName) {
            return $this->normalize((string) $customer->name) === $this->normalize($customerName);
        });
    }

    public function usesWarehouseContext($user): bool
    {
        if (!$user) {
            return false;
        }

        return (method_exists($user, 'isWarehouseOnly') && $user->isWarehouseOnly())
            || $user->can('warehouse.view')
            || $user->can('warehouse.manage')
            || $user->can('warehouse.export');
    }

    public function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
