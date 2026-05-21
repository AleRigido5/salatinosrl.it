<?php
// app/Livewire/Admin/RegisterPaymentModal.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoicePayment;
use App\Models\AccountingEntry;
use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class RegisterPaymentModal extends Component
{
    public $paymentId;
    public $payment;
    public $amount;
    public $paymentDate;
    public $paymentMethod;
    public $notes;
    public $isPartial = false;
    
    protected $rules = [
        'amount' => 'required|numeric|min:0.01',
        'paymentDate' => 'required|date',
        'paymentMethod' => 'required|string',
    ];
    
    public function mount($paymentId = null)
    {
        if ($paymentId) {
            $this->paymentId = $paymentId;
            $this->payment = InvoicePayment::find($paymentId);
            $this->amount = $this->payment->residual_amount;
            $this->paymentDate = now()->format('Y-m-d');
        }
    }
    
    public function register()
    {
        $this->validate();
        
        if ($this->amount > $this->payment->residual_amount) {
            $this->addError('amount', 'L\'importo non può superare il residuo da pagare (' . number_format($this->payment->residual_amount, 2) . ' €)');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Registra il pagamento
            $success = $this->payment->registerPayment($this->amount);
            
            if (!$success) {
                throw new \Exception('Errore durante la registrazione del pagamento');
            }
            
            // Crea la scrittura in prima nota
            $this->createAccountingEntry();
            
            DB::commit();
            
            $this->dispatch('paymentRegistered');
            $this->dispatch('closeModal');
            $this->dispatch('showSuccess', message: 'Pagamento registrato con successo!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    private function createAccountingEntry()
    {
        $invoice = $this->payment->payable;
        
        // Determina i conti da usare (esempio)
        $debitAccountId = $this->getAccountIdByCode('DEBITI_VERSO_FORNITORI');
        $creditAccountId = $this->getAccountIdByCode($this->paymentMethod === 'MP05' ? 'CASSA' : 'BANCA_C_C');
        
        AccountingEntry::create([
            'entry_date' => $this->paymentDate,
            'description' => 'Pagamento fattura ' . ($invoice->n_invoice ?? '') . ' - ' . ($invoice->supplier_name ?? ''),
            'type' => 'uscita',
            'id_payments_methods' => $this->getPaymentMethodId($this->paymentMethod),
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'amount' => $this->amount,
            'invoice_id' => $invoice->id ?? null,
            'invoice_payment_id' => $this->payment->id,
        ]);
    }
    
    private function getAccountIdByCode($code)
    {
        // Implementa la logica per ottenere l'ID del conto dal codice
        return Account::where('code', $code)->value('id') ?? 1;
    }
    
    private function getPaymentMethodId($code)
    {
        return PaymentMethod::where('code', $code)->value('id');
    }
    
    public function render()
    {
        return view('livewire.admin.register-payment-modal');
    }
}