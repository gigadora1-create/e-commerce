<?php

namespace App\Mail;

use App\Models\SupplyRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyPurchaseRequestCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupplyRequest $supplyRequest
    ) {
    }

    public function build()
    {
        $this->supplyRequest->loadMissing([
            'items.product',
            'requestedBy:id,name,email',
            'client:id,name',
        ]);

        $pdf = Pdf::loadView('supplies.request-email-pdf', [
            'supplyRequest' => $this->supplyRequest,
        ])->setPaper('letter');

        return $this->subject('Nuevo pedido de proveeduria ' . $this->supplyRequest->request_number)
            ->view('emails.supplies.purchase_request_created')
            ->attachData(
                $pdf->output(),
                'acta_recibido_proveeduria_' . $this->supplyRequest->request_number . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
