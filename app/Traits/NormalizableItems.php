<?php

namespace App\Traits;

trait NormalizableItems
{
    private function normalizeItems(array $productIds, array $quantities, bool $allowZero = false): array
    {
        $normalized = [];

        foreach ($productIds as $index => $productId) {
            $productId = (int) $productId;
            $quantity = (int) ($quantities[$index] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            if ($allowZero ? $quantity < 0 : $quantity <= 0) {
                continue;
            }

            if (!isset($normalized[$productId])) {
                $normalized[$productId] = 0;
            }

            $normalized[$productId] += $quantity;
        }

        return $allowZero ? $normalized : array_filter($normalized, fn ($q) => $q > 0);
    }
}
