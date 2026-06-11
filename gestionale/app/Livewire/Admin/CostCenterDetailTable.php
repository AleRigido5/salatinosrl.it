<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\CostCenter;
use App\Models\InvoiceRow;
use App\Models\Activity;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostCenterDetailTable extends Component
{
    public $costCenter;
    public $costCenterId;
    public $dateFrom = '';
    public $dateTo = '';
    
    // Tab attiva (invoices o activities)
    public $activeTab = 'invoices';
    
    // Filtri attività
    public $activitySearch = '';
    public $activityServiceFilter = '';
    public $activityStaffFilter = '';
    
    // Ordinamento fatture
    public $invoiceSortField = 'invoice_date';
    public $invoiceSortDirection = 'desc';
    
    // Ordinamento attività
    public $activitySortField = 'data_activities';
    public $activitySortDirection = 'desc';
    
    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];
    
    public function mount($costCenterId)
    {
        $this->costCenterId = $costCenterId;
        $this->costCenter = CostCenter::with(['ownership', 'entity'])->findOrFail($costCenterId);
        
        // Default: ultimi 3 mesi
        $this->dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }
    
    public function updateDateRange($data)
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    // ==================== ORDINAMENTO FATTURE ====================
    
    public function sortInvoicesBy($field)
    {
        if ($this->invoiceSortField === $field) {
            $this->invoiceSortDirection = $this->invoiceSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->invoiceSortField = $field;
            $this->invoiceSortDirection = 'asc';
        }
    }
    
    // ==================== ORDINAMENTO ATTIVITÀ ====================
    
    public function sortActivitiesBy($field)
    {
        if ($this->activitySortField === $field) {
            $this->activitySortDirection = $this->activitySortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->activitySortField = $field;
            $this->activitySortDirection = 'asc';
        }
    }
    
    // ==================== FILTRI ATTIVITÀ ====================
    
    public function updatedActivitySearch()
    {
        // Reset paginazione se usi paginazione
    }
    
    public function updatedActivityServiceFilter()
    {
        // Reset paginazione se usi paginazione
    }
    
    public function updatedActivityStaffFilter()
    {
        // Reset paginazione se usi paginazione
    }
    
    public function clearActivityFilters()
    {
        $this->activitySearch = '';
        $this->activityServiceFilter = '';
        $this->activityStaffFilter = '';
    }
    
    // ==================== PROPRIETÀ ====================
    
    /**
     * Fatture associate al centro di costo nel periodo selezionato
     * Raggruppa per documento
     */
    public function getInvoicesProperty()
    {
        $query = InvoiceRow::with([
                'invoiceReceived.entity',
                'invoiceReceived.ownership',
                'invoiceSent.entity',
                'invoiceSent.ownership',
                'service'
            ])
            ->where('id_cost_center', $this->costCenterId)
            ->whereNull('invoice_row.deleted_at');
        
        // Filtro per data sulla fattura
        if ($this->dateFrom && $this->dateTo) {
            $query->where(function($q) {
                // Fatture ricevute - usa data_invoice
                $q->whereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_received')
                        ->whereColumn('invoices_received.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_received')
                        ->whereBetween('invoices_received.data_invoice', [$this->dateFrom, $this->dateTo]);
                })
                // Fatture emesse - usa data_invoice
                ->orWhereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_sent')
                        ->whereColumn('invoices_sent.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_sent')
                        ->whereBetween('invoices_sent.data_invoice', [$this->dateFrom, $this->dateTo]);
                });
            });
        } elseif ($this->dateFrom) {
            $query->where(function($q) {
                $q->whereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_received')
                        ->whereColumn('invoices_received.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_received')
                        ->whereDate('invoices_received.data_invoice', '>=', $this->dateFrom);
                })
                ->orWhereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_sent')
                        ->whereColumn('invoices_sent.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_sent')
                        ->whereDate('invoices_sent.data_invoice', '>=', $this->dateFrom);
                });
            });
        } elseif ($this->dateTo) {
            $query->where(function($q) {
                $q->whereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_received')
                        ->whereColumn('invoices_received.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_received')
                        ->whereDate('invoices_received.data_invoice', '<=', $this->dateTo);
                })
                ->orWhereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_sent')
                        ->whereColumn('invoices_sent.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_sent')
                        ->whereDate('invoices_sent.data_invoice', '<=', $this->dateTo);
                });
            });
        }
        
        // Ordina
        $orderByField = $this->invoiceSortField === 'invoice_date' ? 'data_invoice' : 'total';
        $orderByDirection = $this->invoiceSortDirection;
        
        // Raccogli i risultati e raggruppa per documento
        $rows = $query->get();
        
        $invoices = [];
        foreach ($rows as $row) {
            $invoiceKey = $row->document_type . '_' . $row->document_id;
            
            if (!isset($invoices[$invoiceKey])) {
                if ($row->document_type === 'invoice_received' && $row->invoiceReceived) {
                    $invoice = $row->invoiceReceived;
                    $invoices[$invoiceKey] = [
                        'id' => $invoice->id,
                        'type' => 'received',
                        'number' => $invoice->n_invoice,
                        'date' => $invoice->data_invoice,  // CORRETTO: data_invoice
                        'entity_name' => $invoice->entity->ragione_sociale ?? ($invoice->entity->nome . ' ' . $invoice->entity->cognome),
                        'ownership' => $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-',
                        'total' => 0,
                        'rows' => []
                    ];
                } elseif ($row->document_type === 'invoice_sent' && $row->invoiceSent) {
                    $invoice = $row->invoiceSent;
                    $invoices[$invoiceKey] = [
                        'id' => $invoice->id,
                        'type' => 'sent',
                        'number' => $invoice->n_invoice,
                        'date' => $invoice->data_invoice,  // CORRETTO: data_invoice
                        'entity_name' => $invoice->entity->ragione_sociale ?? ($invoice->entity->nome . ' ' . $invoice->entity->cognome),
                        'ownership' => $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-',
                        'total' => 0,
                        'rows' => []
                    ];
                }
            }
            
            if (isset($invoices[$invoiceKey])) {
                $invoices[$invoiceKey]['total'] += $row->total;
                $invoices[$invoiceKey]['rows'][] = $row;
            }
        }
        
        // Converti in collection e ordina per data
        $collection = collect($invoices);
        
        if ($orderByField === 'data_invoice') {
            $collection = $collection->sortBy(function($item) use ($orderByDirection) {
                return $item['date'];
            }, SORT_REGULAR, $orderByDirection === 'desc');
        } else {
            $collection = $collection->sortBy(function($item) use ($orderByDirection) {
                return $item['total'];
            }, SORT_REGULAR, $orderByDirection === 'desc');
        }
        
        return $collection;
    }
    
    /**
     * Attività giornaliere con personale per il centro di costo nel periodo
     */
    public function getActivitiesProperty()
    {
        $query = Activity::with([
                'service:id,Titolo',
                'staffDetails.staff:id_personale,NomePers,CognomePers,Soprannome',
                'staffDetails.ownership'
            ])
            ->where('id_cost_centers', $this->costCenterId)
            ->when($this->dateFrom, fn($q) => $q->whereDate('data_activities', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('data_activities', '<=', $this->dateTo))
            ->when($this->activitySearch, function($q) {
                $search = '%' . $this->activitySearch . '%';
                $q->where(function($sq) use ($search) {
                    $sq->where('note', 'like', $search)
                      ->orWhere('invoice_references', 'like', $search);
                });
            })
            ->when($this->activityServiceFilter, fn($q) => $q->where('id_services', $this->activityServiceFilter))
            ->when($this->activityStaffFilter, function($q) {
                $q->whereHas('staffDetails', fn($sq) => $sq->where('id_staff', $this->activityStaffFilter));
            })
            ->orderBy($this->activitySortField, $this->activitySortDirection);
        
        return $query->get();
    }
    
    /**
     * Lista servizi per filtro
     */
    public function getServicesListProperty()
    {
        return \App\Models\Service::where('Stato', 1)
            ->orderBy('Titolo')
            ->get(['id', 'Titolo']);
    }
    
    /**
     * Lista personale per filtro
     */
    public function getStaffListProperty()
    {
        return \App\Models\Staff::where('valid', 1)
            ->orderBy('CognomePers')
            ->get(['id_personale as id', 'NomePers', 'CognomePers', 'Soprannome']);
    }
    
    /**
     * Totali riepilogativi
     */
    public function getTotalsProperty()
    {
        $invoiceTotal = $this->invoices->sum('total');
        
        $activities = $this->activities;
        $totalHa = $activities->sum('ha');
        $totalOre = $activities->sum(function($act) {
            return $act->staffDetails->sum('n_ore');
        });
        
        return [
            'invoice_total' => $invoiceTotal,
            'total_ha' => $totalHa,
            'total_ore' => $totalOre,
        ];
    }
    
    public function getFormattedDateFromAttribute()
    {
        return $this->dateFrom ? Carbon::parse($this->dateFrom)->format('d/m/Y') : '-';
    }
    
    public function getFormattedDateToAttribute()
    {
        return $this->dateTo ? Carbon::parse($this->dateTo)->format('d/m/Y') : '-';
    }
    
    public function render()
    {
        return view('livewire.admin.cost-center-detail-table', [
            'invoices' => $this->invoices,
            'activities' => $this->activities,
            'servicesList' => $this->servicesList,
            'staffList' => $this->staffList,
            'totals' => $this->totals,
        ]);
    }
}