<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Service;
use App\Models\Setting;
use App\Models\UnitaMisura;
use Illuminate\Support\Facades\Auth;

class ServicesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $sortField = 'Titolo';
    public $sortDirection = 'asc';
    public $perPage = 15;
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingService = null;
    
    // Modal modifica
    public $showEditModal = false;
    public $editingService = null;
    public $editingId = null;
    public $editTitolo = '';
    public $editCategoria = '';
    public $editDescrizione = '';
    public $editDescrFattura = '';
    public $editPrezzo = '';
    public $editUnitaMisura = '';
    public $editStato = true;
    
    protected $paginationTheme = 'tailwind';
    
    public function getServicesProperty()
    {
        $query = Service::with(['category', 'unitaMisura', 'createdBy', 'updatedBy']);
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('Titolo', 'like', $searchTerm)
                  ->orWhere('Descrizione', 'like', $searchTerm);
            });
        }
        
        if ($this->categoryFilter) {
            $query->where('id_categories', $this->categoryFilter);
        }
        
        if ($this->statusFilter === 'active') {
            $query->where('Stato', 1);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('Stato', 0);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    public function getCategoriesProperty()
    {
        return Setting::where('tabella_riferimento', 'services')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getUnitaMisuraProperty()
    {
        return UnitaMisura::where('valid', 1)->orderBy('nome')->get();
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
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter']);
        $this->resetPage();
    }
    
    public function viewService($id)
    {
        $this->viewingService = Service::with(['category', 'unitaMisura', 'createdBy', 'updatedBy'])->find($id);
        $this->showViewModal = true;
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingService = null;
    }
    
    public function editService($id)
    {
        $service = Service::find($id);
        if ($service) {
            $this->editingId = $id;
            $this->editingService = $service;
            $this->editTitolo = $service->Titolo;
            $this->editCategoria = $service->id_categories;
            $this->editDescrizione = $service->Descrizione;
            $this->editDescrFattura = $service->Descr_fattura;
            $this->editPrezzo = $service->Prezzo_un;
            $this->editUnitaMisura = $service->UnitaMisura_id_unita;
            $this->editStato = (bool)$service->Stato;
            $this->showEditModal = true;
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingService = null;
        $this->editingId = null;
    }
    
    public function updateService()
    {
        $this->validate([
            'editTitolo' => 'required|string|max:255',
            'editPrezzo' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $service = Service::find($this->editingId);
            if ($service) {
                $service->update([
                    'Titolo' => $this->editTitolo,
                    'id_categories' => $this->editCategoria,
                    'Descrizione' => $this->editDescrizione,
                    'Descr_fattura' => $this->editDescrFattura,
                    'Prezzo_un' => $this->editPrezzo,
                    'UnitaMisura_id_unita' => $this->editUnitaMisura,
                    'Stato' => $this->editStato,
                    'updated_by' => Auth::guard('admin')->id(),
                    'updated_at' => now()
                ]);
                
                $this->closeEditModal();
                $this->dispatch('showSuccess', message: 'Servizio aggiornato con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    public function toggleStatus($id)
    {
        try {
            $service = Service::find($id);
            if ($service) {
                $newStatus = !$service->Stato;
                $service->update([
                    'Stato' => $newStatus,
                    'updated_by' => Auth::guard('admin')->id(),
                    'updated_at' => now()
                ]);
                $statusText = $newStatus ? 'attivato' : 'disattivato';
                $this->dispatch('showSuccess', message: "Servizio '{$service->Titolo}' {$statusText} con successo!");
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    public function getComponentKeyProperty()
    {
        return md5($this->search . $this->categoryFilter . $this->statusFilter . $this->sortField . $this->sortDirection . $this->page);
    }
    
    public function render()
    {
        return view('livewire.admin.services.services-table', [
            'services' => $this->services,
            'categories' => $this->categories,
            'unitaMisura' => $this->unitaMisura,
            'componentKey' => $this->componentKey,
        ]);
    }
}