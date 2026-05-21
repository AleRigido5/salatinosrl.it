<?php
// app/Livewire/Admin/RegisterPayment.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
use App\Models\AccountingEntry;
use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RegisterPayment extends Component
{
    // Step 1: Selezione controparte
    public $entitySearch = '';
    public $entityResults = [];
    public $showEntityDropdown = false;
    public $selectedEntityId = null;
    public $selectedEntityName = '';
    public $selectedEntityType = '';
    
    // Step 2: Dati pagamento
    public $paymentDate = '';
    public $paymentMethod = '';
    public $totalAmount = 0;
    public $notes = '';
    
    // Step 3: Selezione fatture
    public $availableInvoices = [];
    public $totalSelectedAmount = 0;
    
    // Step 4: Spese aggiuntive
    public $bankFees = 0;
    
    // Modale
    public $showModal = false;
    public $currentStep = 1;
    
    protected $rules = [
        'selectedEntityId' => 'required',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required',
    ];
    
    protected $messages = [
        'selectedEntityId.required' => 'Selezionare un cliente o fornitore',
        'paymentDate.required' => 'La data del pagamento è obbligatoria',
        'paymentMethod.required' => 'Il metodo di pagamento è obbligatorio',
    ];
    
    public function mount()
    {
        $this->paymentDate = Carbon::now()->format('Y-m-d');
    }
    
    // ==================== STEP 1: RICERCA ENTITÀ ====================
    
    public function updatedEntitySearch()
    {
        if (strlen($this->entitySearch) < 2) {
            $this->entityResults = [];
            $this->showEntityDropdown = false;
            return;
        }
        
        $this->entityResults = Entity::where('valid', 1)
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get()
            ->map(function($entity) {
                return [
                    'id' => $entity->id_cliente,
                    'name' => $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome),
                    'type' => $entity->entity_type,
                    'piva' => $entity->partita_iva,
                ];
            })
            ->toArray();
        
        $this->showEntityDropdown = !empty($this->entityResults);
    }
    
    public function selectEntity($id, $name, $type)
    {
        $this->selectedEntityId = $id;
        $this->selectedEntityName = $name;
        $this->selectedEntityType = $type;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
        
        // Carica le fatture aperte per questa entità
        $this->loadAvailableInvoices();
        
        // Passa allo step 2
        $this->currentStep = 2;
        
        // Notifica l'avanzamento
        $this->dispatch('stepChanged', step: 2);
    }
    
    // ==================== STEP 2: CARICA FATTURE APERTE ====================
    
    public function loadAvailableInvoices()
    {
        if (!$this->selectedEntityId) {
            $this->availableInvoices = [];
            return;
        }
        
        // Trova tutti i pagamenti (scadenze) non ancora saldati per questa entità
        $openPayments = InvoicePayment::whereHas('payable', function($q) {
                $q->where('id_entities', $this->selectedEntityId);
            })
            ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
            ->with('payable')
            ->get();
        
        $this->availableInvoices = [];
        foreach ($openPayments as $payment) {
            $invoice = $payment->payable;
            if ($invoice) {
                $residual = $payment->residual_amount > 0 ? $payment->residual_amount : $payment->amount;
                $this->availableInvoices[] = [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->n_invoice,
                    'invoice_date' => $invoice->data_invoice ? $invoice->data_invoice->format('d/m/Y') : '-',
                    'due_date' => $payment->due_date ? $payment->due_date->format('d/m/Y') : '-',
                    'total_amount' => (float) $payment->amount,
                    'residual_amount' => (float) $residual,
                    'paid_amount' => (float) $payment->paid_amount,
                    'status' => $payment->status,
                    'selected' => false,
                    'selected_amount' => 0,
                ];
            }
        }
        
        $this->calculateTotalSelected();
    }
    
    // ==================== STEP 3: SELEZIONE FATTURE ====================
    
    public function toggleInvoice($index)
    {
        if (!isset($this->availableInvoices[$index])) return;
        
        if (!$this->availableInvoices[$index]['selected']) {
            // Seleziona l'intero importo residuo
            $this->availableInvoices[$index]['selected'] = true;
            $this->availableInvoices[$index]['selected_amount'] = $this->availableInvoices[$index]['residual_amount'];
        } else {
            // Deseleziona
            $this->availableInvoices[$index]['selected'] = false;
            $this->availableInvoices[$index]['selected_amount'] = 0;
        }
        
        $this->calculateTotalSelected();
    }
    
    public function updateSelectedAmount($index, $value)
    {
        if (!isset($this->availableInvoices[$index])) return;
        
        $amount = floatval(str_replace(',', '.', $value));
        $residual = $this->availableInvoices[$index]['residual_amount'];
        
        if ($amount > $residual) {
            $amount = $residual;
        }
        
        if ($amount > 0) {
            $this->availableInvoices[$index]['selected'] = true;
            $this->availableInvoices[$index]['selected_amount'] = $amount;
        } else {
            $this->availableInvoices[$index]['selected'] = false;
            $this->availableInvoices[$index]['selected_amount'] = 0;
        }
        
        $this->calculateTotalSelected();
    }
    
    public function calculateTotalSelected()
    {
        $this->totalSelectedAmount = 0;
        
        foreach ($this->availableInvoices as $invoice) {
            if ($invoice['selected'] && $invoice['selected_amount'] > 0) {
                $this->totalSelectedAmount += $invoice['selected_amount'];
            }
        }
        
        $this->totalAmount = $this->totalSelectedAmount + $this->bankFees;
    }
    
    // ==================== SPESE AGGIUNTIVE ====================
    
    public function updatedBankFees()
    {
        $this->calculateTotalSelected();
    }
    
    // ==================== REGISTRA IL PAGAMENTO ====================
    
    public function register()
    {
        $this->validate();
        
        // Verifica che ci siano fatture selezionate
        $selectedInvoices = [];
        foreach ($this->availableInvoices as $invoice) {
            if ($invoice['selected'] && $invoice['selected_amount'] > 0) {
                $selectedInvoices[] = [
                    'payment_id' => $invoice['payment_id'],
                    'amount' => $invoice['selected_amount'],
                ];
            }
        }
        
        if (empty($selectedInvoices)) {
            $this->dispatch('showError', message: 'Selezionare almeno una fattura da pagare');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Registra i pagamenti sulle fatture
            foreach ($selectedInvoices as $selected) {
                $payment = InvoicePayment::find($selected['payment_id']);
                if ($payment) {
                    $payment->registerPayment($selected['amount']);
                }
            }
            
            // Registra in prima nota
            $this->createAccountingEntry($selectedInvoices);
            
            DB::commit();
            
            $this->resetForm();
            $this->dispatch('paymentRegistered');
            $this->dispatch('showSuccess', message: 'Pagamento registrato con successo!');
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    private function createAccountingEntry($selectedInvoices)
    {
        // Determina i conti in base al tipo (fornitore = uscita, cliente = entrata)
        $isSupplier = $this->selectedEntityType === 'fornitore';
        
        if ($isSupplier) {
            // Pagamento a fornitore (USCITA)
            $debitAccountId = $this->getAccountIdByCode('DEBITI_VERSO_FORNITORI');
            $creditAccountId = $this->getAccountIdByCode($this->paymentMethod === 'MP05' ? 'CASSA' : 'BANCA_C_C');
            $entryType = 'uscita';
        } else {
            // Pagamento da cliente (ENTRATA)
            $debitAccountId = $this->getAccountIdByCode($this->paymentMethod === 'MP05' ? 'CASSA' : 'BANCA_C_C');
            $creditAccountId = $this->getAccountIdByCode('CREDITI_VERSO_CLIENTI');
            $entryType = 'entrata';
        }
        
        // Crea la scrittura principale
        $description = ($isSupplier ? 'Pagamento a ' : 'Incasso da ') . $this->selectedEntityName;
        if (count($selectedInvoices) > 1) {
            $description .= ' per ' . count($selectedInvoices) . ' fatture';
        }
        
        AccountingEntry::create([
            'entry_date' => $this->paymentDate,
            'description' => $description . ($this->notes ? ' - ' . $this->notes : ''),
            'type' => $entryType,
            'id_payments_methods' => $this->getPaymentMethodId($this->paymentMethod),
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'amount' => $this->totalAmount,
            'created_by' => Auth::guard('admin')->id(),
        ]);
        
        // Se ci sono spese bancarie, registrale separatamente
        if ($this->bankFees > 0) {
            AccountingEntry::create([
                'entry_date' => $this->paymentDate,
                'description' => 'Spese bancarie per ' . ($isSupplier ? 'pagamento' : 'incasso') . ' fatture',
                'type' => 'uscita',
                'id_payments_methods' => $this->getPaymentMethodId($this->paymentMethod),
                'debit_account_id' => $this->getAccountIdByCode('SPESE_BANCARIE'),
                'credit_account_id' => $creditAccountId,
                'amount' => $this->bankFees,
                'created_by' => Auth::guard('admin')->id(),
            ]);
        }
    }
    
    private function getAccountIdByCode($code)
    {
        $account = Account::where('code', $code)->first();
        return $account ? $account->id : null;
    }
    
    private function getPaymentMethodId($code)
    {
        $method = PaymentMethod::where('code', $code)->first();
        return $method ? $method->id : null;
    }
    
    public function resetForm()
    {
        $this->entitySearch = '';
        $this->selectedEntityId = null;
        $this->selectedEntityName = '';
        $this->selectedEntityType = '';
        $this->paymentDate = Carbon::now()->format('Y-m-d');
        $this->paymentMethod = '';
        $this->totalAmount = 0;
        $this->notes = '';
        $this->availableInvoices = [];
        $this->totalSelectedAmount = 0;
        $this->bankFees = 0;
        $this->currentStep = 1;
    }
    
    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function goToStep($step)
    {
        if ($step == 1) {
            $this->currentStep = 1;
        } elseif ($step == 2 && $this->selectedEntityId) {
            $this->currentStep = 2;
        } elseif ($step == 3 && $this->totalSelectedAmount > 0) {
            $this->currentStep = 3;
        }
    }
    
    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->get();
    }
    
    public function render()
    {
        return view('livewire.admin.register-payment', [
            'paymentMethods' => $this->paymentMethods,
        ]);
    }
}