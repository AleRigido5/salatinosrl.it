<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WarehouseProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseProductsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtri
    public string $search = '';
    public string $statusFilter = '1'; // default: solo attivi
    public int $perPage = 100;

    // Form crea/modifica
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $sku = '';
    public string $name = '';
    public string $description = '';
    public string $unit_of_measure = '';
    public string $quantity = '0';
    public bool $valid = true;

    // Eliminazione
    public ?int $deletingId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    // ==================== ELENCO ====================
    public function getProductsProperty()
    {
        $query = WarehouseProduct::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter);
        }

        return $query->orderBy('name')->paginate($this->perPage);
    }

    // ==================== CREA / MODIFICA ====================
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $product = WarehouseProduct::findOrFail($id);

        $this->editingId = $product->id;
        $this->sku = $product->sku ?? '';
        $this->name = $product->name;
        $this->description = $product->description ?? '';
        $this->unit_of_measure = $product->unit_of_measure ?? '';
        $this->quantity = (string) $product->quantity;
        $this->valid = (bool) $product->valid;

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->sku = '';
        $this->name = '';
        $this->description = '';
        $this->unit_of_measure = '';
        $this->quantity = '0';
        $this->valid = true;
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'sku' => 'nullable|string|max:100|unique:warehouse_products,sku,' . ($this->editingId ?: 'NULL') . ',id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_of_measure' => 'nullable|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'valid' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $adminId = Auth::guard('admin')->id();

        $data = [
            'sku' => $this->sku ?: null,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'unit_of_measure' => $this->unit_of_measure ?: null,
            'quantity' => (float) str_replace(',', '.', $this->quantity),
            'valid' => $this->valid,
            'updated_by' => $adminId,
        ];

        DB::beginTransaction();
        try {
            if ($this->editingId) {
                $product = WarehouseProduct::findOrFail($this->editingId);
                $product->update($data);
            } else {
                $data['created_by'] = $adminId;
                $product = WarehouseProduct::create($data);
            }

            DB::commit();

            $this->dispatch('showSuccess', message: "Prodotto '{$product->name}' " . ($this->editingId ? 'aggiornato' : 'creato') . ' con successo.');
            $this->closeFormModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== ELIMINAZIONE ====================
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        $product = WarehouseProduct::find($this->deletingId);

        if ($product) {
            // Non eliminiamo un prodotto con movimenti collegati (FK con
            // ON DELETE RESTRICT lo impedirebbe comunque a livello DB, ma
            // qui diamo un messaggio chiaro invece del solo errore SQL).
            if ($product->movements()->exists()) {
                $this->dispatch('showError', message: "Impossibile eliminare '{$product->name}': ha movimentazioni collegate.");
                $this->deletingId = null;
                return;
            }

            $name = $product->name;
            $product->delete();
            $this->dispatch('showSuccess', message: "Prodotto '{$name}' eliminato.");
        }

        $this->deletingId = null;
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-products-table', [
            'products' => $this->products,
        ]);
    }
}