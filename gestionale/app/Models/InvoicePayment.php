<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
     * Ottiene l'importo residuo da pagare
     */
    public function getResidualAmountAttribute($value)
    {
        // Se residual_amount è 0, significa che deve essere uguale all'importo totale
        if ($value == 0 && $this->paid_amount == 0) {
            return $this->amount;
        }
        return $value > 0 ? $value : 0;
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
            $this->residual_amount = max(0, $newResidual);
            
            if ($newResidual <= 0) {
                // Pagato completamente
                $this->status = 'paid';
                $this->paid_at = now();
            } else {
                // Pagato parzialmente
                $this->status = 'partially_paid';
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
        // Se è parzialmente pagato, mostra un label specifico
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
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];
        
        return $statuses[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}