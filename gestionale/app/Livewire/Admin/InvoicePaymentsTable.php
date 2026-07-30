<?php
// app/Livewire/Admin/InvoicePaymentsTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
use App\Models\CreditNoteInvoiceRelation;
use App\Models\AccountingEntry;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class InvoicePaymentsTable extends Component
{
    use WithPagination;

    // Filtri ricerca
    public string $search = '';
    public string $status = '';
    public string $invoiceSearch = '';

    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Autocomplete Fornitore
    public string $supplierSearch = '';
    public Collection $supplierResults;
    public string $selectedSupplierId = '';
    public string $selectedSupplierName = '';
    public bool $showSupplierDropdown = false;

    // Date
    public string $dateFrom = '';
    public string $dateTo = '';

    // Ordinamento
    public string $sortField = 'due_date';
    public string $sortDirection = 'asc';
    public int $perPage = 100;

    // Modal dettagli
    public $selectedPayment = null;
    public bool $showModal = false;

    // Chiusura fattura con nota di credito
    public ?int $closingInvoiceId = null;
    public string $closeInvoiceSearch = '';
    public Collection $creditNoteResults;
    public string $closeInvoiceError = '';
    public array $selectedCreditNotes = [];

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
        'refreshPayments' => 'refreshTable',
        'paymentRegistered' => 'refreshTable',
        'refreshInvoices' => 'refreshTable',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
        $this->creditNoteResults = new Collection();
        $this->closeInvoiceError = '';
        $this->selectedCreditNotes = [];
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if ($this->selectedOwnershipId && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if ($this->selectedOwnershipId) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->resetPage();
        }

        if (strlen($this->ownershipSearch) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name']);

        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        if ($this->selectedSupplierId && $this->supplierSearch === $this->selectedSupplierName) {
            $this->showSupplierDropdown = false;
            return;
        }

        if ($this->selectedSupplierId) {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
            $this->resetPage();
        }

        if (strlen($this->supplierSearch) < 2) {
            $this->supplierResults = new Collection();
            $this->showSupplierDropdown = false;
            return;
        }

        $this->supplierResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name']);

        $this->showSupplierDropdown = $this->supplierResults->isNotEmpty();
    }

    public function selectSupplier(int $id, string $name): void
    {
        $this->selectedSupplierId = (string)$id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
        $this->resetPage();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
        $this->resetPage();
        $this->dispatch('clearSupplierInput');
    }

    // ==================== FILTRI E QUERY ====================

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->invoiceSearch = '';
        $this->clearOwnership();
        $this->clearSupplier();
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates');
    }

    public function refreshTable(): void
    {
        $this->resetPage();
    }

    public function clearStatus(): void
    {
        $this->status = '';
        $this->resetPage();
    }

    /**
     * Query di base - Mostra TUTTI i pagamenti
     */
    private function baseQuery(): Builder
    {
        $query = InvoicePayment::query()
            ->with(['payable' => function($q) {
                $q->with(['ownership', 'entity']);
            }])
            ->when($this->search, function($q) {
                $q->whereHas('payable', function($sq) {
                    $sq->where('n_invoice', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function($q) {
                if ($this->status === 'closed_credit_note') {
                    $q->where(function($sq) {
                        $sq->where('invoice_payments.status', 'closed_credit_note')
                          ->orWhereRaw("
                              invoice_payments.status = 'paid' 
                              AND invoice_payments.payable_type = 'App\\Models\\InvoiceReceived' 
                              AND EXISTS (
                                  SELECT 1 FROM invoices_received ir 
                                  WHERE ir.id = invoice_payments.payable_id 
                                  AND ir.deleted_at IS NULL
                                  AND (
                                      EXISTS (
                                          SELECT 1 FROM credit_note_invoice_relations r
                                          INNER JOIN invoices_received nc ON nc.id = r.credit_note_id
                                          WHERE r.invoice_id = ir.id 
                                          AND nc.type_invoice = 'TD04'
                                          AND nc.deleted_at IS NULL
                                      )
                                      OR 
                                      EXISTS (
                                          SELECT 1 FROM credit_note_invoice_relations r
                                          WHERE r.credit_note_id = ir.id
                                      )
                                      OR
                                      EXISTS (
                                          SELECT 1 FROM invoices_received nc 
                                          WHERE nc.closes_invoice_id = ir.id 
                                          AND nc.type_invoice = 'TD04'
                                          AND nc.deleted_at IS NULL
                                      )
                                  )
                              )
                          ");
                    });
                } else {
                    $q->where('invoice_payments.status', $this->status);
                }
            })
            ->when($this->invoiceSearch, function($q) {
                $q->whereHas('payable', fn($sq) => $sq->where('n_invoice', 'like', '%' . $this->invoiceSearch . '%'));
            })
            ->when($this->selectedOwnershipId, function($q) {
                $q->whereHas('payable', fn($sq) => $sq->where('id_ownership', $this->selectedOwnershipId));
            })
            ->when($this->selectedSupplierId, function($q) {
                $q->whereHas('payable', fn($sq) => $sq->where('id_entities', $this->selectedSupplierId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('due_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('due_date', '<=', $this->dateTo));

        return $query;
    }

    public function getPaymentsProperty()
    {
        return $this->baseQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Calcola il residuo "vero" di una scadenza: 0 se pagata (o saldata con NC),
     * altrimenti residual_amount se valorizzato, altrimenti amount - paid_amount.
     */
    private function computeResidual($payment, bool $isClosedByNC = false): float
    {
        if ($isClosedByNC || in_array($payment->status, ['paid', 'closed_credit_note'])) {
            return 0.0;
        }

        if ($payment->residual_amount > 0) {
            return (float) $payment->residual_amount;
        }

        return max(0.0, (float) $payment->amount - (float) $payment->paid_amount);
    }

    /**
     * Totali (Importo / Residuo) calcolati sull'INTERO set di scadenze che
     * rispettano i filtri correnti, non solo sulla pagina visibile.
     */
    public function getPaymentTotalsProperty(): array
    {
        $payments = $this->baseQuery()->get();

        $totaleImporto = 0;
        $totaleResiduo = 0;

        foreach ($payments as $payment) {
            $invoice = $payment->payable;
            if (!$invoice) {
                continue;
            }

            $isCreditNote = method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
            $isClosedByNC = $payment->status === 'closed_credit_note' ||
                ($payment->status === 'paid' && method_exists($invoice, 'isClosedByCreditNote') && $invoice->isClosedByCreditNote());

            $residual = $this->computeResidual($payment, $isClosedByNC);

            $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
            $displayResidual = $isCreditNote ? -$residual : $residual;

            $totaleImporto += $displayAmount;
            $totaleResiduo += $displayResidual;
        }

        return [
            'importo' => round($totaleImporto, 2),
            'residuo' => round($totaleResiduo, 2),
        ];
    }

    // ==================== MODAL DETTAGLI ====================

    public function showDetails(int $id): void
    {
        $this->selectedPayment = InvoicePayment::with(['payable.ownership', 'payable.entity'])->find($id);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedPayment = null;
    }

    // ==================== CHIUSURA FATTURA CON NOTA DI CREDITO ====================

    public function openCloseModal(int $invoiceId): void
    {
        $this->closingInvoiceId = $invoiceId;
        $this->closeInvoiceSearch = '';
        $this->creditNoteResults = new Collection();
        $this->closeInvoiceError = '';
        $this->selectedCreditNotes = [];
        
        $existingRelations = CreditNoteInvoiceRelation::where('invoice_id', $invoiceId)->get();
        foreach ($existingRelations as $rel) {
            $this->selectedCreditNotes[] = $rel->credit_note_id;
        }
    }

    public function closeCloseModal(): void
    {
        $this->closingInvoiceId = null;
        $this->closeInvoiceSearch = '';
        $this->creditNoteResults = new Collection();
        $this->closeInvoiceError = '';
        $this->selectedCreditNotes = [];
    }

    public function updatedCloseInvoiceSearch(): void
    {
        $this->closeInvoiceError = '';
        
        if (strlen($this->closeInvoiceSearch) < 2) {
            $this->creditNoteResults = new Collection();
            return;
        }

        try {
            $invoice = InvoiceReceived::find($this->closingInvoiceId);
            
            if (!$invoice) {
                $this->creditNoteResults = new Collection();
                $this->closeInvoiceError = 'Fattura non trovata';
                return;
            }

            $this->creditNoteResults = InvoiceReceived::where('type_invoice', 'TD04')
                ->where('n_invoice', 'like', '%' . $this->closeInvoiceSearch . '%')
                ->where('id_ownership', $invoice->id_ownership)
                ->where('id_entities', $invoice->id_entities)
                ->where(function($q) {
                    $q->whereRaw('
                        importo_totale > COALESCE((
                            SELECT SUM(allocated_amount) 
                            FROM credit_note_invoice_relations 
                            WHERE credit_note_id = invoices_received.id
                        ), 0)
                    ');
                })
                ->orderBy('n_invoice', 'asc')
                ->limit(10)
                ->get();

            if ($this->creditNoteResults->isEmpty()) {
                $this->closeInvoiceError = 'Nessuna nota di credito disponibile per questa proprietà e fornitore';
            }

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Errore ricerca nota di credito: ' . $e->getMessage());
            $this->creditNoteResults = new Collection();
            $this->closeInvoiceError = 'Errore nella ricerca: ' . $e->getMessage();
        }
    }

    public function toggleCreditNoteSelection(int $creditNoteId): void
    {
        if (in_array($creditNoteId, $this->selectedCreditNotes)) {
            $this->selectedCreditNotes = array_diff($this->selectedCreditNotes, [$creditNoteId]);
        } else {
            $this->selectedCreditNotes[] = $creditNoteId;
        }
    }

    public function closeInvoiceWithCreditNotes(): void
    {
        if (!$this->closingInvoiceId) {
            $this->dispatch('showError', message: 'Nessuna fattura selezionata');
            return;
        }

        if (empty($this->selectedCreditNotes)) {
            $this->dispatch('showError', message: 'Seleziona almeno una nota di credito');
            return;
        }

        DB::beginTransaction();
        try {
            $invoice = InvoiceReceived::findOrFail($this->closingInvoiceId);
            $totalAllocated = 0;
            $creditNotesUsed = [];

            foreach ($this->selectedCreditNotes as $creditNoteId) {
                $creditNote = InvoiceReceived::findOrFail($creditNoteId);

                $existingRelation = CreditNoteInvoiceRelation::where('credit_note_id', $creditNoteId)
                    ->where('invoice_id', $invoice->id)
                    ->first();
                    
                if ($existingRelation) {
                    continue;
                }

                $remainingAmount = $creditNote->importo_totale - $creditNote->allocated_amount;
                $amountToAllocate = min($invoice->importo_totale - $totalAllocated, $remainingAmount);

                if ($amountToAllocate <= 0) {
                    continue;
                }

                CreditNoteInvoiceRelation::create([
                    'credit_note_id' => $creditNoteId,
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $amountToAllocate
                ]);

                $totalAllocated += $amountToAllocate;
                $creditNotesUsed[] = $creditNote->n_invoice;

                $newRemaining = $creditNote->remaining_amount;
                if ($newRemaining <= 0.01) {
                    $creditNote->payments()->get()->each(function ($payment) {
                        $payment->skipAutoStatus = true;
                        $payment->paid_amount = $payment->amount;
                        $payment->residual_amount = 0;
                        $payment->status = 'closed_credit_note';
                        $payment->paid_at = now();
                        $payment->save();
                    });
                }
            }

            $invoiceRemaining = $invoice->importo_totale - $totalAllocated;
            if ($invoiceRemaining <= 0.01) {
                $invoice->payments()->get()->each(function ($payment) {
                    $payment->skipAutoStatus = true;
                    $payment->paid_amount = $payment->amount;
                    $payment->residual_amount = 0;
                    $payment->status = 'closed_credit_note';
                    $payment->paid_at = now();
                    $payment->save();
                });
            }

            DB::commit();

            $this->closeCloseModal();
            
            $message = "Fattura {$invoice->n_invoice} chiusa con le note di credito: " . implode(', ', $creditNotesUsed);
            if ($invoiceRemaining > 0.01) {
                $message .= ". Restano da allocare " . number_format($invoiceRemaining, 2, ',', '.') . " €";
            } else {
                $message .= ". Fattura completamente saldata.";
            }
            
            $this->dispatch('showSuccess', message: $message);
            $this->dispatch('refreshPayments');
            
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Errore chiusura fattura con NC: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== CORREZIONE STATI ====================

    public function fixAllCreditNoteStatuses(): void
    {
        try {
            DB::beginTransaction();
            
            $sql = "
                UPDATE invoice_payments ip
                SET ip.status = 'closed_credit_note',
                    ip.residual_amount = 0,
                    ip.paid_at = NOW()
                WHERE ip.status = 'paid'
                  AND ip.payable_type = 'App\\Models\\InvoiceReceived'
                  AND ip.deleted_at IS NULL
                  AND EXISTS (
                      SELECT 1 FROM invoices_received ir 
                      WHERE ir.id = ip.payable_id 
                      AND ir.deleted_at IS NULL
                      AND (
                          EXISTS (
                              SELECT 1 FROM credit_note_invoice_relations r
                              INNER JOIN invoices_received nc ON nc.id = r.credit_note_id
                              WHERE r.invoice_id = ir.id 
                              AND nc.type_invoice = 'TD04'
                              AND nc.deleted_at IS NULL
                          )
                          OR 
                          EXISTS (
                              SELECT 1 FROM credit_note_invoice_relations r
                              WHERE r.credit_note_id = ir.id
                          )
                          OR
                          EXISTS (
                              SELECT 1 FROM invoices_received nc 
                              WHERE nc.closes_invoice_id = ir.id 
                              AND nc.type_invoice = 'TD04'
                              AND nc.deleted_at IS NULL
                          )
                      )
                  )
            ";
            
            $updatedCount = DB::update($sql);
            
            DB::commit();
            
            if ($updatedCount > 0) {
                $this->dispatch('showSuccess', message: "Corretti {$updatedCount} pagamenti con stato 'Saldato con NC'");
            } else {
                $this->dispatch('showInfo', message: 'Nessun pagamento da correggere.');
            }
            
            $this->dispatch('refreshPayments');
            
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Errore correzione stati NC: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function getStatusesProperty(): array
    {
        return [
            'issued' => ['label' => 'Emessa / In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'partially_paid' => ['label' => 'Pagato parzialmente', 'badge_class' => 'bg-blue-100 text-blue-800'],
            'paid' => ['label' => 'Pagato', 'badge_class' => 'bg-green-100 text-green-800'],
            'closed_credit_note' => ['label' => 'Saldato con NC', 'badge_class' => 'bg-purple-100 text-purple-800'],
        ];
    }

    // ==================== METODI PER ESPORTAZIONE ====================

    /**
     * Genera l'URL per l'esportazione PDF con tutti i filtri attivi
     */
    public function getExportPdfUrl()
    {
        $params = [];
        
        if ($this->selectedOwnershipId) {
            $params['ownership_id'] = $this->selectedOwnershipId;
        }
        if ($this->selectedSupplierId) {
            $params['supplier_id'] = $this->selectedSupplierId;
        }
        if ($this->invoiceSearch) {
            $params['invoice_search'] = $this->invoiceSearch;
        }
        if ($this->status) {
            $params['status'] = $this->status;
        }
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->sortField) {
            $params['sort_field'] = $this->sortField;
        }
        if ($this->sortDirection) {
            $params['sort_direction'] = $this->sortDirection;
        }
        
        // Passa il per_page per limitare i risultati come nella tabella
        if ($this->perPage != 100000) {
            $params['per_page'] = $this->perPage;
        }
        
        return route('admin.invoice-payments.export-pdf', $params);
    }

    /**
     * Genera l'URL per l'esportazione Excel con tutti i filtri attivi
     */
    public function getExportExcelUrl()
    {
        $params = [];
        
        if ($this->selectedOwnershipId) {
            $params['ownership_id'] = $this->selectedOwnershipId;
        }
        if ($this->selectedSupplierId) {
            $params['supplier_id'] = $this->selectedSupplierId;
        }
        if ($this->invoiceSearch) {
            $params['invoice_search'] = $this->invoiceSearch;
        }
        if ($this->status) {
            $params['status'] = $this->status;
        }
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->sortField) {
            $params['sort_field'] = $this->sortField;
        }
        if ($this->sortDirection) {
            $params['sort_direction'] = $this->sortDirection;
        }
        
        // Passa il per_page per limitare i risultati come nella tabella
        if ($this->perPage != 100000) {
            $params['per_page'] = $this->perPage;
        }
        
        return route('admin.invoice-payments.export-excel', $params);
    }

    public function render()
    {
        return view('livewire.admin.invoice-payments-table', [
            'payments' => $this->payments,
            'statuses' => $this->statuses,
            'paymentTotals' => $this->paymentTotals,
        ]);
    }
}