<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WarehouseProduct;
use App\Models\WarehouseCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class WarehouseProductsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtri
    public string $search = '';
    public string $statusFilter = '1'; // default: solo attivi
    public string $categoryFilter = '';
    public int $perPage = 100;

    // Form crea/modifica prodotto
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $sku = '';
    public string $id_category = '';
    public string $name = '';
    public string $description = '';
    public string $unit_of_measure = '';
    public string $quantity = '0';
    public bool $valid = true;

    // Eliminazione prodotto
    public ?int $deletingId = null;

    // ==================== GESTIONE CATEGORIE (modal dedicato) ====================
    public bool $showCategoriesModal = false;
    public string $categoryName = '';
    public string $categoryParentId = '';
    public ?int $editingCategoryId = null;
    public ?int $deletingCategoryId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->categoryFilter = '';
        $this->resetPage();
    }

    // ==================== ALBERO CATEGORIE (helper condiviso) ====================

    /**
     * Restituisce tutte le categorie appiattite in un array con 'depth'
     * (0 = principale, 1 = sottocategoria), ordinate: ogni principale
     * seguita subito dalle sue sottocategorie. Usato sia per il <select>
     * del form prodotto/filtro, sia per la lista nel modal categorie.
     */
    public function getFlattenedCategoriesProperty(): array
    {
        $all = WarehouseCategory::orderBy('sort_order')->orderBy('name')->get();
        $byParent = $all->groupBy('parent_id');

        $flatten = function ($parentId, $depth) use (&$flatten, $byParent) {
            $result = [];
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $result[] = ['category' => $cat, 'depth' => $depth];
                $result = array_merge($result, $flatten($cat->id, $depth + 1));
            }
            return $result;
        };

        return $flatten(null, 0);
    }

    // ==================== ELENCO PRODOTTI ====================
    public function getProductsProperty()
    {
        $query = WarehouseProduct::with('category.parent');

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

        if ($this->categoryFilter) {
            // Se è selezionata una categoria PRINCIPALE, includi anche i
            // prodotti delle sue sottocategorie; se è una sottocategoria,
            // filtra solo quella.
            $selected = WarehouseCategory::find($this->categoryFilter);
            if ($selected && $selected->isMainCategory()) {
                $childIds = WarehouseCategory::where('parent_id', $selected->id)->pluck('id');
                $query->whereIn('id_category', $childIds->push($selected->id));
            } else {
                $query->where('id_category', $this->categoryFilter);
            }
        }

        return $query->orderBy('name')->paginate($this->perPage);
    }

    // ==================== CREA / MODIFICA PRODOTTO ====================
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
        $this->id_category = (string) ($product->id_category ?? '');
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
        $this->id_category = '';
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
            'id_category' => 'nullable|exists:warehouse_categories,id',
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
            'id_category' => $this->id_category ?: null,
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

    // ==================== ELIMINAZIONE PRODOTTO ====================
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

    // ==================== GESTIONE CATEGORIE ====================
    public function openCategoriesModal(): void
    {
        $this->resetCategoryForm();
        $this->showCategoriesModal = true;
    }

    public function closeCategoriesModal(): void
    {
        $this->showCategoriesModal = false;
        $this->resetCategoryForm();
    }

    protected function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryParentId = '';
        $this->deletingCategoryId = null;
        $this->resetErrorBag();
    }

    public function editCategory(int $id): void
    {
        $category = WarehouseCategory::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryParentId = (string) ($category->parent_id ?? '');
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryParentId = '';
        $this->resetErrorBag();
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => 'required|string|max:150',
            'categoryParentId' => 'nullable|exists:warehouse_categories,id',
        ], [], [
            'categoryName' => 'nome categoria',
        ]);

        // Una sottocategoria non può diventare a sua volta genitore di
        // un'altra: qui gestiamo solo 2 livelli, come richiesto.
        if ($this->categoryParentId) {
            $parent = WarehouseCategory::find($this->categoryParentId);
            if ($parent && !$parent->isMainCategory()) {
                $this->addError('categoryParentId', 'Puoi scegliere solo una categoria principale come genitore.');
                return;
            }
        }

        if ($this->editingCategoryId) {
            $category = WarehouseCategory::findOrFail($this->editingCategoryId);
            $category->update([
                'name' => $this->categoryName,
                'parent_id' => $this->categoryParentId ?: null,
            ]);
            $this->dispatch('showSuccess', message: "Categoria '{$category->name}' aggiornata.");
        } else {
            $maxOrder = WarehouseCategory::where('parent_id', $this->categoryParentId ?: null)->max('sort_order');
            WarehouseCategory::create([
                'name' => $this->categoryName,
                'parent_id' => $this->categoryParentId ?: null,
                'sort_order' => ($maxOrder ?? 0) + 1,
            ]);
            $this->dispatch('showSuccess', message: "Categoria '{$this->categoryName}' creata.");
        }

        $this->cancelEditCategory();
    }

    public function confirmDeleteCategory(int $id): void
    {
        $this->deletingCategoryId = $id;
    }

    public function cancelDeleteCategory(): void
    {
        $this->deletingCategoryId = null;
    }

    public function deleteCategory(): void
    {
        if (!$this->deletingCategoryId) {
            return;
        }

        $category = WarehouseCategory::find($this->deletingCategoryId);

        if ($category) {
            if ($category->children()->exists()) {
                $this->dispatch('showError', message: "Impossibile eliminare '{$category->name}': ha sottocategorie collegate. Eliminale prima singolarmente.");
                $this->deletingCategoryId = null;
                return;
            }

            if ($category->products()->exists()) {
                $this->dispatch('showError', message: "Impossibile eliminare '{$category->name}': ci sono prodotti collegati. Riassegnali prima a un'altra categoria.");
                $this->deletingCategoryId = null;
                return;
            }

            $name = $category->name;
            $category->delete();
            $this->dispatch('showSuccess', message: "Categoria '{$name}' eliminata.");
        }

        $this->deletingCategoryId = null;
    }

    public function render()
    {
        return view('livewire.admin.warehouse.warehouse-products-table', [
            'products' => $this->products,
            'flattenedCategories' => $this->flattenedCategories,
        ]);
    }
}