<?php
// app/Models/InvoicePayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;  
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class InvoicePayment extends Model
{
    use SoftDeletes;  

    protected $table = 'invoice_payments';
    
    protected $fillable = [
        'payable_id',
        'payable_type',
        'due_date',
        'amount',
        'residual_amount',  
        'paid_amount',      
        'payment_method',
        'iban',
        'paid_at',
        'status',
    ];

    public bool $skipAutoStatus = false;
    
    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'residual_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];
    
    protected $attributes = [
        'status' => 'issued',
        'residual_amount' => 0,
        'paid_amount' => 0,
    ];

    protected $dates = ['deleted_at'];
    
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function installmentTransactions(): HasMany
    {
        return $this->hasMany(InstallmentTransaction::class, 'id_invoice_payment', 'id');
    }
    
    /**
     * Verifica se è completamente pagata
     */
    public function isFullyPaid(): bool
    {
        return $this->status === 'paid' || $this->residual_amount <= 0;
    }
    
    /**
     * Verifica se è parzialmente pagata
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid' || ($this->paid_amount > 0 && $this->paid_amount < $this->amount);
    }
    
    /**
     * Verifica se il pagamento è stato chiuso da una nota di credito
     */
    public function isClosedByCreditNote(): bool
    {
        // Se lo stato è esplicitamente 'closed_credit_note'
        if ($this->status === 'closed_credit_note') {
            return true;
        }
        
        // Se è pagato, controlla se la fattura ha una NC che la chiude
        if ($this->status === 'paid' && $this->payable) {
            $payable = $this->payable;
            
            // Solo per InvoiceReceived
            if ($payable instanceof \App\Models\InvoiceReceived) {
                // Verifica se la fattura ha note di credito che la chiudono (nuova struttura)
                if (method_exists($payable, 'closingCreditNotes') && $payable->closingCreditNotes()->exists()) {
                    return true;
                }
                
                // Verifica se è una nota di credito che chiude fatture (nuova struttura)
                if (method_exists($payable, 'closedInvoices') && $payable->closedInvoices()->exists()) {
                    return true;
                }
                
                // Compatibilità con vecchia struttura (closes_invoice_id)
                if (method_exists($payable, 'closingCreditNote') && $payable->closingCreditNote()->exists()) {
                    return true;
                }
                
                if (method_exists($payable, 'closedInvoice') && $payable->closedInvoice()->exists()) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Accessor: Calcola il residuo dinamicamente
     * IMPORTANTE: Il residuo è sempre amount - paid_amount
     */
    public function getResidualAmountAttribute($value)
    {
        // Calcolo dinamico basato su amount e paid_amount
        $calculatedResidual = max(0, $this->amount - $this->paid_amount);
        
        // Log per debug (rimuovi in produzione)
        if (abs($calculatedResidual - $value) > 0.01 && $value > 0) {
            \Illuminate\Support\Facades\Log::warning('Residual mismatch', [
                'payment_id' => $this->id,
                'stored' => $value,
                'calculated' => $calculatedResidual,
                'amount' => $this->amount,
                'paid_amount' => $this->paid_amount
            ]);
        }
        
        return $calculatedResidual;
    }
    
    /**
     * Mutator: Assicura che residual_amount sia sempre amount - paid_amount
     */
    public function setResidualAmountAttribute($value)
    {
        // Ignora il valore passato, calcola da amount e paid_amount
        $this->attributes['residual_amount'] = max(0, $this->amount - $this->paid_amount);
    }
    
    /**
     * Mutator per amount: quando cambia amount, ricalcola residual
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = $value;
        if (isset($this->attributes['paid_amount'])) {
            $this->attributes['residual_amount'] = max(0, $value - $this->attributes['paid_amount']);
        }
    }
    
    /**
     * Mutator per paid_amount: quando cambia paid_amount, ricalcola residual e status
     */
    public function setPaidAmountAttribute($value)
    {
        $this->attributes['paid_amount'] = $value;
        
        if (isset($this->attributes['amount'])) {
            $newResidual = max(0, $this->attributes['amount'] - $value);
            $this->attributes['residual_amount'] = $newResidual;
            
            if ($this->skipAutoStatus) {
                return; // lo stato viene gestito esplicitamente da chi chiama (es. chiusura con NC)
            }
            
            if ($newResidual <= 0.01) {
                $this->attributes['status'] = 'paid';
                if (empty($this->attributes['paid_at'])) {
                    $this->attributes['paid_at'] = now();
                }
            } elseif ($value > 0) {
                $this->attributes['status'] = 'partially_paid';
                $this->attributes['paid_at'] = null;
            } else {
                $this->attributes['status'] = 'issued';
                $this->attributes['paid_at'] = null;
            }
        }
    }
    
    /**
     * Registra un pagamento (totale o parziale)
     */
    public function registerPayment(float $paidAmount): bool
    {
        $newPaidTotal = $this->paid_amount + $paidAmount;
        $newResidual = $this->amount - $newPaidTotal;
        
        DB::beginTransaction();
        
        try {
            $this->paid_amount = $newPaidTotal;
            // Il residual_amount verrà calcolato automaticamente dal mutator
            
            if ($newResidual <= 0) {
                $this->status = 'paid';
                $this->paid_at = now();
            } else {
                $this->status = 'partially_paid';
                $this->paid_at = null;
            }
            
            $this->save();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
    
    public function getStatusLabelAttribute(): string
    {
        if ($this->isPartiallyPaid() && $this->status !== 'paid') {
            return 'Parzialmente pagato (' . number_format($this->paid_amount, 2) . ' € su ' . number_format($this->amount, 2) . ' €)';
        }
        
        $invoiceStatuses = Config::get('gestionale.invoice_status', []);
        return $invoiceStatuses[$this->status]['label'] ?? $this->status;
    }
    
    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->payment_method === null) {
            return 'Non specificato';
        }
        return PaymentMethod::getLabel($this->payment_method);
    }
    
    public function getStatusBadgeClassAttribute(): string
    {
        $statuses = [
            'issued' => 'bg-blue-100 text-blue-800',
            'partially_paid' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'closed_credit_note' => 'bg-purple-100 text-purple-800',
        ];
        
        return $statuses[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
    
    /**
     * Override save per garantire che residual_amount sia sempre corretto
     */
    public function save(array $options = [])
    {
        $this->attributes['residual_amount'] = max(0, $this->amount - $this->paid_amount);
        
        if (!$this->skipAutoStatus) {
            if ($this->attributes['residual_amount'] <= 0.01) {
                $this->attributes['status'] = 'paid';
            } elseif ($this->paid_amount > 0) {
                $this->attributes['status'] = 'partially_paid';
            } else {
                $this->attributes['status'] = 'issued';
            }
        }
        
        return parent::save($options);
    }
}