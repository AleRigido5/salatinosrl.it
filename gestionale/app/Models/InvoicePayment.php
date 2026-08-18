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
     * Calcola il residuo "grezzo" (amount - paid_amount - crediti_allocati)
     * Questo metodo ora considera anche le note di credito allocate
     */
    private function computeRawResidual(float $amount, float $paidAmount): float
    {
        // Ottieni l'importo totale delle note di credito allocate a questa fattura
        $creditAllocated = 0;
        
        if ($this->payable && $this->payable instanceof \App\Models\InvoiceReceived) {
            $creditAllocated = CreditNoteInvoiceRelation::where('invoice_id', $this->payable->id)
                ->sum('allocated_amount');
        }
        
        $diff = $amount - $paidAmount - $creditAllocated;
        return $amount < 0 ? min(0, $diff) : max(0, $diff);
    }

    /**
     * Vero quando il residuo è "esaurito"
     */
    private function isResidualSettled(float $residual): bool
    {
        return abs($residual) <= 0.01;
    }
    
    /**
     * Verifica se è completamente pagata
     */
    public function isFullyPaid(): bool
    {
        return $this->status === 'paid' || $this->isResidualSettled((float) $this->residual_amount);
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
        if ($this->status === 'closed_credit_note') {
            return true;
        }
        
        if ($this->status === 'paid' && $this->payable) {
            $payable = $this->payable;
            
            if ($payable instanceof \App\Models\InvoiceReceived) {
                if (method_exists($payable, 'closingCreditNotes') && $payable->closingCreditNotes()->exists()) {
                    return true;
                }
                
                if (method_exists($payable, 'closedInvoices') && $payable->closedInvoices()->exists()) {
                    return true;
                }
                
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
     * Accessor: Calcola il residuo dinamicamente considerando le NC
     */
    public function getResidualAmountAttribute($value)
    {
        $calculatedResidual = $this->computeRawResidual((float) $this->amount, (float) $this->paid_amount);
        
        // Log per debug
        if (abs($calculatedResidual - $value) > 0.01 && abs($value) > 0) {
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
     * Mutator: Assicura che residual_amount sia sempre coerente
     */
    public function setResidualAmountAttribute($value)
    {
        $this->attributes['residual_amount'] = $this->computeRawResidual(
            (float) $this->amount, 
            (float) $this->paid_amount
        );
    }
    
    /**
     * Mutator per amount
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = $value;
        if (isset($this->attributes['paid_amount'])) {
            $this->attributes['residual_amount'] = $this->computeRawResidual(
                (float) $value, 
                (float) $this->attributes['paid_amount']
            );
        }
    }
    
    /**
     * Mutator per paid_amount
     */
    public function setPaidAmountAttribute($value)
    {
        $this->attributes['paid_amount'] = $value;
        
        if (isset($this->attributes['amount'])) {
            $amount = (float) $this->attributes['amount'];
            $newResidual = $this->computeRawResidual($amount, (float) $value);
            $this->attributes['residual_amount'] = $newResidual;
            
            if ($this->skipAutoStatus) {
                return;
            }
            
            if ($this->isResidualSettled($newResidual)) {
                $this->attributes['status'] = 'paid';
                if (empty($this->attributes['paid_at'])) {
                    $this->attributes['paid_at'] = now();
                }
            } elseif ($value != 0) {
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
        $newResidual = $this->computeRawResidual((float) $this->amount, $newPaidTotal);
        
        DB::beginTransaction();
        
        try {
            $this->paid_amount = $newPaidTotal;
            
            if ($this->isResidualSettled($newResidual)) {
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
        $amount = (float) $this->amount;
        $paidAmount = (float) $this->paid_amount;
        $residual = $this->computeRawResidual($amount, $paidAmount);
        $this->attributes['residual_amount'] = $residual;
        
        if (!$this->skipAutoStatus) {
            if ($this->isResidualSettled($residual)) {
                $this->attributes['status'] = 'paid';
            } elseif ($paidAmount != 0) {
                $this->attributes['status'] = 'partially_paid';
            } else {
                $this->attributes['status'] = 'issued';
            }
        }
        
        return parent::save($options);
    }
}