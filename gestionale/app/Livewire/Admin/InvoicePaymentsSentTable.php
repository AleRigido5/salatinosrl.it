<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoicePayment;
use App\Models\InvoiceSent;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\InstallmentTransaction;
use App\Models\AccountingEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoicePaymentsSentTable extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    // Filtri
    public $search = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $clientFilter = '';
    public $invoiceNumberFilter = '';
    public $perPage = 100;
    
    // Autocomplete Proprietà
    public $ownershipSearch = '';
    public $selectedOwnershipId = null;
    public $selectedOwnershipName = '';
    public $showOwnershipDropdown = false;
    public $ownershipResults;
    
    // Autocomplete Cliente
    public $clientSearch = '';
    public $selectedClientId = null;
    public $selectedClientName = '';
    public $showClientDropdown = false;
    public $clientResults;
    
    // Ordinamento
    public $sortField = 'due_date';
    public $sortDirection = 'asc';
    
    // Modal dettagli
    public $showModal = false;
    public $selectedPayment = null;

    // Chiusura fattura con nota di credito
    public ?int $closingInvoiceId = null;
    public string $closeInvoiceSearch = '';
    public Collection $creditNoteResults;

    // Tipi documento SDI trattati come nota di credito. FIX: prima veniva
    // controllato solo 'TD04', quindi le note di credito salvate con tipo
    // 'TD08' (o comunque diverso da TD04) non comparivano mai nella
    // ricerca "Chiudi fattura con nota di credito", indipendentemente da
    // quanti caratteri si digitavano. Stessa convenzione già usata in
    // InvoiceSentTable / InvoiceSentStatistics / StatisticsGeneral.
    protected const CREDIT_NOTE_TYPES = ['TD04', 'TD08'];
    
    // Eventi ascoltati
    protected $listeners = [
        'refreshPayments' => 'refreshTable',
        'paymentRegistered' => 'refreshTable',
    ];
    
    // Stati disponibili
    public $statuses = [
        'issued' => ['label' => 'In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
        'partially_paid' => ['label' => 'Pagato parzialmente', 'badge_class' => 'bg-blue-100 text-blue-800'],
        'paid' => ['label' => 'Pagato', 'badge_class' => 'bg-green-100 text-green-800'],
        'overdue' => ['label' => 'Scaduto', 'badge_class' => 'bg-red-100 text-red-800'],
        'closed_credit_note' => ['label' => 'Saldato con NC', 'badge_class' => 'bg-purple-100 text-purple-800'],
    ];
    
    public function mount()
    {
        $this->ownershipResults = new Collection();
        $this->clientResults = new Collection();
        $this->creditNoteResults = new Collection();
        $this->selectedOwnershipId = null;
        $this->selectedOwnershipName = '';
        $this->selectedClientId = null;
        $this->selectedClientName = '';
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->clientFilter = '';
        $this->invoiceNumberFilter = '';
        $this->selectedOwnershipId = null;
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->selectedClientId = null;
        $this->selectedClientName = '';
        $this->clientSearch = '';
        $this->ownershipResults = new Collection();
        $this->clientResults = new Collection();
        $this->resetPage();
        
        $this->dispatch('resetDates');
    }
    
    public function clearOwnership()
    {
        $this->selectedOwnershipId = null;
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->ownershipResults = new Collection();
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }
    
    public function clearClient()
    {
        $this->selectedClientId = null;
        $this->selectedClientName = '';
        $this->clientSearch = '';
        $this->clientResults = new Collection();
        $this->resetPage();
        $this->dispatch('clearClientInput');
    }
    
    public function clearStatus()
    {
        $this->statusFilter = '';
        $this->resetPage();
    }
    
    public function refreshTable()
    {
        $this->resetPage();
    }
    
    public function updatedOwnershipSearch()
    {
        if ($this->selectedOwnershipId && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }
        
        if ($this->selectedOwnershipId) {
            $this->selectedOwnershipId = null;
            $this->selectedOwnershipName = '';
            $this->resetPage();
        }
        
        if (strlen($this->ownershipSearch) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }
        
        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function($query) {
                $query->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                      ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                      ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get()
            ->map(function($item) {
                return (object)[
                    'id' => $item->id_proprieta,
                    'name' => $item->RagAbbrev ?? $item->Rag_Soc_intest ?? 'Proprietà',
                    'ragione_sociale' => $item->Rag_Soc_intest
                ];
            });
        
        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }
    
    public function selectOwnership($id, $name)
    {
        $this->selectedOwnershipId = $id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->ownershipResults = new Collection();
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }
    
    public function updatedClientSearch()
    {
        if ($this->selectedClientId && $this->clientSearch === $this->selectedClientName) {
            $this->showClientDropdown = false;
            return;
        }
        
        if ($this->selectedClientId) {
            $this->selectedClientId = null;
            $this->selectedClientName = '';
            $this->resetPage();
        }
        
        if (strlen($this->clientSearch) < 2) {
            $this->clientResults = new Collection();
            $this->showClientDropdown = false;
            return;
        }
        
        $this->clientResults = Entity::where('valid', 1)
            ->where(function($query) {
                $query->where('ragione_sociale', 'like', '%' . $this->clientSearch . '%')
                      ->orWhere('nome', 'like', '%' . $this->clientSearch . '%')
                      ->orWhere('cognome', 'like', '%' . $this->clientSearch . '%');
            })
            ->limit(10)
            ->get()
            ->map(function($item) {
                return (object)[
                    'id' => $item->id_cliente,
                    'name' => $item->ragione_sociale ?: ($item->nome . ' ' . $item->cognome),
                    'piva' => $item->p_iva
                ];
            });
        
        $this->showClientDropdown = $this->clientResults->isNotEmpty();
    }
    
    public function selectClient($id, $name)
    {
        $this->selectedClientId = $id;
        $this->selectedClientName = $name;
        $this->clientSearch = $name;
        $this->clientResults = new Collection();
        $this->showClientDropdown = false;
        $this->resetPage();
    }
    
    public function showDetails($paymentId)
    {
        $this->selectedPayment = InvoicePayment::with(['payable.entity', 'payable.ownership'])
            ->where('payable_type', InvoiceSent::class)
            ->find($paymentId);
        
        if ($this->selectedPayment) {
            $this->showModal = true;
        }
    }
    
    public function closeModal()
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

    /**
     * FIX: la ricerca controllava solo type_invoice = 'TD04', escludendo
     * di fatto qualunque nota di credito salvata con un codice diverso
     * (es. TD08), che quindi non compariva mai — indipendentemente dalla
     * lunghezza del testo digitato. Ora usa whereIn su tutti i tipi
     * documento trattati come nota di credito nel resto del gestionale.
     *
     * NOTA: se dopo questo fix una NC specifica continua a non comparire,
     * le cause più probabili sono altre due, entrambe intenzionali nella
     * query:
     * 1) closes_invoice_id già valorizzato -> la NC risulta già collegata
     *    a un'altra fattura e viene esclusa (whereNull).
     * 2) il numero digitato non corrisponde esattamente al valore salvato
     *    in n_invoice (es. sta in un campo diverso, come n_invoice_ext).
     */
    public function updatedCloseInvoiceSearch(): void
    {
        if (strlen(trim($this->closeInvoiceSearch)) < 2) {
            $this->creditNoteResults = new Collection();
            return;
        }

        try {
            $this->creditNoteResults = InvoiceSent::whereIn('type_invoice', self::CREDIT_NOTE_TYPES)
                ->where('n_invoice', 'like', '%' . $this->closeInvoiceSearch . '%')
                ->whereNull('closes_invoice_id')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Errore ricerca nota di credito vendite: ' . $e->getMessage());
            $this->creditNoteResults = new Collection();
            $this->dispatch('showError', message: 'Errore nella ricerca: ' . $e->getMessage());
        }
    }

    public function closeInvoiceWithCreditNote(int $creditNoteId): void
    {
        if (!$this->closingInvoiceId) {
            return;
        }

        DB::beginTransaction();
        try {
            $invoice = InvoiceSent::findOrFail($this->closingInvoiceId);
            $creditNote = InvoiceSent::findOrFail($creditNoteId);

            // Collega la nota di credito alla fattura che chiude
            $creditNote->update(['closes_invoice_id' => $invoice->id]);

            // Chiudi tutte le scadenze della fattura originale
            $invoice->payments()->get()->each(function ($payment) {
                $payment->skipAutoStatus = true;
                $payment->paid_amount = $payment->amount;
                $payment->status = 'closed_credit_note';
                $payment->paid_at = now();
                $payment->save();
            });

            // Chiudi anche le scadenze della nota di credito stessa
            $creditNote->payments()->get()->each(function ($payment) {
                $payment->skipAutoStatus = true;
                $payment->paid_amount = $payment->amount;
                $payment->status = 'closed_credit_note';
                $payment->paid_at = now();
                $payment->save();
            });

            DB::commit();

            $this->closeCloseModal();
            $this->dispatch('showSuccess', message: "Fattura {$invoice->n_invoice} chiusa con nota di credito {$creditNote->n_invoice}");
            $this->dispatch('refreshPayments');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Errore chiusura fattura vendita con NC: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function getPaymentsProperty()
    {
        $query = InvoicePayment::where('payable_type', InvoiceSent::class)
            ->with(['payable.entity', 'payable.ownership'])
            ->join('invoices_sent', 'invoice_payments.payable_id', '=', 'invoices_sent.id')
            ->join('entities', 'invoices_sent.id_entities', '=', 'entities.id_cliente')
            ->select('invoice_payments.*')
            // Mostra SOLO i pagamenti che hanno un residuo > 0
            ->where(function($q) {
                $q->where('invoice_payments.residual_amount', '>', 0.01)
                  ->orWhereRaw('invoice_payments.amount - invoice_payments.paid_amount > 0.01');
            });
        
        // Filtro Proprietà
        if ($this->selectedOwnershipId) {
            $query->where('invoices_sent.id_ownership', $this->selectedOwnershipId);
        }
        
        // Filtro Cliente
        if ($this->selectedClientId) {
            $query->where('invoices_sent.id_entities', $this->selectedClientId);
        }
        
        // Filtro ricerca generica
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('invoices_sent.n_invoice', 'like', '%' . $this->search . '%')
                  ->orWhere('entities.ragione_sociale', 'like', '%' . $this->search . '%')
                  ->orWhere('entities.nome', 'like', '%' . $this->search . '%')
                  ->orWhere('entities.cognome', 'like', '%' . $this->search . '%');
            });
        }
        
        // Filtro stato - escludi 'paid' perché non dovrebbe più apparire
        if (!empty($this->statusFilter)) {
            if ($this->statusFilter !== 'paid') {
                $query->where('invoice_payments.status', $this->statusFilter);
            }
        }
        
        // Filtro data da
        if (!empty($this->dateFrom)) {
            $query->whereDate('invoice_payments.due_date', '>=', $this->dateFrom);
        }
        
        // Filtro data a
        if (!empty($this->dateTo)) {
            $query->whereDate('invoice_payments.due_date', '<=', $this->dateTo);
        }
        
        // Filtro numero fattura
        if (!empty($this->invoiceNumberFilter)) {
            $query->where('invoices_sent.n_invoice', 'like', '%' . $this->invoiceNumberFilter . '%');
        }
        
        // Ordinamento
        if ($this->sortField === 'client_name') {
            $query->orderBy('entities.ragione_sociale', $this->sortDirection);
        } elseif ($this->sortField === 'invoice_number') {
            $query->orderBy('invoices_sent.n_invoice', $this->sortDirection);
        } elseif ($this->sortField === 'amount') {
            $query->orderBy('invoice_payments.amount', $this->sortDirection);
        } elseif ($this->sortField === 'residual_amount') {
            $query->orderBy('invoice_payments.residual_amount', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
        
        if ($this->perPage == 100000) {
            return $query->paginate($query->count());
        }
        
        return $query->paginate($this->perPage);
    }

    /**
     * Genera l'URL per l'esportazione PDF con tutti i filtri attivi.
     */
    public function getExportPdfUrl()
    {
        return route('admin.invoice-payments-sent.export-pdf', $this->exportParams());
    }

    /**
     * Genera l'URL per l'esportazione Excel con tutti i filtri attivi.
     */
    public function getExportExcelUrl()
    {
        return route('admin.invoice-payments-sent.export-excel', $this->exportParams());
    }

    /**
     * Costruisce l'array di query string con i filtri attualmente attivi
     * nella tabella, da passare alle route di export.
     */
    protected function exportParams(): array
    {
        $params = [];

        if ($this->selectedOwnershipId) {
            $params['ownership_id'] = $this->selectedOwnershipId;
        }
        if ($this->selectedClientId) {
            $params['client_id'] = $this->selectedClientId;
        }
        if ($this->statusFilter) {
            $params['status'] = $this->statusFilter;
        }
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->invoiceNumberFilter) {
            $params['invoice_number'] = $this->invoiceNumberFilter;
        }
        if ($this->search) {
            $params['search'] = $this->search;
        }

        return $params;
    }
    
    public function render()
    {
        return view('livewire.admin.invoice-payments-sent-table', [
            'payments' => $this->payments,
        ]);
    }
}