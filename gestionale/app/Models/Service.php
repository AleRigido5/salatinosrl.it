<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $keyType = 'int';

    // ATTIVA i timestamp (importante!)
    public $timestamps = true;

    protected $fillable = [
        'id_categories',
        'settings_category_id',
        'Titolo',
        'Descrizione',
        'Descr_fattura',
        'Stato',
        'Prezzo_un',
        'UnitaMisura_id_unita',
        'id_vat_rate',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'Stato' => 'boolean',
        'Prezzo_un' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELAZIONI PER IL TRACCIAMENTO ====================
    
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // Relazione con la categoria (tabella settings)
    public function category()
    {
        return $this->belongsTo(Setting::class, 'id_categories', 'id')
            ->where('tabella_riferimento', 'services');
    }

    // Relazione con l'unità di misura
    public function unitaMisura()
    {
        return $this->belongsTo(UnitaMisura::class, 'UnitaMisura_id_unita', 'id_um');
    }

    // Relazione con VAT Rate
    public function vatRate()
    {
        return $this->belongsTo(VatRate::class, 'id_vat_rate');
    }
    
    // ==================== HELPER PER IL TRACCIAMENTO ====================
    
    public function getTrackingInfoAttribute()
    {
        $info = [];
        
        if ($this->created_at && $this->createdBy) {
            $info['created'] = [
                'by' => $this->createdBy->name,
                'by_id' => $this->createdBy->id,
                'by_email' => $this->createdBy->email,
                'at' => $this->created_at->format('d/m/Y H:i:s'),
                'full' => "{$this->createdBy->name} - {$this->created_at->format('d/m/Y H:i:s')}"
            ];
        } elseif ($this->created_at) {
            $info['created'] = [
                'by' => 'Sistema',
                'by_id' => null,
                'by_email' => null,
                'at' => $this->created_at->format('d/m/Y H:i:s'),
                'full' => "Sistema - {$this->created_at->format('d/m/Y H:i:s')}"
            ];
        }
        
        if ($this->updated_at && $this->updatedBy && $this->created_at != $this->updated_at) {
            $info['updated'] = [
                'by' => $this->updatedBy->name,
                'by_id' => $this->updatedBy->id,
                'by_email' => $this->updatedBy->email,
                'at' => $this->updated_at->format('d/m/Y H:i:s'),
                'full' => "{$this->updatedBy->name} - {$this->updated_at->format('d/m/Y H:i:s')}",
                'relative' => $this->updated_at->diffForHumans()
            ];
        } elseif ($this->updated_at && $this->created_at != $this->updated_at) {
            $info['updated'] = [
                'by' => 'Sistema',
                'by_id' => null,
                'by_email' => null,
                'at' => $this->updated_at->format('d/m/Y H:i:s'),
                'full' => "Sistema - {$this->updated_at->format('d/m/Y H:i:s')}",
                'relative' => $this->updated_at->diffForHumans()
            ];
        }
        
        return $info;
    }
    
    public function getTrackingHtmlAttribute()
    {
        $html = '<div class="text-xs text-gray-500 space-y-1">';
        
        if ($this->created_at && $this->createdBy) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Inserito:</span>';
            $html .= '<span class="text-gray-700">' . e($this->createdBy->name) . '</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->created_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        } elseif ($this->created_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Inserito:</span>';
            $html .= '<span class="text-gray-700">Sistema</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->created_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        }
        
        if ($this->updated_at && $this->updatedBy && $this->created_at != $this->updated_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Modificato:</span>';
            $html .= '<span class="text-gray-700">' . e($this->updatedBy->name) . '</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->updated_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '<span class="text-gray-400 ml-1">(' . $this->updated_at->diffForHumans() . ')</span>';
            $html .= '</div>';
        } elseif ($this->updated_at && $this->created_at != $this->updated_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Modificato:</span>';
            $html .= '<span class="text-gray-700">Sistema</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->updated_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    // Scope per servizi attivi
    public function scopeActive($query)
    {
        return $query->where('Stato', 1);
    }

    // Accessor per il nome della categoria
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->valore : 'Senza categoria';
    }

    // Accessor per lo stato formattato
    public function getStatusLabelAttribute()
    {
        return $this->Stato ? 'Attivo' : 'Disattivo';
    }

    // Accessor per lo stato badge
    public function getStatusBadgeAttribute()
    {
        return $this->Stato 
            ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attivo</span>'
            : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Disattivo</span>';
    }
}