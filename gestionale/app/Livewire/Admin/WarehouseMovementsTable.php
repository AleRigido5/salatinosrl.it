<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WarehouseMovement;
use App\Models\WarehouseProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseMovementsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtri
    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Autocomplete prodotto (per filtro)
    public string $productSearch = '';
    public Collection $productResults;
    public string $selectedProductId = '';
    public string $selectedProductName = '';
    public bool $showProductDropdown = false;

    public int $perPage = 100;

    // Form nuovo movimento
    public bool $showFormModal = false;
    public string $formProductSearch = '';
    public Collection $formProductResults;
    public string $formProductId = '';
    public string $formProductName = '';
    public bool $showFormProductDropdown = false;
    public string $type = 'entrata';
    public string $quantity = '';
    public string $movement_date = '';
    public string $note = '';

    public function mount(): void
    {
        $this->productResults = new Collection();
        $this->formProductResults = new Collection();
        $this->movement_date = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->typeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->clearProductFilter();
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE PRODOTTO (filtro) ====================
    public function updatedProductSearch(): void
    {
        if (!empty($this->selectedProductId) && $this->productSearch === $this->selectedProductName) {
            $this->showProductDropdown = false;
            return;
        }
        if (!empty($this->selectedProductId) && $this->productSearch !== $this->selectedProductName) {
            $this->selectedProductId = '';
            $this->selectedProductName = '';
        }
        if (strlen(trim($this->productSearch)) < 2) {
            $this->productResults = new Collection();
            $this->showProductDropdown = false;
            return;
        }

        $this->productResults = WarehouseProduct::where('name', 'like', '%' . $this->productSearch . '%')
            ->orWhere('sku', 'like', '%' . $this->productSearch . '%')
            ->limit(10)
            ->get(['id', 'name', 'sku']);

        $this->showProductDropdown = $this->productResults->isNotEmpty();
    }

    public function selectProduct($id, string $name): void
    {
        $this->selectedProductId = (string) $id;
        $this->selectedProductName = $name;
        $this->productSearch = $name;
        $this->showProductDropdown = false;
        $this->resetPage();
    }

    public function clearProductFilter(): void
    {
        $this->selectedProductId = '';
        $this->selectedProductName = '';
        $this->productSearch = '';
        $this->showProductDropdown = false;
        $this->resetPage();
    }

    // ==================== ELENCO ====================
    public function getMovementsProperty()
    {
        $query = WarehouseMovement::with(['product', 'creator']);

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->selectedProductId) {
            $query->where('id_product', $this->selectedProductId);
        }

        if ($this->dateFrom) {
            $query->whereDate('movement_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('movement_date', '<=', $this->dateTo);
        }

        return $query->orderByDesc('movement_date')->orderByDesc('id')->paginate($this->perPage);
    }

    // ==================== AUTOCOMPLETE PRODOTTO (form) ====================
    public function updatedFormProductSearch(): void
    {
        if (!empty($this->formProductId) && $this->formProductSearch === $this->formProductName) {
            $this->showFormProductDropdown = false;
            return;
        }
        if (!empty($this->formProductId) && $this->formProductSearch !== $this->formProductName) {
            $this->formProductId = '';
            $this->formProductName = '';
        }
        if (strlen(trim($this->formProductSearch)) < 2) {
            $this->formProductResults = new Collection();
            $this->showFormProductDropdown = false;
            return;
        }

        $this->formProductResults = WarehouseProduct::where('valid', 1)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->formProductSearch . '%')
                  ->orWhere('sku', 'like', '%' . $this->formProductSearch . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'sku', 'quantity', 'unit_of_measure']);

        $this->showFormProductDropdown = $this->formProductResults->isNotEmpty();
    }

    public function selectFormProduct($id, string $name): void
    {
        $this->formProductId = (string) $id;
        $this->formProductName = $name;
        $this->formProductSearch = $name;
        $this->showFormProductDropdown = false;
    }

    public function clearFormProduct(): void
    {
        $this->formProductId = '';
        $this->formProductName = '';
        $this->formProductSearch = '';
    }

    // ==================== NUOVO MOVIMENTO ====================
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->clearFormProduct();
        $this->type = 'entrata';
        $this->quantity = '';
        $this->movement_date = now()->format('Y-m-d');
        $this->note = '';
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'formProductId' => 'required|exists:warehouse_products,id',
            'type' => 'required|in:entrata,uscita',
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $adminId = Auth::guard('admin')->id();
        $qty = (float) str_replace(',', '.', $this->quantity);

        DB::beginTransaction();
        try {
            $product = WarehouseProduct::findOrFail($this->formProductId);

            // Movimento manuale: reference_type/reference_id restano NULL
            // — verranno valorizzati in futuro quando i movimenti saranno
            // generati automaticamente dai DDT di acquisto/vendita.
            WarehouseMovement::create([
                'id_product' => $product->id,
                'type' => $this->type,
                'quantity' => $qty,
                'movement_date' => $this->movement_date,
                'reference_type' => null,
                'reference_id' => null,
                'note' => $this->note ?: null,
                'created_by' => $adminId,
            ]);

            // Aggiorna la giacenza cache del prodotto
            $delta = $this->type === WarehouseMovement::TYPE_IN ? $qty : -$qty;
            $product->increment('quantity', $delta);
            $product->update(['updated_by' => $adminId]);

            DB::commit();

            $this->dispatch('showSuccess', message: "Movimento registrato: {$this->type} di {$qty} {$product->unit_of_measure} su '{$product->name}'.");
            $this->closeFormModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-movements-table', [
            'movements' => $this->movements,
        ]);
    }
}