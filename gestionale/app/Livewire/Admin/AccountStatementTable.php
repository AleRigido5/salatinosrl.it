<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\AccountingEntry;
use App\Models\Ownership;
use Carbon\Carbon;

class AccountStatementTable extends Component
{
    public $entityId;
    public $entity;
    public $dateFrom = '';
    public $dateTo = '';
    public $statementType = 'all';
    public $transactions = [];
    public $totalDebit = 0;
    public $totalCredit = 0;
    public $finalBalance = 0;
    
    // Filtri
    public $ownershipSearch = '';
    public $selectedOwnershipId = '';
    public $selectedOwnershipName = '';
    public $showOwnershipDropdown = false;
    public $ownershipResults = [];
    
    public $costCenterSearch = '';
    public $selectedCostCenterId = '';
    public $selectedCostCenterName = '';
    public $showCostCenterDropdown = false;
    public $costCenterResults = [];
    
    public $type_invoice = '';
    public $status = '';
    public $search = '';
    public $perPage = 100;
    
    public $typeDocuments = [];
    public $statuses = [];
    
    protected $listeners = ['dateRangeUpdated' => 'handleDateRangeUpdated'];
    
    public function mount($entityId, $entity = null, $dateFrom = null, $dateTo = null, $statementType = 'all')
    {
        $this->entityId = $entityId;
        $this->entity = $entity ?? Entity::findOrFail($entityId);
        $this->dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-12 months'));
        $this->dateTo = $dateTo ?? date('Y-m-d');
        $this->statementType = $statementType;
        
        $this->typeDocuments = config('gestionale.tipo_documento', []);
        
        $this->statuses = [
            'issued' => ['label' => 'Emessa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'approved' => ['label' => 'Approvata', 'badge_class' => 'bg-green-100 text-green-800'],
            'paid' => ['label' => 'Pagata', 'badge_class' => 'bg-blue-100 text-blue-800'],
            'cancelled' => ['label' => 'Annullata', 'badge_class' => 'bg-red-100 text-red-800'],
        ];
        
        $this->loadTransactions();
    }
    
    // ==================== GESTIONE DATE ====================
    
    public function handleDateRangeUpdated($data)
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->loadTransactions();
    }
    
    // ==================== FILTRI PROPRIETÀ ====================
    
    public function updatedOwnershipSearch()
    {
        if (strlen($this->ownershipSearch) < 2) {
            $this->ownershipResults = [];
            $this->showOwnershipDropdown = false;
            return;
        }
        
        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name', 'Rag_Soc_intest as ragione_sociale']);
        
        $this->showOwnershipDropdown = count($this->ownershipResults) > 0;
    }
    
    public function selectOwnership($id, $name)
    {
        $this->selectedOwnershipId = $id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->loadTransactions();
    }
    
