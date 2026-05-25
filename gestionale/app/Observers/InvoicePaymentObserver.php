<?php

namespace App\Observers;

use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
use App\Models\AccountingEntry;
use App\Models\InstallmentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentObserver
{
    /**
     * Handle the InvoicePayment "created" event.
     */
    public function created(InvoicePayment $payment): void
    {
        Log::info('InvoicePaymentObserver: created', ['payment_id' => $payment->id, 'status' => $payment->status]);
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the InvoicePayment "updated" event.
     */
    public function updated(InvoicePayment $payment): void
    {
        Log::info('InvoicePaymentObserver: updated', [
            'payment_id' => $payment->id, 
            'status' => $payment->status,
            'paid_amount' => $payment->paid_amount,
            'residual_amount' => $payment->residual_amount
        ]);
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the InvoicePayment "deleted" event.
     */
    public function deleted(InvoicePayment $payment): void
    {
        Log::info('InvoicePaymentObserver: deleted', ['payment_id' => $payment->id]);
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the InvoicePayment "restored" event.
     */
    public function restored(InvoicePayment $payment): void
    {
        Log::info('InvoicePaymentObserver: restored', ['payment_id' => $payment->id]);
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Aggiorna lo stato della fattura in base ai pagamenti effettuati
     */
    private function updateInvoiceStatus(InvoicePayment $payment): void
    {
        // Verifica che sia una fattura passiva
        if ($payment->payable_type !== InvoiceReceived::class) {
            Log::info('InvoicePaymentObserver: skip - not InvoiceReceived', ['type' => $payment->payable_type]);
            return;
        }

        $invoiceId = $payment->payable_id;
        if (!$invoiceId) {
            Log::warning('InvoicePaymentObserver: payable_id is null');
            return;
        }

        $invoice = InvoiceReceived::find($invoiceId);
        if (!$invoice) {
            Log::warning('InvoicePaymentObserver: invoice not found', ['id' => $invoiceId]);
            return;
        }

        // Calcola il totale pagato (escludendo pagamenti cancellati)
        $totalPaid = $invoice->payments()
            ->whereNull('deleted_at')
            ->sum('paid_amount');

        $residual = $invoice->importo_totale - $totalPaid;
        $newStatus = $this->determineStatus($residual, $totalPaid);

        Log::info('InvoicePaymentObserver: updating invoice status', [
            'invoice_id' => $invoice->id,
            'n_invoice' => $invoice->n_invoice,
            'total_amount' => $invoice->importo_totale,
            'total_paid' => $totalPaid,
            'residual' => $residual,
            'old_status' => $invoice->status,
            'new_status' => $newStatus
        ]);

        // Aggiorna lo stato della fattura se cambiato
        if ($invoice->status !== $newStatus) {
            $invoice->updateQuietly(['status' => $newStatus]);
        }

        // Aggiorna anche il payment principale (se esiste e diverso da quello corrente)
        $mainPayment = $invoice->payments()->first();
        if ($mainPayment && $mainPayment->id !== $payment->id) {
            if ($mainPayment->residual_amount != $residual || $mainPayment->status != $newStatus) {
                $mainPayment->updateQuietly([
                    'residual_amount' => $residual,
                    'status' => $newStatus,
                    'paid_at' => $residual <= 0 ? now() : ($mainPayment->paid_at ?? null),
                ]);
            }
        }
    }

    /**
     * Determina lo stato della fattura in base al residuo e al totale pagato
     */
    private function determineStatus(float $residual, float $totalPaid): string
    {
        if ($residual <= 0.01) {  // Tolleranza di 1 centesimo
            return 'paid';
        } elseif ($totalPaid > 0) {
            return 'partially_paid';
        } else {
            return 'issued';
        }
    }
}