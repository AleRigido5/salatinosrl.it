<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoicesReceivedTable extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;
    public $sortField = 'data_invoice';
    public $sortDirection = 'desc';
    
    protected $paginationTheme = 'tailwind';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 15],
        'sortField' => ['except' => 'data_invoice'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }
    
    public function updatingDateFrom()
    {
        $this->resetPage();
    }
    
    public function updatingDateTo()
    {
        $this->resetPage();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    
    public function getInvoicesProperty()
    {
        $query = InvoiceReceived::with(['ownership', 'entity', 'rows']);
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('n_invoice', 'like', $searchTerm)
                  ->orWhere('causale', 'like', $searchTerm)
                  ->orWhere('sdi_id', 'like', $searchTerm);
            });
        }
        
        if ($this->status) {
            $query->where('status', $this->status);
        }
        
        if ($this->dateFrom) {
            $query->whereDate('data_invoice', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('data_invoice', '<=', $this->dateTo);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        if ($this->perPage == 0) {
            return $query->get();
        }
        
        return $query->paginate($this->perPage);
    }
    
    public function deleteInvoice($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_purchases')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $invoice = InvoiceReceived::find($id);
            if ($invoice) {
                $invoice->rows()->delete();
                $invoice->delete();
                $this->dispatch('showSuccess', message: 'Fattura eliminata con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function getStatusesProperty()
    {
        return config('gestionale.invoice_status', []);
    }
    
    public function getTypesProperty()
    {
        return config('gestionale.invoice_types', []);
    }
    
    public function formatDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
    }
    
    public function render()
    {
        return view('livewire.admin.invoices-received.invoices-received-table', [
            'invoices' => $this->invoices,
            'statuses' => $this->statuses,
        ]);
    }
}