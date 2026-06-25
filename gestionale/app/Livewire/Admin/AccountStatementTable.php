<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\InvoicePayment;
use App\Models\Ownership;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
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
    
    // Cache per i metodi di pagamento
    protected $paymentMethodsCache = [];
    
    public function mount($entityId, $entity = null, $dateFrom = null, $dateTo = null, $statementType = 'all')
    {
        $this->entityId = $entityId;
        $this->entity = $entity ?? Entity::findOrFail($entityId);
        $this->dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-12 months'));
        $this->dateTo = $dateTo ?? date('Y-m-d');
        $this->statementType = $statementType;
        
        // Carica i tipi documento dalla config
        $this->typeDocuments = config('gestionale.tipo_documento', []);
        
        // Carica gli stati
        $this->statuses = [
            'issued' => ['label' => 'Emessa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'approved' => ['label' => 'Approvata', 'badge_class' => 'bg-green-100 text-green-800'],
            'paid' => ['label' => 'Pagata', 'badge_class' => 'bg-blue-100 text-blue-800'],
            'cancelled' => ['label' => 'Annullata', 'badge_class' => 'bg-red-100 text-red-800'],
        ];
        
        // Pre-carica i metodi di pagamento in cache
        $this->loadPaymentMethodsCache();
        
        $this->loadTransactions();
    }
    
    /**
     * Carica i metodi di pagamento in cache
     */
    private function loadPaymentMethodsCache()
    {
        $methods = PaymentMethod::where('is_active', true)->get();
        foreach ($methods as $method) {
            $this->paymentMethodsCache[$method->code] = $method->name;
        }
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
        
        // Notifica il componente DateRangeFilter per resettare la UI
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
        
        // Resetta anche il componente date range
        $this->dispatch('resetDates');
    }
    
    /**
     * CARICA TUTTI I MOVIMENTI (FATTURE + PAGAMENTI)
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
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']);
                $transactions[] = [
                    'id'          => 'invoice_sent_' . $inv->id,
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito emessa' : 'Fattura di Vendita',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? 0 : $inv->importo_totale,  
                    'avere'       => $isNC ? $inv->importo_totale : 0,  
                    'saldo'       => 0,
                    'type'        => 'invoice',
                ];
                
                // AGGIUNGI I PAGAMENTI DELLA FATTURA (se ce ne sono)
                $payments = InvoicePayment::where('payable_id', $inv->id)
                    ->where('payable_type', InvoiceSent::class)
                    ->where('paid_amount', '>', 0)
                    ->when($this->dateFrom && $this->dateTo, fn($q) => $q->whereBetween('paid_at', [$this->dateFrom, $this->dateTo]))
                    ->get();
                    
                foreach ($payments as $payment) {
                    // Ottieni le informazioni del conto bancario tramite IBAN
                    $bankInfo = $this->getBankAccountInfoByIban($payment->iban);
                    $methodLabel = $this->getPaymentMethodLabel($payment->payment_method);
                    
                    // Costruisci la descrizione del pagamento
                    if ($bankInfo) {
                        $descrizione = 'Pagamento ricevuto: ' . $methodLabel . ' (' . $bankInfo['name'] . ' ' . $bankInfo['n_conto'] . ')';
                    } else {
                        $descrizione = 'Pagamento ricevuto: ' . $methodLabel;
                    }
                    
                    $transactions[] = [
                        'id'          => 'payment_sent_' . $payment->id,
                        'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                        'descrizione' => $descrizione,
                        'data'        => $payment->paid_at ?? $payment->due_date,
                        'n_fattura'   => $inv->n_invoice,
                        'dare'        => 0,
                        'avere'       => $payment->paid_amount,
                        'saldo'       => 0,
                        'type'        => 'payment',
                        'payment_id'  => $payment->id,
                    ];
                }
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
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']);
                $transactions[] = [
                    'id'          => 'invoice_received_' . $inv->id,
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito ricevuta' : 'Fattura di Acquisto',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? $inv->importo_totale : 0,
                    'avere'       => $isNC ? 0 : $inv->importo_totale,
                    'saldo'       => 0,
                    'type'        => 'invoice',
                ];
            }
        }

        // ==================== PAGAMENTI FORNITORI ====================
        $paymentsData = [];

        if (in_array($this->entity->entity_type, ['fornitore', 'entrambi'])) {
            
            $paymentQuery = InvoicePayment::whereHas('payable', function($q) {
                    $q->where('id_entities', $this->entityId);
                })
                ->where('payable_type', InvoiceReceived::class)
                ->where('paid_amount', '>', 0)
                ->when($this->dateFrom && $this->dateTo, fn($q) => $q->whereBetween('paid_at', [$this->dateFrom, $this->dateTo]));
            
            if ($this->selectedOwnershipId) {
                $paymentQuery->whereHas('payable', fn($q) => $q->where('id_ownership', $this->selectedOwnershipId));
            }
            if ($this->type_invoice) {
                $paymentQuery->whereHas('payable', fn($q) => $q->where('type_invoice', $this->type_invoice));
            }
            if ($this->search) {
                $paymentQuery->whereHas('payable', fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'));
            }
            
            $payments = $paymentQuery->with(['payable.ownership'])->get();
            
            // Raggruppa i pagamenti per IBAN e metodo
            $groupedPayments = [];
            foreach ($payments as $payment) {
                $paymentDate = $payment->paid_at ? $payment->paid_at->format('Y-m-d') : null;
                if (!$paymentDate) continue;
                
                $method = $this->getPaymentMethodLabel($payment->payment_method);
                $iban = $payment->iban;
                $proprieta = $payment->payable->ownership->RagAbbrev ?? $payment->payable->ownership->Rag_Soc_intest ?? '-';
                
                // Ottieni info banca dall'IBAN
                $bankInfo = $this->getBankAccountInfoByIban($iban);
                $bankDetails = $bankInfo ? " (" . $bankInfo['name'] . ' ' . $bankInfo['n_conto'] . ")" : '';
                
                $groupKey = $paymentDate . '_' . $method . '_' . $iban . '_' . $proprieta;
                
                if (!isset($groupedPayments[$groupKey])) {
                    $groupedPayments[$groupKey] = [
                        'proprieta' => $proprieta,
                        'data' => $paymentDate,
                        'method' => $method,
                        'bankDetails' => $bankDetails,
                        'total_amount' => 0,
                        'invoices' => []
                    ];
                }
                
                $groupedPayments[$groupKey]['total_amount'] += $payment->paid_amount;
                if ($payment->payable && $payment->payable->n_invoice) {
                    $groupedPayments[$groupKey]['invoices'][] = $payment->payable->n_invoice;
                }
            }
            
            // Crea una riga di pagamento per ogni gruppo
            foreach ($groupedPayments as $group) {
                $descrizione = 'Pagamento effettuato: ' . $group['method'] . $group['bankDetails'];
                
                // Aggiungi i numeri fattura se presenti
                if (count($group['invoices']) > 0) {
                    $invoiceNumbers = array_unique($group['invoices']);
                    if (count($invoiceNumbers) <= 3) {
                        $descrizione = 'Pagamento fatture ' . implode(', ', $invoiceNumbers) . ': ' . $group['method'] . $group['bankDetails'];
                    } else {
                        $descrizione = 'Pagamento ' . count($invoiceNumbers) . ' fatture: ' . $group['method'] . $group['bankDetails'];
                    }
                }
                
                $paymentsData[] = [
                    'id'          => 'payment_group_' . md5($group['data'] . $group['method'] . $group['bankDetails']),
                    'proprieta'   => $group['proprieta'],
                    'descrizione' => $descrizione,
                    'data'        => $group['data'],
                    'n_fattura'   => '',
                    'dare'        => $group['total_amount'],
                    'avere'       => 0,
                    'saldo'       => 0,
                    'type'        => 'payment_group',
                ];
            }
        }

        // Unisci fatture e pagamenti
        $transactions = array_merge($transactions, $paymentsData);

        // Ordina per data crescente
        usort($transactions, fn($a, $b) => strcmp($a['data'], $b['data']));

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
     * Ottiene le informazioni del conto bancario tramite IBAN
     */
    private function getBankAccountInfoByIban($iban)
    {
        if (!$iban) return null;
        
        // Pulisci l'IBAN (rimuovi spazi)
        $cleanIban = preg_replace('/\s+/', '', $iban);
        
        // Cerca il conto bancario con questo IBAN (pulito o con spazi)
        $bankAccount = BankAccount::where('iban', $cleanIban)
            ->orWhere('iban', $iban)
            ->first();
            
        if (!$bankAccount) return null;
        
        return [
            'name' => $bankAccount->name,
            'n_conto' => $bankAccount->n_conto,
            'iban' => $bankAccount->iban,
        ];
    }
    
    /**
     * Ottiene l'etichetta del metodo di pagamento dal database
     */
    private function getPaymentMethodLabel($code)
    {
        if (!$code) {
            return 'Non specificato';
        }
        
        // Cerca nella cache
        if (isset($this->paymentMethodsCache[$code])) {
            return $this->paymentMethodsCache[$code];
        }
        
        // Se non trovato in cache, cerca nel database
        $method = PaymentMethod::where('code', $code)->first();
        if ($method) {
            $this->paymentMethodsCache[$code] = $method->name;
            return $method->name;
        }
        
        return $code; // Ritorna il codice se non trovato
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