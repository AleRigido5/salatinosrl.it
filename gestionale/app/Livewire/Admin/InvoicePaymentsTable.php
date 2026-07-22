<?php
// app/Livewire/Admin/InvoicePaymentsTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
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

    // Cestino
    public bool $showTrashModal = false;
    public string $trashSearch = '';
    public string $trashSortField = 'deleted_at';
    public string $trashSortDirection = 'desc';

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
     * Query di base condivisa tra elenco paginato e calcolo totali,
     * così i due restano sempre coerenti con gli stessi filtri.
     */
    private function baseQuery(): Builder
    {
        return InvoicePayment::query()
            ->with(['payable' => function($q) {
                $q->with(['ownership', 'entity']);
            }])
            // Mostra TUTTI i pagamenti che hanno un residuo > 0
            // indipendentemente dallo stato memorizzato
            ->where(function($q) {
                $q->where('residual_amount', '>', 0.01)
                  ->orWhereRaw('amount - paid_amount > 0.01');
            })
            ->when($this->search, function($q) {
                $q->whereHas('payable', function($sq) {
                    $sq->where('n_invoice', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function($q) {
                $q->where('status', $this->status);
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
    }

    public function getPaymentsProperty()
    {
        return $this->baseQuery()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Totali calcolati su TUTTI i risultati filtrati (non solo la pagina corrente),
     * con le note di credito conteggiate come importo negativo.
     */
    public function getTotalsProperty(): array
    {
        $totalAmount = 0;
        $totalResidual = 0;

        $this->baseQuery()->get()->each(function ($payment) use (&$totalAmount, &$totalResidual) {
            $isCreditNote = $payment->payable && method_exists($payment->payable, 'isCreditNote') && $payment->payable->isCreditNote();
            $residual = $payment->residual_amount > 0 ? $payment->residual_amount : $payment->amount;

            $totalAmount += $isCreditNote ? -$payment->amount : $payment->amount;
            $totalResidual += $isCreditNote ? -$residual : $residual;
        });

        return [
            'amount' => $totalAmount,
            'residual' => $totalResidual,
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
    }

    public function closeCloseModal(): void
    {
        $this->closingInvoiceId = null;
        $this->closeInvoiceSearch = '';
        $this->creditNoteResults = new Collection();
    }

    public function updatedCloseInvoiceSearch(): void
    {
        if (strlen($this->closeInvoiceSearch) < 2) {
            $this->creditNoteResults = new Collection();
            return;
        }

        $this->creditNoteResults = InvoiceReceived::where('type_invoice', 'TD04')
            ->where('n_invoice', 'like', '%' . $this->closeInvoiceSearch . '%')
            ->whereNull('closes_invoice_id') // non già usata per chiudere un'altra fattura
            ->limit(10)
            ->get();
    }

    public function closeInvoiceWithCreditNote(int $creditNoteId): void
    {
        if (!$this->closingInvoiceId) {
            return;
        }

        DB::beginTransaction();
        try {
            $invoice = InvoiceReceived::findOrFail($this->closingInvoiceId);
            $creditNote = InvoiceReceived::findOrFail($creditNoteId);

            // Collega la nota di credito alla fattura che chiude
            $creditNote->update(['closes_invoice_id' => $invoice->id]);

            // Chiudi tutte le scadenze della fattura originale
            $invoice->payments()->get()->each(function ($payment) {
                $payment->paid_amount = $payment->amount;
                $payment->status = 'paid';
                $payment->paid_at = now();
                $payment->save();
            });

            // Chiudi anche le scadenze della nota di credito stessa
            // (non deve comparire come "da incassare")
            $creditNote->payments()->get()->each(function ($payment) {
                $payment->paid_amount = $payment->amount;
                $payment->status = 'paid';
                $payment->paid_at = now();
                $payment->save();
            });

            DB::commit();

            $this->closeCloseModal();
            $this->dispatch('showSuccess', message: "Fattura {$invoice->n_invoice} chiusa con nota di credito {$creditNote->n_invoice}");
            $this->dispatch('refreshPayments');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== CESTINO ====================

    public function getTrashCountProperty(): int
    {
        return InvoicePayment::onlyTrashed()->count();
    }

    public function getTrashedPaymentsProperty()
    {
        $query = InvoicePayment::onlyTrashed()->with(['payable.ownership', 'payable.entity']);

        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('amount', 'like', $searchTerm)
                  ->orWhereHas('payable', fn($sq) => $sq->where('n_invoice', 'like', $searchTerm));
            });
        }

        return $query->orderBy($this->trashSortField, $this->trashSortDirection)
                     ->paginate(10);
    }

    public function openTrashModal(): void
    {
        $this->trashSearch = '';
        $this->showTrashModal = true;
    }

    public function closeTrashModal(): void
    {
        $this->showTrashModal = false;
        $this->trashSearch = '';
    }

    public function trashSortBy(string $field): void
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }

    public function restoreFromTrash(int $id): void
    {
        try {
            DB::beginTransaction();

            $payment = InvoicePayment::onlyTrashed()->find($id);
            if ($payment) {
                $invoice = $payment->payable;
                $paymentName = 'Pagamento di € ' . number_format($payment->amount, 2, ',', '.') . ' per fattura ' . ($invoice->n_invoice ?? '');

                // Ripristina il pagamento
                $payment->restore();

                // RESETTA LO STATO A "EMESSA / IN ATTESA"
                $payment->update([
                    'paid_amount' => 0,
                    'residual_amount' => $payment->amount,
                    'status' => 'issued',
                    'paid_at' => null,
                ]);

                // Aggiorna lo stato della fattura a "issued"
                if ($invoice) {
                    $invoice->update(['status' => 'issued']);
                }

                // Elimina le installment_transactions collegate
                $payment->installmentTransactions()->forceDelete();

                // Elimina le accounting_entries collegate
                $accountingEntries = AccountingEntry::whereHas('installmentTransactions', function($q) use ($payment) {
                    $q->where('id_invoice_payment', $payment->id);
                })->get();

                foreach ($accountingEntries as $entry) {
                    $entry->installmentTransactions()->forceDelete();
                    $entry->forceDelete();
                }

                DB::commit();

                $this->dispatch('showSuccess', message: "{$paymentName} ripristinato e stato resettato a 'Emessa / In attesa'");
                $this->dispatch('refreshPayments');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function forceDeleteFromTrash(int $id): void
    {
        try {
            DB::beginTransaction();

            $payment = InvoicePayment::onlyTrashed()->find($id);
            if ($payment) {
                $paymentName = 'Pagamento di € ' . number_format($payment->amount, 2, ',', '.');

                // Elimina le installment_transactions collegate
                $payment->installmentTransactions()->forceDelete();

                // Elimina le accounting_entries collegate
                $accountingEntries = AccountingEntry::whereHas('installmentTransactions', function($q) use ($payment) {
                    $q->where('id_invoice_payment', $payment->id);
                })->get();

                foreach ($accountingEntries as $entry) {
                    $entry->installmentTransactions()->forceDelete();
                    $entry->forceDelete();
                }

                // Elimina definitivamente il pagamento
                $payment->forceDelete();

                DB::commit();

                $this->dispatch('showSuccess', message: "{$paymentName} eliminato definitivamente.");
                $this->dispatch('refreshPayments');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function getStatusesProperty(): array
    {
        return [
            'issued' => ['label' => 'Emessa / In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'partially_paid' => ['label' => 'Pagato parzialmente', 'badge_class' => 'bg-blue-100 text-blue-800'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.invoice-payments-table', [
            'payments' => $this->payments,
            'totals' => $this->totals,
            'statuses' => $this->statuses,
            'trashCount' => $this->trashCount,
            'trashedPayments' => $this->trashedPayments,
        ]);
    }
}