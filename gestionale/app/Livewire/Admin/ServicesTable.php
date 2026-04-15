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
    public $perPage = 15;
    public $sortField = 'Titolo';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingService = null;
    
    // MODAL MODIFICA - AGGIUNTO
    public $showEditModal = false;
    public $editingService = null;
    public $editingId = null;
    
    // Form fields per modifica
    public $editTitolo = '';
    public $editCategoria = '';
    public $editDescrizione = '';
    public $editDescrFattura = '';
    public $editPrezzo = '';
    public $editUnitaMisura = '';
    public $editStato = true;
    
    protected $paginationTheme = 'tailwind';
    
    protected $queryString = ['search'];
    
    // Aggiungi le regole di validazione
    protected function rules()
    {
        return [
            'editTitolo' => 'required|string|max:255',
            'editCategoria' => 'nullable|exists:settings,id',
            'editDescrizione' => 'nullable|string',
            'editDescrFattura' => 'nullable|string',
            'editPrezzo' => 'nullable|numeric|min:0',
            'editUnitaMisura' => 'nullable|exists:unita_misura,id_um',
            'editStato' => 'boolean'
        ];
    }
    
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
    
    public function getUnitaMisuraProperty()
    {
        return UnitaMisura::where('valid', 1)->orderBy('nome')->get();
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
        
        return $query->with(['category', 'unitaMisura', 'createdBy', 'updatedBy'])->paginate($this->perPage);
    }
    
    public function viewService($id)
    {
        try {
            $service = Service::with(['category', 'unitaMisura', 'createdBy', 'updatedBy'])->find($id);
            
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
    
    // ==================== METODI MODIFICA ====================
    
    public function editService($id)
    {
        try {
            $service = Service::with(['category', 'unitaMisura'])->find($id);
            
            if (!$service) {
                $this->dispatch('showError', message: 'Servizio non trovato');
                return;
            }
            
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
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento dei dati: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingService = null;
        $this->editingId = null;
        $this->resetEditForm();
    }
    
    public function resetEditForm()
    {
        $this->editTitolo = '';
        $this->editCategoria = '';
        $this->editDescrizione = '';
        $this->editDescrFattura = '';
        $this->editPrezzo = '';
        $this->editUnitaMisura = '';
        $this->editStato = true;
    }
    
    public function updateService()
    {
        $this->validate();
        
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
            
            if (!$service) {
                session()->flash('error', 'Servizio non trovato');
                return;
            }
            
            $newStatus = !$service->Stato;
            $service->update([
                'Stato' => $newStatus,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);
            
            $statusText = $newStatus ? 'attivato' : 'disattivato';
            $this->dispatch('showSuccess', message: "Servizio '{$service->Titolo}' {$statusText} con successo!");
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
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
    
    public function getComponentKey()
    {
        return 'services-table-' . $this->getPage() . '-' . md5($this->search . $this->categoryFilter . $this->statusFilter . $this->sortField . $this->sortDirection);
    }

    public function render()
    {
        return view('livewire.admin.services-table', [
            'services' => $this->services,
            'categories' => $this->categories,
            'unitaMisura' => $this->unitaMisura,
            'componentKey' => $this->getComponentKey()
        ]);
    }
}