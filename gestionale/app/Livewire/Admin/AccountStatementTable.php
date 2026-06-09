<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;

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
            ->get(['id_proprieta as id', 'RagAbbrev as name']);
        
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
    }
    
    public function loadTransactions()
    {
        $transactions = [];
        $runningBalance = 0;
        
        // Query base per fatture di vendita
        $invoiceQuery = InvoiceSent::where('id_entities', $this->entityId)
            ->whereBetween('data_invoice', [$this->dateFrom, $this->dateTo]);
        
        // Applica filtri
        if ($this->selectedOwnershipId) {
            $invoiceQuery->where('id_ownership', $this->selectedOwnershipId);
        }
        if ($this->type_invoice) {
            $invoiceQuery->where('type_invoice', $this->type_invoice);
        }
        if ($this->status) {
            $invoiceQuery->where('status', $this->status);
        }
        if ($this->search) {
            $invoiceQuery->where('n_invoice', 'like', '%' . $this->search . '%');
        }
        if ($this->selectedCostCenterId) {
            $invoiceQuery->whereHas('rows', function($q) {
                $q->where('id_cost_center', $this->selectedCostCenterId);
            });
        }
        
        // PER CLIENTI: fatture di vendita e incassi
        if ($this->entity->entity_type == 'cliente' || $this->entity->entity_type == 'entrambi') {
            
            // FATTURE DI VENDITA
            if ($this->statementType != 'payments') {
                $invoices = $invoiceQuery->orderBy('data_invoice', 'asc')
                    ->with('ownership')
                    ->get();
                
                foreach ($invoices as $invoice) {
                    $runningBalance += $invoice->importo_totale;
                    
                    $proprieta = '-';
                    if ($invoice->ownership) {
                        $proprieta = $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-';
                    }
                    
                    $transactions[] = [
                        'proprieta' => $proprieta,
                        'descrizione' => $invoice->type_invoice == 'TD04' ? 'Nota di Credito' : 'Fattura di Vendita',
                        'data' => $invoice->data_invoice,
                        'n_fattura' => $invoice->n_invoice,
                        'dare' => $invoice->type_invoice == 'TD04' ? 0 : $invoice->importo_totale,
                        'avere' => $invoice->type_invoice == 'TD04' ? $invoice->importo_totale : 0,
                        'saldo' => $runningBalance,
                    ];
                }
            }
            
            // INCASSI
            if ($this->statementType != 'invoices') {
                $paymentQuery = DB::table('invoice_payments as p')
                    ->join('invoices_sent as i', 'p.payable_id', '=', 'i.id')
                    ->where('p.payable_type', InvoiceSent::class)
                    ->where('i.id_entities', $this->entityId)
                    ->whereBetween('p.due_date', [$this->dateFrom, $this->dateTo]);
                
                if ($this->selectedOwnershipId) {
                    $paymentQuery->where('i.id_ownership', $this->selectedOwnershipId);
                }
                if ($this->type_invoice) {
                    $paymentQuery->where('i.type_invoice', $this->type_invoice);
                }
                if ($this->search) {
                    $paymentQuery->where('i.n_invoice', 'like', '%' . $this->search . '%');
                }
                
                $payments = $paymentQuery->select(
                        'p.due_date as date',
                        'p.amount',
                        'p.payment_method',
                        'i.n_invoice',
                        'i.id_ownership'
                    )
                    ->orderBy('p.due_date', 'asc')
                    ->get();
                
                foreach ($payments as $payment) {
                    $runningBalance -= $payment->amount;
                    $method = $payment->payment_method ?? 'Bonifico';
                    $methodLabel = $this->getPaymentMethodLabel($method);
                    
                    $proprieta = '-';
                    $ownership = DB::table('ownership')->where('id_proprieta', $payment->id_ownership)->first();
                    if ($ownership) {
                        $proprieta = $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? '-';
                    }
                    
                    $transactions[] = [
                        'proprieta' => $proprieta,
                        'descrizione' => 'Incasso ' . $methodLabel,
                        'data' => $payment->date,
                        'n_fattura' => $payment->n_invoice,
                        'dare' => 0,
                        'avere' => $payment->amount,
                        'saldo' => $runningBalance,
                    ];
                }
            }
        }
        
        // Ordina per data
        usort($transactions, function($a, $b) {
            return strtotime($a['data']) - strtotime($b['data']);
        });
        
        $this->transactions = $transactions;
        $this->calculateTotals();
    }
    
    private function getPaymentMethodLabel($method)
    {
        $methods = [
            'MP05' => 'Bonifico Bancario',
            'MP01' => 'Bonifico',
            'MP02' => 'Assegno',
            'MP03' => 'Contanti',
            'MP08' => 'Carta di Credito',
        ];
        
        return $methods[$method] ?? $method ?? 'Bonifico';
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