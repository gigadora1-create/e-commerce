<?php

namespace App\Services\Supply;

use App\Mail\SupplyPurchaseRequestCreated;
use App\Models\SupplyPurchaseRecipient;
use App\Models\SupplyRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SupplyPurchaseNotificationService
{
    public function sendRequestCreatedNotification(SupplyRequest $supplyRequest): int
    {
        if (!Schema::hasTable('supply_purchase_recipients')) {
            return 0;
        }

        $emails = SupplyPurchaseRecipient::query()
            ->active()
            ->orderBy('email')
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if ($emails === []) {
            return 0;
        }

        try {
            Mail::to($emails)->send(new SupplyPurchaseRequestCreated($supplyRequest));
        } catch (\Throwable $exception) {
            Log::error('Error enviando correo de solicitud de compra de proveeduria', [
                'supply_request_id' => $supplyRequest->id,
                'request_number' => $supplyRequest->request_number,
                'recipients' => $emails,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }

        return count($emails);
    }
}
