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

    // Filtri elenco prodotti
    public string $search = '';
    public string $statusFilter = '1'; // default: solo attivi
    public int $perPage = 100;

    // Autocomplete: filtro Categoria nell'elenco prodotti
    public string $categoryFilterSearch = '';
    public Collection $categoryFilterResults;
    public string $categoryFilter = '';
    public string $categoryFilterName = '';
    public bool $showCategoryFilterDropdown = false;

    // Form crea/modifica prodotto
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $sku = '';
    public string $name = '';
    public string $description = '';
    public string $unit_of_measure = '';
    public string $quantity = '0';
    public bool $valid = true;

    // Autocomplete: Categoria dentro il form prodotto
    public string $id_category = '';
    public string $categorySearch = '';
    public Collection $categoryResults;
    public bool $showCategoryDropdown = false;

    // Eliminazione prodotto
    public ?int $deletingId = null;

    // ==================== GESTIONE CATEGORIE (modal dedicato) ====================
    public bool $showCategoriesModal = false;
    public string $categoryModalSearch = ''; // ricerca/filtro nella lista del modal
    public string $categoryName = '';
    public ?int $editingCategoryId = null;
    public ?int $deletingCategoryId = null;

    // Autocomplete: Categoria PADRE, quando aggiungo/modifico una sottocategoria
    public string $categoryParentSearch = '';
    public Collection $categoryParentResults;
    public string $categoryParentId = '';
    public bool $showCategoryParentDropdown = false;

    public function mount(): void
    {
        $this->categoryFilterResults = new Collection();
        $this->categoryResults = new Collection();
        $this->categoryParentResults = new Collection();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->clearCategoryFilter();
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE: FILTRO CATEGORIA (elenco prodotti) ====================
    public function updatedCategoryFilterSearch(): void
    {
        if ($this->categoryFilter && $this->categoryFilterSearch === $this->categoryFilterName) {
            $this->showCategoryFilterDropdown = false;
            return;
        }
        if ($this->categoryFilter && $this->categoryFilterSearch !== $this->categoryFilterName) {
            $this->categoryFilter = '';
            $this->categoryFilterName = '';
            $this->resetPage();
        }
        if (strlen(trim($this->categoryFilterSearch)) < 2) {
            $this->categoryFilterResults = new Collection();
            $this->showCategoryFilterDropdown = false;
            return;
        }

        $this->categoryFilterResults = WarehouseCategory::with('parent')
            ->where('name', 'like', '%' . $this->categoryFilterSearch . '%')
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->showCategoryFilterDropdown = $this->categoryFilterResults->isNotEmpty();
    }

    public function selectCategoryFilter($id, string $name): void
    {
        $this->categoryFilter = (string) $id;
        $this->categoryFilterName = $name;
        $this->categoryFilterSearch = $name;
        $this->showCategoryFilterDropdown = false;
        $this->resetPage();
    }

    public function clearCategoryFilter(): void
    {
        $this->categoryFilter = '';
        $this->categoryFilterName = '';
        $this->categoryFilterSearch = '';
        $this->showCategoryFilterDropdown = false;
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE: CATEGORIA nel form prodotto ====================
    public function updatedCategorySearch(): void
    {
        if ($this->id_category && $this->categorySearch === $this->categorySelectedName()) {
            $this->showCategoryDropdown = false;
            return;
        }
        if ($this->id_category && $this->categorySearch !== $this->categorySelectedName()) {
            $this->id_category = '';
        }
        if (strlen(trim($this->categorySearch)) < 2) {
            $this->categoryResults = new Collection();
            $this->showCategoryDropdown = false;
            return;
        }

        $this->categoryResults = WarehouseCategory::with('parent')
            ->where('name', 'like', '%' . $this->categorySearch . '%')
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->showCategoryDropdown = $this->categoryResults->isNotEmpty();
    }

    // Nome pieno ("Principale > Sotto") dell'ultima categoria selezionata,
    // usato solo per capire se l'utente ha ritoccato il testo dopo la scelta.
    protected function categorySelectedName(): string
    {
        if (!$this->id_category) {
            return '';
        }
        $cat = WarehouseCategory::with('parent')->find($this->id_category);
        return $cat ? $cat->full_name : '';
    }

    public function selectCategory($id, string $fullName): void
    {
        $this->id_category = (string) $id;
        $this->categorySearch = $fullName;
        $this->showCategoryDropdown = false;
    }

    public function clearCategory(): void
    {
        $this->id_category = '';
        $this->categorySearch = '';
        $this->showCategoryDropdown = false;
    }

    // ==================== ALBERO CATEGORIE (helper per il modal Gestione Categorie) ====================

    /**
     * Restituisce le categorie da mostrare nel modal "Gestione Categorie",
     * appiattite con 'depth' (0 = principale, 1 = sottocategoria).
     *
     * Senza ricerca: mostra l'intero albero (adatto quando le categorie
     * sono poche). Con ricerca ($categoryModalSearch): mostra SOLO le
     * categorie il cui nome corrisponde, in elenco piatto — indispensabile
     * quando ce ne sono centinaia/migliaia, per non dover scorrere tutto.
     */
    public function getFlattenedCategoriesProperty(): array
    {
        if (strlen(trim($this->categoryModalSearch)) >= 2) {
            $matches = WarehouseCategory::with('parent')
                ->where('name', 'like', '%' . $this->categoryModalSearch . '%')
                ->orderBy('name')
                ->limit(100)
                ->get();

            return $matches->map(fn ($cat) => ['category' => $cat, 'depth' => $cat->isMainCategory() ? 0 : 1])->all();
        }

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
        $product = WarehouseProduct::with('category.parent')->findOrFail($id);

        $this->editingId = $product->id;
        $this->sku = $product->sku ?? '';
        $this->id_category = (string) ($product->id_category ?? '');
        $this->categorySearch = $product->category ? $product->category->full_name : '';
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
        $this->clearCategory();
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
        $this->categoryModalSearch = '';
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
        $this->clearCategoryParent();
        $this->deletingCategoryId = null;
        $this->resetErrorBag();
    }

    public function editCategory(int $id): void
    {
        $category = WarehouseCategory::with('parent')->findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        if ($category->parent) {
            $this->categoryParentId = (string) $category->parent->id;
            $this->categoryParentSearch = $category->parent->name;
        } else {
            $this->clearCategoryParent();
        }
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->clearCategoryParent();
        $this->resetErrorBag();
    }

    // ==================== AUTOCOMPLETE: CATEGORIA PADRE (nel modal categorie) ====================
    public function updatedCategoryParentSearch(): void
    {
        if ($this->categoryParentId && $this->categoryParentSearch === $this->categoryParentSelectedName()) {
            $this->showCategoryParentDropdown = false;
            return;
        }
        if ($this->categoryParentId && $this->categoryParentSearch !== $this->categoryParentSelectedName()) {
            $this->categoryParentId = '';
        }
        if (strlen(trim($this->categoryParentSearch)) < 2) {
            $this->categoryParentResults = new Collection();
            $this->showCategoryParentDropdown = false;
            return;
        }

        // Solo categorie PRINCIPALI possono essere scelte come genitore
        // (struttura a 2 soli livelli), escludendo quella in modifica.
        $this->categoryParentResults = WarehouseCategory::whereNull('parent_id')
            ->when($this->editingCategoryId, fn ($q) => $q->where('id', '!=', $this->editingCategoryId))
            ->where('name', 'like', '%' . $this->categoryParentSearch . '%')
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->showCategoryParentDropdown = $this->categoryParentResults->isNotEmpty();
    }

    protected function categoryParentSelectedName(): string
    {
        if (!$this->categoryParentId) {
            return '';
        }
        $cat = WarehouseCategory::find($this->categoryParentId);
        return $cat ? $cat->name : '';
    }

    public function selectCategoryParent($id, string $name): void
    {
        $this->categoryParentId = (string) $id;
        $this->categoryParentSearch = $name;
        $this->showCategoryParentDropdown = false;
    }

    public function clearCategoryParent(): void
    {
        $this->categoryParentId = '';
        $this->categoryParentSearch = '';
        $this->showCategoryParentDropdown = false;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryName' => 'required|string|max:150',
            'categoryParentId' => 'nullable|exists:warehouse_categories,id',
        ], [], [
            'categoryName' => 'nome categoria',
        ]);

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