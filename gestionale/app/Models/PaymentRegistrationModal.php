<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\InvoiceReceived;
use App\Models\InvoicePayment;
use App\Models\AccountingEntry;
use App\Models\InstallmentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PaymentRegistrationModal extends Component
{
    public $showModal = false;
    public $currentStep = 1;
    
    // STEP 1 - Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;
    
    // STEP 1 - Entità (Cliente/Fornitore)
    public string $entitySearch = '';
    public Collection $entityResults;
    public string $selectedEntityId = '';
    public string $selectedEntityName = '';
    public string $selectedEntityType = '';
    public bool $showEntityDropdown = false;
    
    // STEP 2
    public string $paymentDate = '';
    public string $paymentMethod = '';
    public ?string $bankAccountId = null;
    public array $bankAccounts = [];
    public array $availableInvoices = [];
    public string $notes = '';
    
    // STEP 3
    public float $totalAmount = 0;
    public float $totalSelectedAmount = 0;
    
    protected $rules = [
        'selectedOwnershipId' => 'required|exists:ownership,id_proprieta',
        'selectedEntityId' => 'required|exists:entities,id_cliente',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required|exists:payment_methods,code',
        'bankAccountId' => 'nullable|exists:bank_accounts,id',
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
    
    public function loadBankAccounts(): void
    {
        $this->bankAccounts = DB::table('bank_accounts as ba')
            ->leftJoin('ownership as o', 'ba.id_ownership', '=', 'o.id_proprieta')
            ->where('ba.valid', 1)
            ->select(
                'ba.id',
                'ba.name as bank_name',
                'ba.n_conto',
                'ba.iban',
                'ba.id_ownership',
                DB::raw("CONCAT_WS(' - ', 
                    COALESCE(o.RagAbbrev, o.Rag_Soc_intest, 'N/A'), 
                    ba.name, 
                    ba.n_conto
                ) as display_name")
            )
            ->orderBy('o.RagAbbrev')
            ->orderBy('ba.name')
            ->get()
            ->map(function($item) {
                return (array) $item;
            })
            ->toArray();
    }
    
    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
        $this->currentStep = 1;
    }
    
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }
    
    public function resetForm(): void
    {
        $this->currentStep = 1;
        
        // Reset proprietà
        $this->ownershipSearch = '';
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipResults = new Collection();
        $this->showOwnershipDropdown = false;
        
        // Reset entità
        $this->entitySearch = '';
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->selectedEntityType = '';
        $this->entityResults = new Collection();
        $this->showEntityDropdown = false;
        
        $this->paymentDate = date('Y-m-d');
        $this->paymentMethod = '';
        $this->bankAccountId = null;
        $this->availableInvoices = [];
        $this->notes = '';
        $this->totalAmount = 0;
        $this->totalSelectedAmount = 0;
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
            ->get(['id_proprieta as id', 'RagAbbrev as name', 'Rag_Soc_intest as ragione_sociale']);
        
        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }
    
    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->dispatch('ownershipSelected', name: $name);
    }
    
    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
        $this->dispatch('clearOwnershipInput');
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
                   'entity_type as type',
                   'partita_iva as piva']);
        
        $this->showEntityDropdown = $this->entityResults->isNotEmpty();
    }
    
    public function selectEntity(int $id, string $name, string $type): void
    {
        $this->selectedEntityId = (string)$id;
        $this->selectedEntityName = $name;
        $this->selectedEntityType = $type;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
        $this->loadInvoices(); // Carica le fatture subito
        $this->dispatch('entitySelected', name: $name);
    }
    
    public function clearEntity(): void
    {
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->selectedEntityType = '';
        $this->entitySearch = '';
        $this->showEntityDropdown = false;
        $this->availableInvoices = [];
        $this->dispatch('clearEntityInput');
    }
    
    public function goToStep($step): void
    {
        if ($step == 2) {
            $this->validate([
                'selectedOwnershipId' => 'required',
                'selectedEntityId' => 'required',
            ]);
            $this->loadInvoices();
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
    
    public function loadInvoices(): void
    {
        if (!$this->selectedEntityId) {
            $this->availableInvoices = [];
            return;
        }
        
        // Ottieni tutte le fatture del fornitore
        $invoices = InvoiceReceived::where('id_entities', $this->selectedEntityId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->with(['payments' => function($q) {
                // Ordina per data scadenza
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
                    // Recupera la proprietà dalla fattura
                    $ownershipName = $invoice->ownership->RagAbbrev ?? $invoice->ownership_name ?? '-';
                    
                    $this->availableInvoices[] = [
                        'id' => $payment->id,  // ID del pagamento, non della fattura
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->n_invoice,
                        'due_date' => $payment->due_date ? $payment->due_date->format('d/m/Y') : '-',
                        'total_amount' => $payment->amount,
                        'residual_amount' => $residual,
                        'selected' => false,
                        'selected_amount' => 0,
                        'ownership_name' => $ownershipName,  // Opzionale: per mostrare la proprietà
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
        $this->totalSelectedAmount = round($total, 2);
        $this->totalAmount = $this->totalSelectedAmount;
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
            
            // === 2. REGISTRA I PAGAMENTI SULLE SCADENZE ===
            foreach ($this->availableInvoices as $invoiceData) {
                if (!$invoiceData['selected'] || $invoiceData['selected_amount'] <= 0) {
                    continue;
                }
                
                // Trova il pagamento (scadenza) specifico
                $payment = InvoicePayment::find($invoiceData['id']);
                if (!$payment) continue;
                
                $paidAmount = $invoiceData['selected_amount'];
                $newPaidAmount = $payment->paid_amount + $paidAmount;
                $newResidual = $payment->amount - $newPaidAmount;
                
                // Aggiorna il pagamento
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
            $this->closeModal();
            $this->dispatch('refreshPayments');
            $this->dispatch('refreshAccountingEntries');
            $this->dispatch('refreshInvoices');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('PaymentRegistrationModal::register error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

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
        return view('livewire.admin.payment-registration-modal', [
            'paymentMethods' => $this->paymentMethods,
            'bankAccounts' => $this->bankAccounts,
        ]);
    }
}