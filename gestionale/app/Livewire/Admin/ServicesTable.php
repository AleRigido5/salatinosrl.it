<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class ServicesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'Titolo';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingService = null;
    
    protected $paginationTheme = 'tailwind';
    
    protected $queryString = ['search'];
    
    // IMPORTANTE: Disabilita la paginazione standard di Livewire
    protected function paginate($query)
    {
        return $query->paginate($this->perPage);
    }
    
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
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }
    
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }
    
    public function getCategoriesProperty()
    {
        return Setting::where('tabella_riferimento', 'services')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getServicesProperty()
    {
        $query = Service::query();
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('Titolo', 'like', $searchTerm)
                  ->orWhere('Descrizione', 'like', $searchTerm)
                  ->orWhere('Descr_fattura', 'like', $searchTerm);
            });
        }
        
        if ($this->categoryFilter) {
            $query->where('id_categories', $this->categoryFilter);
        }
        
        if ($this->statusFilter !== '') {
            $query->where('Stato', $this->statusFilter === 'active');
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->with('category', 'unitaMisura')->paginate($this->perPage);
    }
    
    public function viewService($id)
    {
        try {
            $service = Service::with('category', 'unitaMisura')->find($id);
            
            if (!$service) {
                session()->flash('error', 'Servizio non trovato');
                return;
            }
            
            $this->viewingService = $service;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingService = null;
    }
    
    public function editService($id)
    {
        return redirect()->route('admin.services.edit', $id);
    }
    
    public function toggleStatus($id)
    {
        try {
            $service = Service::find($id);
            
            if (!$service) {
                session()->flash('error', 'Servizio non trovato');
                return;
            }
            
            $newStatus = !$service->Stato;
            $service->update(['Stato' => $newStatus]);
            
            $statusText = $newStatus ? 'attivato' : 'disattivato';
            session()->flash('success', "Servizio '{$service->Titolo}' {$statusText} con successo!");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore: ' . $e->getMessage());
        }
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'Titolo';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    // Metodo per generare una chiave univoca per il componente
    public function getComponentKey()
    {
        return 'services-table-' . $this->getPage() . '-' . md5($this->search . $this->categoryFilter . $this->statusFilter . $this->sortField . $this->sortDirection);
    }

    public function render()
    {
        return view('livewire.admin.services-table', [
            'services' => $this->services,
            'categories' => $this->categories,
            'componentKey' => $this->getComponentKey()
        ]);
    }
}