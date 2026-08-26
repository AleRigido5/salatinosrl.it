<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-boxes-stacked mr-3 text-lime-600"></i>
            Catalogo Prodotti
        </h1>
        <div class="flex items-center gap-2">
            <button wire:click="openCategoriesModal" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition-all duration-200">
                <i class="fa-solid fa-sitemap mr-2"></i> Gestione Categorie
            </button>
            <button wire:click="openCreateModal" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-plus mr-2"></i> Nuovo Prodotto
            </button>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cerca</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nome, codice, descrizione..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
            </div>

            <!-- Autocomplete: filtro Categoria -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Categoria</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="categoryFilterSearch"
                        x-on:focus="open = true"
                        placeholder="Cerca categoria..."
                        class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @if($categoryFilter)
                    <button type="button" wire:click="clearCategoryFilter" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>
                    @endif
                </div>
                <div x-show="open && @entangle('showCategoryFilterDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                    @forelse($categoryFilterResults as $item)
                        <div x-on:click="open = false; @this.call('selectCategoryFilter', {{ $item->id }}, '{{ addslashes($item->full_name) }}')"
                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            {{ $item->full_name }}
                        </div>
                    @empty
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutti</option>
                    <option value="1">Attivi</option>
                    <option value="0">Disattivi</option>
                </select>
            </div>
        </div>
        @if($search || $statusFilter !== '1' || $categoryFilter)
        <div class="mt-3 pt-3 border-t border-gray-200">
            <button wire:click="clearFilters" class="text-xs text-gray-400 hover:text-red-500">
                <i class="fas fa-trash-alt mr-1"></i> Rimuovi filtri
            </button>
        </div>
        @endif
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Codice</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Categoria</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Descrizione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Giacenza</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50" wire:key="product-{{ $product->id }}">
                        <td class="px-4 py-3 text-sm font-mono">{{ $product->sku ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($product->category)
                                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700 font-medium">
                                    {{ $product->category->full_name }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-sm truncate" title="{{ $product->description }}">{{ $product->description ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $product->quantity_label }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($product->valid)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Attivo</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Disattivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <button wire:click="openEditModal({{ $product->id }})" class="text-yellow-600 hover:text-yellow-900 mr-2" title="Modifica">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $product->id }})" class="text-red-600 hover:text-red-900" title="Elimina">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-boxes-stacked text-4xl mb-2 text-gray-300 block"></i>
                            Nessun prodotto trovato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($products->hasPages())
    <div class="mt-4">{{ $products->links() }}</div>
    @endif

    <!-- MODAL CREA/MODIFICA PRODOTTO -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $editingId ? 'Modifica Prodotto' : 'Nuovo Prodotto' }}</h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice / SKU</label>
                            <input type="text" wire:model="sku" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('sku') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Autocomplete: Categoria del prodotto -->
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="categorySearch"
                                    x-on:focus="open = true"
                                    placeholder="Cerca categoria..."
                                    class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @if($id_category)
                                <button type="button" wire:click="clearCategory" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>
                            <div x-show="open && @entangle('showCategoryDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @forelse($categoryResults as $item)
                                    <div x-on:click="open = false; @this.call('selectCategory', {{ $item->id }}, '{{ addslashes($item->full_name) }}')"
                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                        {{ $item->full_name }}
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                @endforelse
                            </div>
                            @error('id_category') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                        <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unità di Misura</label>
                            <input type="text" wire:model="unit_of_measure" placeholder="pz, kg, lt..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giacenza</label>
                            <input type="text" inputmode="decimal" wire:model="quantity" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('quantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                                <input type="checkbox" wire:model="valid" class="rounded text-lime-600 focus:ring-lime-500">
                                Prodotto attivo
                            </label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">Giacenza modificabile manualmente qui — normalmente si aggiorna dalle Movimentazioni.</p>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeFormModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="save" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> Salva
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE PRODOTTO -->
    @if($deletingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
            <p class="text-sm text-gray-500 mb-4">Il prodotto verrà eliminato definitivamente. Continuare?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL GESTIONE CATEGORIE ==================== -->
    @if($showCategoriesModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800"><i class="fa-solid fa-sitemap mr-2 text-lime-600"></i> Gestione Categorie</h3>
                    <button wire:click="closeCategoriesModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <!-- Form aggiungi/modifica categoria -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                            {{ $editingCategoryId ? 'Modifica categoria' : 'Aggiungi nuova categoria' }}
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2">
                            <div>
                                <input type="text" wire:model="categoryName" placeholder="Nome categoria..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @error('categoryName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Autocomplete: categoria padre -->
                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="categoryParentSearch"
                                        x-on:focus="open = true"
                                        placeholder="Sotto categoria principale... (vuoto = principale)"
                                        class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    @if($categoryParentId)
                                    <button type="button" wire:click="clearCategoryParent" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>
                                <div x-show="open && @entangle('showCategoryParentDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                    @forelse($categoryParentResults as $item)
                                        <div x-on:click="open = false; @this.call('selectCategoryParent', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                            {{ $item->name }}
                                        </div>
                                    @empty
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessuna categoria principale trovata</div>
                                    @endforelse
                                </div>
                                @error('categoryParentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex gap-2">
                                <button wire:click="saveCategory" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md text-sm transition-colors whitespace-nowrap">
                                    <i class="fas fa-check mr-1"></i> {{ $editingCategoryId ? 'Salva' : 'Aggiungi' }}
                                </button>
                                @if($editingCategoryId)
                                <button wire:click="cancelEditCategory" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Ricerca dinamica nella lista (indispensabile con molte categorie) -->
                    <div>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            <input type="text" wire:model.live.debounce.300ms="categoryModalSearch"
                                placeholder="Cerca tra tutte le categorie e sottocategorie..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        </div>
                        @if(strlen(trim($categoryModalSearch)) >= 2)
                            <p class="text-xs text-gray-400 mt-1">Risultati per "{{ $categoryModalSearch }}" — cancella la ricerca per vedere l'intero albero.</p>
                        @endif
                    </div>

                    <!-- Elenco categorie -->
                    <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-96 overflow-y-auto">
                        @forelse($flattenedCategories as $item)
                            @php $cat = $item['category']; @endphp
                            <div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50" wire:key="category-{{ $cat->id }}"
                                style="padding-left: {{ 1 + $item['depth'] * 1.5 }}rem;">
                                <div class="flex items-center gap-2 text-sm">
                                    @if($item['depth'] > 0)
                                        <i class="fas fa-turn-up fa-rotate-90 text-gray-300 text-xs"></i>
                                        @if(strlen(trim($categoryModalSearch)) >= 2)
                                            <span class="text-xs text-gray-400">{{ $cat->parent->name ?? '' }} ›</span>
                                        @endif
                                    @else
                                        <i class="fas fa-folder text-lime-500"></i>
                                    @endif
                                    <span class="{{ $item['depth'] === 0 ? 'font-semibold text-gray-800' : 'text-gray-600' }}">{{ $cat->name }}</span>
                                    <span class="text-xs text-gray-400">({{ $cat->products()->count() }} prodotti)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="editCategory({{ $cat->id }})" class="text-yellow-600 hover:text-yellow-800 text-sm" title="Modifica">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <button wire:click="confirmDeleteCategory({{ $cat->id }})" class="text-red-600 hover:text-red-800 text-sm" title="Elimina">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-400 text-sm py-6">
                                {{ strlen(trim($categoryModalSearch)) >= 2 ? 'Nessuna categoria trovata per questa ricerca' : 'Nessuna categoria presente' }}
                            </p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeCategoriesModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE CATEGORIA -->
    @if($deletingCategoryId)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione categoria</h3>
            <p class="text-sm text-gray-500 mb-4">La categoria verrà eliminata definitivamente. Continuare?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDeleteCategory" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                <button wire:click="deleteCategory" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
            </div>
        </div>
    </div>
    @endif
</div>