<?php
// app/Livewire/Admin/RegisterPayment.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\PaymentMethod;
use App\Models\InvoiceReceived;
use App\Models\AccountingEntry;        
use App\Models\InstallmentTransaction;
use App\Models\BankAccount;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RegisterPayment extends Component
{
    public $showModal = false;
    public $currentStep = 1;
    
    // STEP 1 - Proprietà (Autocomplete)
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;
    
    // STEP 1 - Entità (Autocomplete)
    public string $entitySearch = '';
    public Collection $entityResults;
    public string $selectedEntityId = '';
    public string $selectedEntityName = '';
    public string $selectedEntityType = '';
    public bool $showEntityDropdown = false;
    
    // STEP 2
    public $paymentDate = '';
    public $paymentMethod = '';
    public $availableInvoices = [];
    public $notes = '';
    public $bankAccountId = '';
    public $bankAccounts = [];

    
    // STEP 3
    public $totalAmount = 0;
    public $totalSelectedAmount = 0;
    
    protected $rules = [
        'selectedOwnershipId' => 'required|exists:ownership,id_proprieta',
        'selectedEntityId' => 'required|exists:entities,id_cliente',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required|exists:payment_methods,code',
    ];
    
    protected $messages = [
        'selectedOwnershipId.required' => 'Selezionare una proprietà',
        'selectedEntityId.required' => 'Selezionare un cliente o fornitore',
        'paymentDate.required' => 'La data del pagamento è obbligatoria',
        'paymentMethod.required' => 'Il metodo di pagamento è obbligatorio',
    ];
    
    public function mount(): void
    {
        $this->paymentDate = date('Y-m-d');
        $this->ownershipResults = new Collection();
        $this->entityResults = new Collection();
        $this->loadBankAccounts(); 
    }
    
    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
        $this->currentStep = 1;
    }

    public function loadBankAccounts(): void
    {
        $this->bankAccounts = \App\Models\BankAccount::where('valid', 1)->get();
    }
    
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function resetForm(): void
    {
        $this->currentStep = 1;
        $this->ownershipSearch = '';
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->entitySearch = '';
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->selectedEntityType = '';
        $this->paymentDate = date('Y-m-d');
        $this->paymentMethod = '';
        $this->bankAccountId = ''; 
        $this->availableInvoices = [];
        $this->notes = '';
        $this->totalAmount = 0;
        $this->totalSelectedAmount = 0;
        $this->ownershipResults = new Collection();
        $this->entityResults = new Collection();
        $this->showOwnershipDropdown = false;
        $this->showEntityDropdown = false;
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
    
    public function selectOwnership($id, $name): void
    {
        $this->selectedOwnershipId = $id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
    }
    
    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
    }
    
    // ==================== AUTOCOMPLETE ENTITÀ ====================
    public function updatedEntitySearch(): void
    {
        if ($this->selectedEntityId && $this->entitySearch === $this->selectedEntityName) {
            $this->showEntityDropdown = false;
            return;
        }

        if ($this->selectedEntityId) {
            $this->selectedEntityId = '';
            $this->selectedEntityName = '';
            $this->selectedEntityType = '';
        }

        if (strlen($this->entitySearch) < 2) {
            $this->entityResults = new Collection();
            $this->showEntityDropdown = false;
            return;
        }

        $this->entityResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])  // Solo fornitori e entrambi
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('partita_iva', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 
                DB::raw("CASE 
                    WHEN ragione_sociale IS NOT NULL AND ragione_sociale != '' THEN ragione_sociale 
                    ELSE CONCAT(nome, ' ', cognome) 
                END as name"), 
                'entity_type as type']);
        
        $this->showEntityDropdown = $this->entityResults->isNotEmpty();
    }
    
    public function selectEntity($id, $name, $type): void
    {
        $this->selectedEntityId = $id;
        $this->selectedEntityName = $name;
        $this->selectedEntityType = $type;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
        
        // Carica le fatture aperte
        $this->loadAvailableInvoices();
    }
    
    public function clearEntity(): void
    {
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->selectedEntityType = '';
        $this->entitySearch = '';
        $this->showEntityDropdown = false;
        $this->availableInvoices = [];
    }
    
    public function goToStep($step): void
    {
        if ($step == 2) {
            $this->validate([
                'selectedOwnershipId' => 'required',
                'selectedEntityId' => 'required',
            ]);
            $this->loadAvailableInvoices();
        }
        
        if ($step == 3) {
            $this->validate([
                'paymentDate' => 'required|date',
                'paymentMethod' => 'required',
            ]);
            $this->calculateTotal();
        }
        
        $this->currentStep = $step;
    }
    
    public function loadAvailableInvoices(): void
    {
        if (!$this->selectedEntityId) {
            $this->availableInvoices = [];
            return;
        }
        
        // Recupera tutte le fatture del fornitore con i relativi pagamenti (scadenze)
        $invoices = InvoiceReceived::where('id_entities', $this->selectedEntityId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->with(['payments' => function($q) {
                // Ordina i pagamenti per data scadenza
                $q->orderBy('due_date', 'asc');
            }])
            ->orderBy('data_invoice', 'asc')
            ->get();
        
        $this->availableInvoices = [];
        
        foreach ($invoices as $invoice) {
            // Per ogni pagamento (scadenza) della fattura
            foreach ($invoice->payments as $payment) {
                // Calcola il residuo per questa specifica scadenza
                $residual = $payment->residual_amount;
                
                // Mostra solo se ha un residuo positivo
                if ($residual > 0.01) {
                    $this->availableInvoices[] = [
                        'id' => $payment->id,  // ID del pagamento, non della fattura
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->n_invoice,
                        'due_date' => $payment->due_date ? $payment->due_date->format('d/m/Y') : $invoice->data_invoice->format('d/m/Y'),
                        'total_amount' => $payment->amount,
                        'residual_amount' => $residual,
                        'selected' => false,
                        'selected_amount' => 0,
                    ];
                }
            }
        }
    }
        
    public function toggleInvoice($index): void
    {
        if (!isset($this->availableInvoices[$index])) return;
        
        if ($this->availableInvoices[$index]['selected']) {
            $this->availableInvoices[$index]['selected'] = false;
            $this->availableInvoices[$index]['selected_amount'] = 0;
        } else {
            $this->availableInvoices[$index]['selected'] = true;
            $this->availableInvoices[$index]['selected_amount'] = $this->availableInvoices[$index]['residual_amount'];
        }
        $this->calculateTotal();
    }
    
    public function updateSelectedAmount($index, $amount): void
    {
        if (!isset($this->availableInvoices[$index])) return;
        
        $amount = floatval(str_replace(',', '.', $amount));
        $maxAmount = $this->availableInvoices[$index]['residual_amount'];
        
        if ($amount > $maxAmount) {
            $amount = $maxAmount;
        }
        if ($amount < 0) {
            $amount = 0;
        }
        
        $this->availableInvoices[$index]['selected_amount'] = round($amount, 2);
        $this->availableInvoices[$index]['selected'] = $amount > 0;
        $this->calculateTotal();
    }
    
    public function calculateTotal(): void
    {
        $total = 0;
        foreach ($this->availableInvoices as $invoice) {
            if ($invoice['selected']) {
                $total += $invoice['selected_amount'];
            }
        }
        $this->totalSelectedAmount = $total;
        $this->totalAmount = $total;
    }
    
    public function register(): void
    {
        $this->validate();
        
        if ($this->totalSelectedAmount <= 0) {
            $this->dispatch('showError', message: 'Selezionare almeno un importo da pagare');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $adminId = Auth::guard('admin')->id();
            
            // === 1. REGISTRAZIONE IN PRIMA NOTA ===
            $accountingEntry = AccountingEntry::create([
                'entry_date' => $this->paymentDate,
                'description' => 'Pagamento fatture ' . $this->selectedEntityName . ($this->notes ? ' - ' . $this->notes : ''),
                'type' => 'uscita',
                'id_payments_methods' => $this->getPaymentMethodId(),
                'bank_account_id' => $this->bankAccountId ?: null,
                'invoice_id' => null,
                'invoice_payment_id' => null,
                'amount' => $this->totalSelectedAmount,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
            
            // === 2. REGISTRA I PAGAMENTI SULLE SINGOLE SCADENZE ===
            foreach ($this->availableInvoices as $invoiceData) {
                if (!$invoiceData['selected'] || $invoiceData['selected_amount'] <= 0) {
                    continue;
                }
                
                // Trova il pagamento specifico (scadenza)
                $payment = InvoicePayment::find($invoiceData['id']);
                if (!$payment) continue;
                
                $paidAmount = $invoiceData['selected_amount'];
                $newPaidAmount = $payment->paid_amount + $paidAmount;
                $newResidual = $payment->amount - $newPaidAmount;
                
                // Aggiorna il pagamento (scadenza)
                $payment->update([
                    'paid_amount' => $newPaidAmount,
                    'residual_amount' => $newResidual,
                    'status' => $newResidual <= 0.01 ? 'paid' : 'partially_paid',
                    'paid_at' => $newResidual <= 0.01 ? now() : null,
                ]);
                
                // === 3. COLLEGA LA SCRITTURA CONTABILE AL PAGAMENTO ===
                InstallmentTransaction::create([
                    'id_accounting_entries' => $accountingEntry->id,
                    'id_invoice_payment' => $payment->id,
                    'allocated_amount' => $paidAmount,
                ]);
                
                // Aggiorna lo stato della fattura se necessario
                $invoice = $payment->payable;
                if ($invoice) {
                    $totalResidual = $invoice->payments()->sum('residual_amount');
                    if ($totalResidual <= 0.01) {
                        $invoice->update(['status' => 'paid']);
                    } elseif ($totalResidual < $invoice->importo_totale) {
                        $invoice->update(['status' => 'partially_paid']);
                    }
                }
            }
            
            DB::commit();
            
            $this->dispatch('showSuccess', message: 'Pagamento registrato con successo!');
            $this->dispatch('refreshPayments');
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // Metodo helper per ottenere l'ID del metodo di pagamento
    private function getPaymentMethodId(): ?int
    {
        $method = PaymentMethod::where('code', $this->paymentMethod)->first();
        return $method ? $method->id : null;
    }
    
    public function getPaymentMethodsProperty()
    {
        return PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function render()
    {
        return view('livewire.admin.register-payment', [
            'paymentMethods' => $this->paymentMethods,
            'bankAccounts' => $this->bankAccounts,
        ]);
    }
}