<?php
// app/Console/Commands/FixNegativeInvoicePaymentsStatus.php

namespace App\Console\Commands;

use App\Models\InvoicePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corregge una tantum le scadenze (invoice_payments) con importo negativo
 * che sono state importate ed erroneamente marcate come 'paid'.
 *
 * CAUSA DEL BUG (già corretta nel modello InvoicePayment, vedi save()):
 * il calcolo del residuo usava max(0, amount - paid_amount). Per una fattura
 * con amount negativo (es. FIORINO GROUP fatt. 802/02 -27,08 €) e
 * paid_amount = 0 (mai realmente pagata), questo dava sempre residuo 0,
 * facendo scattare lo stato 'paid' già in fase di importazione.
 *
 * Questo comando individua le scadenze ancora "intatte" (paid_amount = 0,
 * quindi mai realmente applicate/consumate in un pagamento) con importo
 * negativo e stato 'paid', e le riporta a 'issued' con il residuo negativo
 * corretto, così potranno comparire ed essere selezionate nel modal di
 * registrazione pagamento.
 *
 * USO:
 *   php artisan invoices:fix-negative-payments --dry-run   (solo anteprima)
 *   php artisan invoices:fix-negative-payments              (applica, con conferma)
 */
class FixNegativeInvoicePaymentsStatus extends Command
{
    protected $signature = 'invoices:fix-negative-payments {--dry-run : Mostra solo cosa verrebbe modificato, senza salvare} {--force : Salta la richiesta di conferma}';

    protected $description = "Corregge lo stato delle scadenze con importo negativo (sostitutive di note di credito) erroneamente marcate 'paid' all'importazione, riportandole a 'issued'.";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $candidates = InvoicePayment::query()
            ->where('amount', '<', 0)
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->where('paid_amount', 0)->orWhereNull('paid_amount');
            })
            ->with('payable')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nessuna scadenza da correggere trovata: tutto ok.');
            return self::SUCCESS;
        }

        $this->info("Trovate {$candidates->count()} scadenze con importo negativo erroneamente marcate 'paid':");

        $rows = [];
        foreach ($candidates as $payment) {
            $invoice = $payment->payable;
            $rows[] = [
                $payment->id,
                $invoice->n_invoice ?? '-',
                $invoice && $invoice->data_invoice ? $invoice->data_invoice->format('d/m/Y') : '-',
                number_format((float) $payment->amount, 2, ',', '.') . ' €',
                $payment->status . ' → issued',
            ];
        }
        $this->table(['Payment ID', 'N. Fattura', 'Data', 'Importo', 'Stato'], $rows);

        if ($dryRun) {
            $this->warn('Modalità --dry-run: nessuna modifica salvata.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('Procedere con la correzione di questi ' . $candidates->count() . ' record?', true)) {
            $this->warn('Operazione annullata.');
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $fixedCount = 0;

            foreach ($candidates as $payment) {
                // paid_amount resta 0 (non è mai stata realmente applicata/pagata):
                // richiamando save() sul modello InvoicePayment già corretto,
                // residual_amount e status vengono ricalcolati con la logica
                // "sign-aware" (residuo negativo preservato, stato 'issued').
                $payment->paid_amount = 0;
                $payment->paid_at = null;
                $payment->save();

                $invoice = $payment->payable;
                if ($invoice && $invoice->status !== 'issued') {
                    $invoice->update(['status' => 'issued']);
                }

                $fixedCount++;
            }

            DB::commit();
            $this->info("Corrette con successo {$fixedCount} scadenze.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Errore durante la correzione, nessuna modifica salvata: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}