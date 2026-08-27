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
use App\Models\CreditNoteInvoiceRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Livewire\Attributes\On;

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

    public string $invoiceType = 'acquisto';
    
    // STEP 2
    public $paymentDate = '';
    public $paymentMethod = '';
    public string $paymentStatus = 'COMPLETATO';
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
        'paymentStatus' => 'required|in:COMPLETATO,INSERITO,AUTOMATICO,DA INSERIRE',
    ];
    
    protected $messages = [
        'selectedOwnershipId.required' => 'Selezionare una proprietà',
        'selectedEntityId.required' => 'Selezionare un cliente o fornitore',
        'paymentDate.required' => 'La data del pagamento è obbligatoria',
        'paymentMethod.required' => 'Il metodo di pagamento è obbligatorio',
        'paymentStatus.required' => 'Lo stato del pagamento è obbligatorio',
    ];
    
    public function mount(string $invoiceType = 'acquisto'): void
    {
        $this->invoiceType = $invoiceType;
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
        $this->paymentStatus = 'COMPLETATO';
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

        // vendita = cliente/entrambi, acquisto = tutti e tre
        $entityTypes = $this->invoiceType === 'vendita'
            ? ['cliente', 'entrambi']
            : ['fornitore', 'cliente', 'entrambi'];

        $this->entityResults = Entity::where('valid', 1)
            ->whereIn('entity_type', $entityTypes)
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%')
                ->orWhere('partita_iva', 'like', '%' . $this->entitySearch . '%');
            })
            ->limit(10)
            ->get([
                'id_cliente as id',
                DB::raw("CASE 
                    WHEN ragione_sociale IS NOT NULL AND ragione_sociale != '' THEN ragione_sociale 
                    ELSE CONCAT(nome, ' ', cognome) 
                END as name"),
                'entity_type as type'
            ]);

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
                'paymentStatus' => 'required|in:COMPLETATO,INSERITO,AUTOMATICO,DA INSERIRE',
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

        if ($this->invoiceType === 'vendita') {
            $invoices = \App\Models\InvoiceSent::where('id_entities', $this->selectedEntityId)
                ->with(['payments' => fn($q) => $q->orderBy('due_date', 'asc')])
                ->orderBy('data_invoice', 'asc')
                ->get();
        } else {
            $invoices = InvoiceReceived::where('id_entities', $this->selectedEntityId)
                ->with(['payments' => fn($q) => $q->orderBy('due_date', 'asc')])
                ->orderBy('data_invoice', 'asc')
                ->get();
        }

        $this->availableInvoices = [];

        foreach ($invoices as $invoice) {
            // FIX: importo delle note di credito già associate a QUESTA
            // fattura (una sola query per fattura, riusata per tutte le sue
            // scadenze/rate). Solo per le fatture di acquisto (InvoiceReceived)
            // — le note di credito multiple via credit_note_invoice_relations
            // esistono solo su quel lato (vedi InvoicePaymentsTable::openCloseModal,
            // che accetta solo InvoiceReceived).
            $creditAllocated = $this->invoiceType === 'vendita'
                ? 0.0
                : (float) CreditNoteInvoiceRelation::where('invoice_id', $invoice->id)->sum('allocated_amount');

            foreach ($invoice->payments as $payment) {
                // FIX (bug segnalato): prima si leggeva $payment->residual_amount,
                // un accessor che dovrebbe già nettare le NC internamente ma si
                // affida a una relazione risolta "pigramente" (lazy) dentro un
                // ciclo — nella pratica, con NC appena associate, il valore
                // restituito risultava ancora LORDO (es. fattura SIAE: mostrava
                // 75,64 € invece di 72,10 € netti, con una NC da 3,54 € già
                // associata), portando a registrare un pagamento più alto del
                // dovuto (90,94 € invece di 83,86 €). Calcoliamo qui il residuo
                // in modo esplicito e diretto, usando amount/paid_amount
                // (colonne grezze, senza passare da alcun accessor) e
                // sottraendo le NC già note per questa fattura — niente più
                // ambiguità su cosa stia effettivamente nettando l'accessor.
                $amount = (float) $payment->amount;
                $paidAmount = (float) $payment->paid_amount;
                $rawResidual = $amount - $paidAmount - $creditAllocated;
                $residual = $amount < 0 ? min(0, $rawResidual) : max(0, $rawResidual);

                // FIX: prima si escludevano i residui negativi (`$residual > 0.01`),
                // quindi le fatture-credito (importo negativo, es. FIORINO GROUP
                // -27,08 €, ancora in stato 'issued' grazie al fix sul modello
                // InvoicePayment) non comparivano MAI in questo elenco.
                // Ora includiamo qualunque residuo diverso da zero in valore
                // assoluto: un residuo positivo è "da pagare", uno negativo è
                // "credito ancora da applicare" e va incluso allo stesso modo,
                // selezionabile, per poter decrementare il totale del pagamento.
                if (abs($residual) > 0.01) {
                    $this->availableInvoices[] = [
                        'id'             => $payment->id,
                        'invoice_id'     => $invoice->id,
                        'invoice_number' => $invoice->n_invoice,
                        'due_date'       => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') : '-',
                        'due_date_raw'   => $payment->due_date,
                        'total_amount'   => $payment->amount,
                        'residual_amount'=> $residual,
                        'credit_allocated' => $creditAllocated,
                        'is_credit'      => $residual < 0,
                        'selected'       => false,
                        'selected_amount'=> 0,
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
            // Per le fatture-credito residual_amount è negativo: selected_amount
            // eredita lo stesso segno, così calculateTotal() lo sottrae
            // automaticamente dal totale del pagamento.
            $this->availableInvoices[$index]['selected_amount'] = $this->availableInvoices[$index]['residual_amount'];
        }
        $this->calculateTotal();
    }
    
    public function updateSelectedAmount($index, $amount): void
    {
        if (!isset($this->availableInvoices[$index])) return;
        
        $amount = floatval(str_replace(',', '.', $amount));
        $maxAmount = (float) $this->availableInvoices[$index]['residual_amount'];
        
        // FIX: il vecchio clamp forzava sempre l'importo in [0, maxAmount],
        // il che rendeva impossibile inserire un importo negativo anche per
        // una riga di credito (maxAmount negativo). Ora il range di validità
        // dipende dal segno del residuo:
        // - riga normale (maxAmount >= 0): importo ammesso in [0, maxAmount]
        // - riga di credito (maxAmount < 0): importo ammesso in [maxAmount, 0]
        if ($maxAmount >= 0) {
            if ($amount > $maxAmount) {
                $amount = $maxAmount;
            }
            if ($amount < 0) {
                $amount = 0;
            }
        } else {
            if ($amount < $maxAmount) {
                $amount = $maxAmount;
            }
            if ($amount > 0) {
                $amount = 0;
            }
        }
        
        $this->availableInvoices[$index]['selected_amount'] = round($amount, 2);
        $this->availableInvoices[$index]['selected'] = abs($amount) > 0.001;
        $this->calculateTotal();
    }
    
    public function calculateTotal(): void
    {
        $total = 0;
        foreach ($this->availableInvoices as $invoice) {
            if ($invoice['selected']) {
                // Le righe di credito hanno selected_amount negativo, quindi
                // questa somma decrementa automaticamente il totale quando
                // vengono selezionate insieme a fatture normali.
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
            $this->dispatch('showError', message: 'Selezionare almeno un importo da pagare (il totale deve essere maggiore di zero)');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $adminId = Auth::guard('admin')->id();
            
            // === 1. REGISTRAZIONE IN PRIMA NOTA ===
            $accountingEntry = AccountingEntry::create([
                'entry_date'          => $this->paymentDate,
                'description'         => 'Pagamento fatture ' . $this->selectedEntityName . ($this->notes ? ' - ' . $this->notes : ''),
                'type'                => $this->invoiceType === 'vendita' ? 'entrata' : 'uscita',
                'id_payments_methods' => $this->getPaymentMethodId(),
                'bank_account_id'     => $this->bankAccountId ?: null,
                'invoice_id'          => null,
                'invoice_payment_id'  => null,
                'amount'              => $this->totalSelectedAmount,
                'status'              => $this->paymentStatus,
                'created_by'          => $adminId,
                'updated_by'          => $adminId,
            ]);
            
            // === 2. REGISTRA I PAGAMENTI SULLE SINGOLE SCADENZE ===
            foreach ($this->availableInvoices as $invoiceData) {
                if (!$invoiceData['selected'] || abs($invoiceData['selected_amount']) <= 0.001) {
                    continue;
                }
                
                // Trova il pagamento specifico (scadenza)
                $payment = InvoicePayment::find($invoiceData['id']);
                if (!$payment) continue;
                
                $paidAmount = $invoiceData['selected_amount'];
                $oldPaidAmount = $payment->paid_amount;
                
                Log::info('Aggiornamento pagamento', [
                    'payment_id' => $payment->id,
                    'old_paid' => $oldPaidAmount,
                    'delta_paid' => $paidAmount,
                    'amount' => $payment->amount,
                    'credit_allocated' => $invoiceData['credit_allocated'] ?? 0,
                ]);
                
                // FIX: non ricalcoliamo più residuo/stato a mano con
                // max(0, ...) — quel calcolo tronca a zero i residui negativi
                // (crediti), marcando come 'paid' anche crediti applicati solo
                // parzialmente. Impostiamo solo paid_amount e lasciamo che i
                // mutator del modello InvoicePayment (già corretti, gestiscono
                // il segno di amount/residuo e la detrazione delle NC) calcolino
                // residual_amount e status in modo coerente con il resto del
                // sistema.
                $payment->paid_amount = $oldPaidAmount + $paidAmount;
                $payment->save();
                
                // === 3. COLLEGA LA SCRITTURA CONTABILE AL PAGAMENTO ===
                InstallmentTransaction::create([
                    'id_accounting_entries' => $accountingEntry->id,
                    'id_invoice_payment' => $payment->id,
                    'allocated_amount' => $paidAmount,
                ]);
                
                // Aggiorna lo stato della fattura in base a TUTTI i suoi pagamenti
                $invoice = $payment->payable;
                if ($invoice) {
                    $totalResidual = (float) $invoice->payments()->sum('residual_amount');
                    $importoTotale = (float) $invoice->importo_totale;

                    // FIX: stesso ragionamento sign-aware. Per una fattura con
                    // importo negativo, sia totalResidual che importoTotale
                    // sono negativi: confrontarli con `<= 0.01` "assoluto"
                    // marcherebbe come 'paid' anche prima di qualunque
                    // applicazione del credito. Usiamo abs() per entrambi.
                    if (abs($totalResidual) <= 0.01) {
                        $newInvoiceStatus = 'paid';
                    } elseif ($importoTotale != 0 && abs($totalResidual) < abs($importoTotale)) {
                        $newInvoiceStatus = 'partially_paid';
                    } else {
                        $newInvoiceStatus = 'issued';
                    }

                    $invoice->update(['status' => $newInvoiceStatus]);
                }
            }
            
            DB::commit();
            
            $this->dispatch('showSuccess', message: 'Pagamento registrato con successo!');
            $this->dispatch('refreshPayments');
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore registrazione pagamento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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

    #[On('refreshAvailableInvoices')]
    public function refreshAvailableInvoices(): void
    {
        if ($this->selectedEntityId) {
            $this->loadAvailableInvoices();
            $this->calculateTotal();
        }
    }
    
    public function render()
    {
        return view('livewire.admin.register-payment', [
            'paymentMethods' => $this->paymentMethods,
            'bankAccounts' => $this->bankAccounts,
        ]);
    }
}