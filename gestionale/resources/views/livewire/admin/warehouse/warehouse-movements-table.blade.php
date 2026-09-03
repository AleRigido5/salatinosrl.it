<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-right-left mr-3 text-lime-600"></i>
            Movimentazioni Magazzino
        </h1>
        <button wire:click="openCreateModal" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200" title="Nuovo Movimento [ALT + N]">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Autocomplete Prodotto -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Prodotto</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="productSearch"
                        x-on:focus="open = true"
                        placeholder="Cerca prodotto..."
                        class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @if($selectedProductId)
                    <button type="button" wire:click="clearProductFilter" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>
                    @endif
                </div>
                <div x-show="open && @entangle('showProductDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                    @forelse($productResults as $item)
                        <div x-on:click="open = false; @this.call('selectProduct', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            {{ $item->name }} @if($item->sku) <span class="text-xs text-gray-400">({{ $item->sku }})</span> @endif
                        </div>
                    @empty
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                <select wire:model.live="typeFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutti</option>
                    <option value="entrata">Entrata</option>
                    <option value="uscita">Uscita</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Data da</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Data a</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
            </div>
        </div>
        @if($typeFilter || $dateFrom || $dateTo || $selectedProductId)
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Prodotto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Tipo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Quantità</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Origine</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Nota</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Inserito da</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($movements as $movement)
                    <tr class="hover:bg-gray-50" wire:key="movement-{{ $movement->id }}">
                        <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $movement->movement_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $movement->product->name ?? '-' }}
                            @if($movement->product && $movement->product->sku)
                                <span class="text-xs text-gray-400">({{ $movement->product->sku }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $movement->type_badge_class }}">
                                {{ $movement->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $movement->type === 'entrata' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $movement->type === 'entrata' ? '+' : '-' }}{{ number_format((float) $movement->quantity, 2, ',', '.') }}
                            {{ $movement->product->unit_of_measure ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($movement->isManual())
                                <span class="inline-flex px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600">Manuale</span>
                            @else
                                <span class="inline-flex px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700">{{ $movement->reference_type }} #{{ $movement->reference_id }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $movement->note }}">{{ $movement->note ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $movement->creator->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-right-left text-4xl mb-2 text-gray-300 block"></i>
                            Nessuna movimentazione trovata
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($movements->hasPages())
    <div class="mt-4">{{ $movements->links() }}</div>
    @endif

    <!-- MODAL NUOVO MOVIMENTO -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Nuovo Movimento</h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <!-- Autocomplete Prodotto -->
                    <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prodotto <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="formProductSearch"
                                x-on:focus="open = true"
                                placeholder="Cerca prodotto..."
                                class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @if($formProductId)
                            <button type="button" wire:click="clearFormProduct" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                            @endif
                        </div>
                        <div x-show="open && @entangle('showFormProductDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                            @forelse($formProductResults as $item)
                                <div x-on:click="open = false; @this.call('selectFormProduct', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div>{{ $item->name }} @if($item->sku) <span class="text-xs text-gray-400">({{ $item->sku }})</span> @endif</div>
                                    <div class="text-xs text-gray-400">Giacenza attuale: {{ number_format((float) $item->quantity, 2, ',', '.') }} {{ $item->unit_of_measure }}</div>
                                </div>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                            @endforelse
                        </div>
                        @error('formProductId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <button type="button" wire:click="$set('type', 'entrata')"
                                    class="flex-1 px-3 py-2 rounded-md text-sm font-medium border-2 transition-all {{ $type === 'entrata' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-200' }}">
                                    <i class="fas fa-arrow-down mr-1"></i> Entrata
                                </button>
                                <button type="button" wire:click="$set('type', 'uscita')"
                                    class="flex-1 px-3 py-2 rounded-md text-sm font-medium border-2 transition-all {{ $type === 'uscita' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200' }}">
                                    <i class="fas fa-arrow-up mr-1"></i> Uscita
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="movement_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('movement_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantità <span class="text-red-500">*</span></label>
                        <input type="text" inputmode="decimal" wire:model="quantity" placeholder="0,00" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        @error('quantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                        <input type="text" wire:model="note" placeholder="Note aggiuntive..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeFormModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="save" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> Registra Movimento
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('keydown', function(e) {
        if (e.altKey && (e.key === "n" || e.key === "N")) {
            const tag = document.activeElement.tagName;
            if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") {
                return;
            }
            e.preventDefault();
            @this.call('openCreateModal');
        }
    });
</script>