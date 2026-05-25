<?php

namespace App\Observers;

use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;

class InvoicePaymentObserver
{
    public function created(InvoicePayment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    public function updated(InvoicePayment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    public function deleted(InvoicePayment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    public function restored(InvoicePayment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    private function updateInvoiceStatus(InvoicePayment $payment): void
    {
        // Usa la relazione polimorfica
        if ($payment->payable_type !== InvoiceReceived::class) {
            return; // Gestisce solo fatture passive
        }

        $invoiceId = $payment->payable_id;
        if (!$invoiceId) return;

        $invoice = InvoiceReceived::find($invoiceId);
        if (!$invoice) return;

        // Somma solo i pagamenti NON cancellati
        $totalPaid = InvoicePayment::where('payable_type', InvoiceReceived::class)
            ->where('payable_id', $invoiceId)
            ->whereNull('deleted_at')
            ->sum('paid_amount');

        $residual = $invoice->importo_totale - $totalPaid;

        if ($residual <= 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'issued';
        }

        // updateQuietly evita loop infiniti (non ri-triggera l'Observer)
        $invoice->updateQuietly(['status' => $status]);
    }
}