    public function clearOwnership()
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->loadTransactions();
    }
    
    // ==================== ALTRI FILTRI ====================
    
    public function updatedTypeInvoice()
    {
        $this->loadTransactions();
    }
    
    public function updatedStatus()
    {
        $this->loadTransactions();
    }
    
    public function updatedSearch()
    {
        $this->loadTransactions();
    }
    
    public function clearTypeInvoice()
    {
        $this->type_invoice = '';
        $this->loadTransactions();
    }
    
    public function clearStatus()
    {
        $this->status = '';
        $this->loadTransactions();
    }
    
    public function clearSearch()
    {
        $this->search = '';
        $this->loadTransactions();
    }
    
    public function clearDates()
    {
        $this->dateFrom = date('Y-m-d', strtotime('-12 months'));
        $this->dateTo = date('Y-m-d');
        $this->loadTransactions();
        
        $this->dispatch('resetDates');
    }
    
    public function resetFilters()
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->type_invoice = '';
        $this->status = '';
        $this->search = '';
        $this->dateFrom = date('Y-m-d', strtotime('-12 months'));
        $this->dateTo = date('Y-m-d');
        $this->statementType = 'all';
        $this->loadTransactions();
        
        $this->dispatch('resetDates');
    }
    
    /**
     * CARICA TUTTI I MOVIMENTI (FATTURE + REGISTRAZIONI CONTABILI DI PAGAMENTO)
     *
     * I pagamenti vengono letti da accounting_entries (la "prima nota" compilata da
     * RegisterPayment / PaymentRegistrationModal), collegata alle fatture tramite la
     * tabella ponte installment_transactions -> invoice_payments -> payable (InvoiceSent
     * o InvoiceReceived). accounting_entries.invoice_payment_id NON viene mai valorizzato
     * dal codice di registrazione, quindi non va usato per il collegamento.
     */
    public function loadTransactions()
    {
        $transactions = [];

        // ==================== CLIENTE: fatture emesse a lui ====================
        if (in_array($this->entity->entity_type, ['cliente', 'entrambi'])) {

            $sent = InvoiceSent::where('id_entities', $this->entityId)
                ->when($this->dateFrom && $this->dateTo, fn($q) => $q->whereBetween('data_invoice', [$this->dateFrom, $this->dateTo]))
                ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
                ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
                ->when($this->status,       fn($q) => $q->where('status', $this->status))
                ->when($this->search,       fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
                ->with('ownership')
                ->get();

            foreach ($sent as $inv) {
                // FIX: una fattura va trattata come "nota di credito" (a storno) sia
                // quando il tipo documento è esplicitamente TD04/TD08, sia quando
                // l'importo_totale è semplicemente negativo (alcuni fornitori/clienti
                // emettono storni come fatture normali con importo negativo, senza
                // usare il tipo documento nota di credito). Usiamo sempre il valore
                // assoluto in dare/avere: la view mostra un importo solo se > 0,
                // quindi un valore negativo lasciato "com'era" spariva dalla tabella
                // pur essendo conteggiato correttamente nei totali.
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']) || $inv->importo_totale < 0;
                $importoAbs = abs($inv->importo_totale);

                $transactions[] = [
                    'id'          => 'invoice_sent_' . $inv->id,
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito emessa' : 'Fattura di Vendita',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? 0 : $importoAbs,
                    'avere'       => $isNC ? $importoAbs : 0,
                    'saldo'       => 0,
                    'type'        => 'invoice',
                ];
            }
        }

        // ==================== FORNITORE: fatture ricevute da lui ====================
        if (in_array($this->entity->entity_type, ['fornitore', 'entrambi'])) {

            $received = InvoiceReceived::where('id_entities', $this->entityId)
                ->when($this->dateFrom && $this->dateTo, fn($q) => $q->whereBetween('data_invoice', [$this->dateFrom, $this->dateTo]))
                ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
                ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
                ->when($this->status,       fn($q) => $q->where('status', $this->status))
                ->when($this->search,       fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
                ->with('ownership')
                ->get();

            foreach ($received as $inv) {
                // FIX: stessa logica del ramo "cliente" sopra. Questo è il caso che
                // riproduce esattamente il bug segnalato: fatture di acquisto con
                // type_invoice = 'fattura' (non TD04/TD08) ma importo_totale negativo
                // (es. -6,93 €, -12,00 €), che finivano in 'avere' come valore
                // negativo e quindi non superavano il controllo "> 0" nella vista,
                // sparendo dall'estratto conto pur essendo presenti nell'elenco fatture.
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']) || $inv->importo_totale < 0;
                $importoAbs = abs($inv->importo_totale);

                $transactions[] = [
                    'id'          => 'invoice_received_' . $inv->id,
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito ricevuta' : 'Fattura di Acquisto',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? $importoAbs : 0,
                    'avere'       => $isNC ? 0 : $importoAbs,
                    'saldo'       => 0,
                    'type'        => 'invoice',
                ];
            }
        }

        // ==================== MOVIMENTI DI CASSA/BANCA (accounting_entries) ====================
        $entryRows = $this->buildAccountingEntryRows();
        $transactions = array_merge($transactions, $entryRows);

        // Ordina per data crescente
        usort($transactions, function($a, $b) {
            $dateA = is_string($a['data']) ? $a['data'] : (method_exists($a['data'], 'format') ? $a['data']->format('Y-m-d') : $a['data']);
            $dateB = is_string($b['data']) ? $b['data'] : (method_exists($b['data'], 'format') ? $b['data']->format('Y-m-d') : $b['data']);
            return strcmp($dateA, $dateB);
        });

        // Calcola saldo progressivo
        $saldo = 0;
        foreach ($transactions as &$row) {
            $saldo += ($row['dare'] - $row['avere']);
            $row['saldo'] = $saldo;
        }

        $this->transactions = $transactions;
        $this->calculateTotals();
    }

    /**
     * Costruisce le righe dei movimenti di cassa/banca a partire da accounting_entries,
     * collegate a questa entità tramite:
     * accounting_entries -> installment_transactions -> invoice_payments -> payable (InvoiceSent | InvoiceReceived)
     */
    private function buildAccountingEntryRows(): array
    {
        $entries = AccountingEntry::whereHas('installmentTransactions.invoicePayment', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('payable_type', InvoiceReceived::class)
                        ->whereHas('payable', function ($q2) {
                            $q2->where('id_entities', $this->entityId);
                            if ($this->selectedOwnershipId) {
                                $q2->where('id_ownership', $this->selectedOwnershipId);
                            }
                            if ($this->type_invoice) {
                                $q2->where('type_invoice', $this->type_invoice);
                            }
                            if ($this->search) {
                                $q2->where('n_invoice', 'like', '%' . $this->search . '%');
                            }
                        });
                })->orWhere(function ($sub) {
                    $sub->where('payable_type', InvoiceSent::class)
                        ->whereHas('payable', function ($q2) {
                            $q2->where('id_entities', $this->entityId);
                            if ($this->selectedOwnershipId) {
                                $q2->where('id_ownership', $this->selectedOwnershipId);
                            }
                            if ($this->type_invoice) {
                                $q2->where('type_invoice', $this->type_invoice);
                            }
                            if ($this->search) {
                                $q2->where('n_invoice', 'like', '%' . $this->search . '%');
                            }
                        });
                });
            })
            ->when($this->dateFrom && $this->dateTo, fn($q) => $q->whereBetween('entry_date', [$this->dateFrom, $this->dateTo]))
            ->with([
                'paymentMethod',
                'bankAccount',
                'installmentTransactions.invoicePayment.payable.ownership',
            ])
            ->get();

        $rows = [];
        foreach ($entries as $entry) {
            $rows[] = $this->mapAccountingEntryToRow($entry);
        }

        return $rows;
    }

    /**
     * Trasforma una singola AccountingEntry in una riga dell'estratto conto.
     */
    private function mapAccountingEntryToRow(AccountingEntry $entry): array
    {
        // Proprietà: quella della fattura collegata tramite la prima rata
        $proprieta = '-';
        $firstInstallment = $entry->installmentTransactions->first();
        if ($firstInstallment && $firstInstallment->invoicePayment && $firstInstallment->invoicePayment->payable) {
            $payable = $firstInstallment->invoicePayment->payable;
            if ($payable->ownership) {
                $proprieta = $payable->ownership->RagAbbrev ?? $payable->ownership->Rag_Soc_intest ?? '-';
            }
        }

        $methodLabel = $entry->paymentMethod->name ?? 'Non specificato';
        $bankLabel = $entry->bankAccount
            ? trim($entry->bankAccount->name . ' ' . $entry->bankAccount->n_conto)
            : null;

        $isUscita = $entry->type === 'uscita';
        $label = $isUscita ? 'Pagamento effettuato' : 'Incasso ricevuto';

        $descrizione = $label . ': ' . $methodLabel . ($bankLabel ? ' (' . $bankLabel . ')' : '');

        // Anche qui normalizziamo con abs() per coerenza e sicurezza, nel caso
        // in futuro venga registrato un importo negativo su una scrittura contabile.
        $importoAbs = abs(floatval($entry->amount));

        return [
            'id'          => 'accounting_entry_' . $entry->id,
            'proprieta'   => $proprieta,
            'descrizione' => $descrizione,
            'data'        => $entry->entry_date,
            'n_fattura'   => '-',
            'dare'        => $isUscita ? $importoAbs : 0,
            'avere'       => $isUscita ? 0 : $importoAbs,
            'saldo'       => 0,
            'type'        => 'accounting_entry',
        ];
    }

    public function getExportPdfUrl(): string
    {
        $params = ['id' => $this->entityId];
        
        if ($this->dateFrom)           $params['date_from']         = $this->dateFrom;
        if ($this->dateTo)             $params['date_to']           = $this->dateTo;
        if ($this->selectedOwnershipId) $params['ownership_id']     = $this->selectedOwnershipId;
        if ($this->type_invoice)       $params['type_invoice']      = $this->type_invoice;
        if ($this->status)             $params['status']            = $this->status;
        if ($this->search)             $params['search']            = $this->search;
        
        return route('admin.entities.account-statement.export-pdf', $params);
    }

    public function getExportExcelUrl(): string
    {
        $params = ['id' => $this->entityId];
        
        if ($this->dateFrom)           $params['date_from']         = $this->dateFrom;
        if ($this->dateTo)             $params['date_to']           = $this->dateTo;
        if ($this->selectedOwnershipId) $params['ownership_id']     = $this->selectedOwnershipId;
        if ($this->type_invoice)       $params['type_invoice']      = $this->type_invoice;
        if ($this->status)             $params['status']            = $this->status;
        if ($this->search)             $params['search']            = $this->search;
        
        return route('admin.entities.account-statement.export-excel', $params);
    }
    
    public function calculateTotals()
    {
        $this->totalDebit = 0;
        $this->totalCredit = 0;
        
        foreach ($this->transactions as $transaction) {
            $this->totalDebit += $transaction['dare'];
            $this->totalCredit += $transaction['avere'];
        }
        
        $this->finalBalance = $this->totalDebit - $this->totalCredit;
    }
    
    public function render()
    {
        return view('livewire.admin.account-statement-table', [
            'typeDocuments' => $this->typeDocuments,
            'statuses' => $this->statuses,
        ]);
    }
}