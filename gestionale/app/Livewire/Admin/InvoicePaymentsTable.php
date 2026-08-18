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
            // 🔥 FILTRO FONDAMENTALE: Solo pagamenti legati a fatture di acquisto
            ->where('payable_type', 'App\\Models\\InvoiceReceived')
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
     * Calcola il residuo "vero" di una scadenza
     * Considera sia i pagamenti cash che le note di credito allocate
     */
    private function computeResidual($payment): float
    {
        // Se la scadenza è già stata chiusa con NC, residuo 0
        if ($payment->status === 'closed_credit_note') {
            return 0.0;
        }

        // Calcola il totale allocato da note di credito per questa fattura
        $invoice = $payment->payable;
        $totalAllocated = 0;
        
        if ($invoice && method_exists($invoice, 'allocated_amount')) {
            $totalAllocated = $invoice->allocated_amount;
        } elseif ($invoice) {
            // Calcolo alternativo se non esiste il metodo allocated_amount
            $totalAllocated = CreditNoteInvoiceRelation::where('invoice_id', $invoice->id)->sum('allocated_amount');
        }

        // Il residuo effettivo è: amount - paid_amount (cash) - totalAllocated (NC)
        $residual = $payment->amount - $payment->paid_amount - $totalAllocated;
        
        // Arrotonda per evitare problemi di floating point
        $residual = round($residual, 2);
        
        // Se il residuo è negativo per arrotondamento, portalo a 0
        if ($residual < 0 && $residual > -0.01) {
            $residual = 0;
        }

        return max(0, $residual); // Il residuo non può essere negativo
    }

    /**
     * Totali (Importo / Residuo) calcolati sull'INTERO set di scadenze
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
            
            // Calcola il residuo effettivo
            $residual = $this->computeResidual($payment);

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

    // ==================== CHIUSURA FATTURA CON NOTA DI CREDITO (CORRETTA) ====================

    public function openCloseModal(int $invoiceId): void
    {
        $this->closingInvoiceId = $invoiceId;
        $this->closeInvoiceSearch = '';
        $this->creditNoteResults = new Collection();
        $this->closeInvoiceError = '';
        $this->selectedCreditNotes = [];
        
        // Pre-seleziona le NC già associate
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

    /**
     * METODO CORRETTO: Chiude una fattura con una o più note di credito
     * Aggiorna correttamente il residuo della scadenza considerando le NC
     */
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
            
            // --- 1. Calcola quanto è già allocato ---
            $existingAllocated = CreditNoteInvoiceRelation::where('invoice_id', $invoice->id)->sum('allocated_amount');
            $totalNewAllocated = 0;
            $creditNotesUsed = [];
            
            // Ottieni la scadenza principale della fattura
            $payment = $invoice->payments()->first();
            
            // Calcola quanto è già stato pagato in contanti (cash)
            $cashPaid = $payment ? $payment->paid_amount : 0;

            // --- 2. Crea le nuove relazioni e calcola il totale allocato ---
            foreach ($this->selectedCreditNotes as $creditNoteId) {
                // Verifica se la NC è già associata a questa fattura
                $existingRelation = CreditNoteInvoiceRelation::where('credit_note_id', $creditNoteId)
                    ->where('invoice_id', $invoice->id)
                    ->first();
                    
                if ($existingRelation) {
                    continue; // Salta se già associata
                }

                $creditNote = InvoiceReceived::findOrFail($creditNoteId);
                
                // Calcola quanto residua ancora disponibile su questa NC
                $usedOnOtherInvoices = CreditNoteInvoiceRelation::where('credit_note_id', $creditNoteId)
                    ->where('invoice_id', '!=', $invoice->id)
                    ->sum('allocated_amount');
                $remainingOnCreditNote = $creditNote->importo_totale - $usedOnOtherInvoices;
                
                // Calcola quanto manca ancora per chiudere la fattura (considerando già allocato e pagato)
                $remainingOnInvoice = $invoice->importo_totale - $existingAllocated - $totalNewAllocated - $cashPaid;
                
                // Quanto possiamo allocare da questa NC
                $amountToAllocate = min($remainingOnCreditNote, $remainingOnInvoice);

                if ($amountToAllocate <= 0.01) {
                    continue;
                }

                // Crea la relazione
                CreditNoteInvoiceRelation::create([
                    'credit_note_id' => $creditNoteId,
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $amountToAllocate
                ]);

                $totalNewAllocated += $amountToAllocate;
                $creditNotesUsed[] = $creditNote->n_invoice . ' (€ ' . number_format($amountToAllocate, 2, ',', '.') . ')';

                // Se la NC è completamente utilizzata, chiudila
                $newTotalUsed = $usedOnOtherInvoices + $amountToAllocate;
                if ($newTotalUsed >= $creditNote->importo_totale - 0.01) {
                    $this->closeCreditNotePayment($creditNote);
                }
            }

            $totalAllocated = $existingAllocated + $totalNewAllocated;

            // --- 3. AGGIORNA LA SCADENZA DELLA FATTURA con i valori corretti ---
            if ($payment) {
                // Calcola il residuo effettivo: importo fattura - pagamenti cash - crediti allocati
                $residualAmount = $invoice->importo_totale - $cashPaid - $totalAllocated;
                $residualAmount = round(max(0, $residualAmount), 2); // Non negativo

                // IMPORTANTE: paid_amount deve includere SOLO i pagamenti cash,
                // NON le NC. Le NC sono tracciate separatamente.
                $payment->skipAutoStatus = true;
                $payment->paid_amount = $cashPaid; // Mantieni invariato il cash pagato
                $payment->residual_amount = $residualAmount;

                // Aggiorna lo stato in base al nuovo residuo
                if ($residualAmount <= 0.01) {
                    $payment->status = 'closed_credit_note';
                    $payment->paid_at = now();
                } else {
                    // Se c'è ancora residuo, mantieni lo stato appropriato
                    if ($cashPaid > 0 && $cashPaid < $invoice->importo_totale) {
                        $payment->status = 'partially_paid';
                    } else {
                        $payment->status = 'issued';
                    }
                    $payment->paid_at = null;
                }
                $payment->save();

                // Se il residuo è zero, aggiorniamo per sicurezza
                if ($residualAmount <= 0.01) {
                    $payment->status = 'closed_credit_note';
                    $payment->paid_at = now();
                    $payment->residual_amount = 0;
                    $payment->save();
                }
            }

            // --- 4. Aggiorna il campo allocated_amount nella fattura ---
            $invoice->allocated_amount = $totalAllocated;
            $invoice->save();

            DB::commit();

            $this->closeCloseModal();
            
            // Messaggio di successo dettagliato
            $invoiceRemaining = $invoice->importo_totale - $cashPaid - $totalAllocated;
            $invoiceRemaining = round(max(0, $invoiceRemaining), 2);
            
            $message = "Fattura {$invoice->n_invoice} aggiornata con " . count($creditNotesUsed) . " nota(e) di credito: " . implode(', ', $creditNotesUsed);
            if ($invoiceRemaining > 0.01) {
                $message .= ". Residuo da pagare: € " . number_format($invoiceRemaining, 2, ',', '.');
            } else {
                $message .= ". Fattura completamente saldata con le note di credito.";
            }
            
            $this->dispatch('showSuccess', message: $message);
            $this->dispatch('refreshPayments');
            
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Errore chiusura fattura con NC: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Chiude la scadenza di una nota di credito quando è completamente utilizzata
     */
    private function closeCreditNotePayment(InvoiceReceived $creditNote): void
    {
        $payment = $creditNote->payments()->first();
        if ($payment) {
            $payment->skipAutoStatus = true;
            $payment->paid_amount = $payment->amount;
            $payment->residual_amount = 0;
            $payment->status = 'closed_credit_note';
            $payment->paid_at = now();
            $payment->save();
        }
    }

    // ==================== CORREZIONE STATI ====================

    public function fixAllCreditNoteStatuses(): void
    {
        try {
            DB::beginTransaction();
            
            // Trova tutte le fatture che hanno NC associate ma non sono aggiornate
            $invoicesWithNC = DB::table('credit_note_invoice_relations')
                ->select('invoice_id', DB::raw('SUM(allocated_amount) as total_allocated'))
                ->groupBy('invoice_id')
                ->get();

            $updatedCount = 0;
            
            foreach ($invoicesWithNC as $item) {
                $invoice = InvoiceReceived::find($item->invoice_id);
                if (!$invoice) continue;
                
                $payment = $invoice->payments()->first();
                if (!$payment) continue;
                
                $cashPaid = $payment->paid_amount;
                $residualAmount = $invoice->importo_totale - $cashPaid - $item->total_allocated;
                $residualAmount = round(max(0, $residualAmount), 2);
                
                if ($residualAmount <= 0.01 && $payment->status !== 'closed_credit_note') {
                    $payment->skipAutoStatus = true;
                    $payment->residual_amount = 0;
                    $payment->status = 'closed_credit_note';
                    $payment->paid_at = now();
                    $payment->save();
                    $updatedCount++;
                } elseif ($residualAmount > 0.01 && $payment->residual_amount != $residualAmount) {
                    $payment->skipAutoStatus = true;
                    $payment->residual_amount = $residualAmount;
                    $payment->save();
                    $updatedCount++;
                }
            }
            
            DB::commit();
            
            if ($updatedCount > 0) {
                $this->dispatch('showSuccess', message: "Corretti {$updatedCount} pagamenti con residuo aggiornato");
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
        
        if ($this->perPage != 100000) {
            $params['per_page'] = $this->perPage;
        }
        
        return route('admin.invoice-payments.export-pdf', $params);
    }

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