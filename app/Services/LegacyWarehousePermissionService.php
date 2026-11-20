<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class LegacyWarehousePermissionService
{
    public function forUser(?User $user): array
    {
        if (!$user || $user->isSuperAdmin()) {
            return [];
        }

        $operationalPermissions = array_keys(config('role_permissions.permissions', []));
        $customerPermissions = Customer::query()->pluck('name')
            ->filter()
            ->map(fn (string $name) => mb_strtoupper($name))
            ->all();
        $nonWarehousePermissions = array_merge($operationalPermissions, $customerPermissions, [
            'biometrico',
            'passwords.index',
            'password.create',
            'user.management',
            'SUPER_ADMIN',
            'USUARIO_CLIENTE',
        ]);

        return $user->getAllPermissions()
            ->pluck('name')
            ->filter(fn (string $permission) => !in_array($permission, $nonWarehousePermissions, true))
            ->values()
            ->all();
    }
}
