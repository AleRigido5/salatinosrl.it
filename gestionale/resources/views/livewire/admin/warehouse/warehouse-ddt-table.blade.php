<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-truck-ramp-box mr-3 text-lime-600"></i>
            DDT di {{ $type === 'acquisto' ? 'Acquisto' : 'Vendita' }}
        </h1>
        <button wire:click="openCreateModal" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> Nuovo DDT
        </button>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cerca</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Numero DDT, cliente/fornitore..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                    <option value="">Tutti</option>
                    <option value="bozza">Bozza</option>
                    <option value="emesso">Emesso</option>
                </select>
            </div>
        </div>
        @if($search || $statusFilter)
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">N. DDT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">{{ $type === 'acquisto' ? 'Fornitore' : 'Cliente' }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Proprietà</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Righe</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ddts as $ddt)
                    <tr class="hover:bg-gray-50" wire:key="ddt-{{ $ddt->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <button wire:click="openDetailModal({{ $ddt->id }})" class="hover:text-lime-600 hover:underline">
                                {{ $ddt->ddt_number }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $ddt->ddt_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $ddt->entity->ragione_sociale ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $ddt->ownership->RagAbbrev ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $ddt->rows_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $ddt->status_badge_class }}">
                                {{ $ddt->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($ddt->status === 'bozza')
                                <button wire:click="issueDdt({{ $ddt->id }})"
                                    wire:confirm="Emettere questo DDT? Verranno generati i movimenti di magazzino e le giacenze verranno aggiornate."
                                    class="text-green-600 hover:text-green-800 mr-2" title="Emetti DDT">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <button wire:click="openEditModal({{ $ddt->id }})" class="text-yellow-600 hover:text-yellow-900 mr-2" title="Modifica">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $ddt->id }})" class="text-red-600 hover:text-red-900" title="Elimina">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            @else
                                <button wire:click="cancelIssue({{ $ddt->id }})"
                                    wire:confirm="Annullare l'emissione? I movimenti di magazzino generati verranno rimossi e le giacenze ripristinate."
                                    class="text-orange-600 hover:text-orange-800" title="Annulla Emissione">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-truck-ramp-box text-4xl mb-2 text-gray-300 block"></i>
                            Nessun DDT trovato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($ddts->hasPages())
    <div class="mt-4">{{ $ddts->links() }}</div>
    @endif

    <!-- ==================== MODAL CREA/MODIFICA ==================== -->
    @if($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ $editingId ? 'Modifica DDT' : 'Nuovo DDT di ' . ($type === 'acquisto' ? 'Acquisto' : 'Vendita') }}
                    </h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N. DDT <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="ddt_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('ddt_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="ddt_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('ddt_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà</label>
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="ownershipSearch"
                                    x-on:focus="open = true"
                                    placeholder="Cerca proprietà..."
                                    class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @if($selectedOwnershipId)
                                <button type="button" wire:click="clearOwnership" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                    <i class="fas fa-times-circle text-sm"></i>
                                </button>
                                @endif
                            </div>
                            <div x-show="open && @entangle('showOwnershipDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @forelse($ownershipResults as $item)
                                    <div x-on:click="open = false; @this.call('selectOwnership', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                        {{ $item->name }}
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $type === 'acquisto' ? 'Fornitore' : 'Cliente' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="entitySearch"
                                x-on:focus="open = true"
                                placeholder="Cerca {{ $type === 'acquisto' ? 'fornitore' : 'cliente' }}..."
                                class="w-full pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @if($selectedEntityId)
                            <button type="button" wire:click="clearEntity" class="absolute right-2 top-2.5 text-gray-400 hover:text-red-500">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                            @endif
                        </div>
                        <div x-show="open && @entangle('showEntityDropdown')" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                            @forelse($entityResults as $item)
                                <div x-on:click="open = false; @this.call('selectEntity', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    {{ $item->name }}
                                </div>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                            @endforelse
                        </div>
                        @error('selectedEntityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Causale</label>
                        <input type="text" wire:model="causale" placeholder="Es. Reso, Consegna materiale, ..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                    </div>

                    <!-- RIGHE -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700">Righe</label>
                            <button type="button" wire:click="addRow" class="text-xs px-3 py-1.5 bg-lime-50 hover:bg-lime-100 text-lime-700 rounded-md">
                                <i class="fas fa-plus mr-1"></i> Aggiungi riga
                            </button>
                        </div>
                        @error('rows') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

                        <div class="space-y-2">
                            @foreach($rows as $index => $row)
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50" wire:key="ddt-row-{{ $index }}">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start">

                                    {{-- Colonna Prodotto: gestita interamente dal server, senza x-show né direttive @ nel markup --}}
                                    <div class="md:col-span-4">
                                        @if($activeRowIndex === (string) $index)
                                            <div class="relative">
                                                <div class="relative">
                                                    <input type="text" wire:model.live.debounce.300ms="rowProductQuery"
                                                        autofocus
                                                        placeholder="Cerca prodotto dal catalogo..."
                                                        class="w-full pl-2 pr-8 py-1.5 text-sm border border-lime-400 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                                                    <button type="button" wire:click="deactivateRowSearch" class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                                        <i class="fas fa-times-circle text-sm"></i>
                                                    </button>
                                                </div>
                                                @if($rowProductQueryResults->isNotEmpty())
                                                <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                                    @foreach($rowProductQueryResults as $p)
                                                        <div wire:click="selectRowProduct({{ $p->id }})"
                                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                                            <div>{{ $p->name }} @if($p->sku) <span class="text-xs text-gray-400">({{ $p->sku }})</span> @endif</div>
                                                            <div class="text-xs text-gray-400">Giacenza: {{ number_format((float) $p->quantity, 2, ',', '.') }} {{ $p->unit_of_measure }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @elseif(strlen(trim($rowProductQuery)) >= 2)
                                                <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                                                    <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <button type="button" wire:click="activateRowProductSearch({{ $index }})"
                                                class="w-full text-left px-2 py-1.5 text-sm border border-gray-300 rounded-md bg-white hover:bg-gray-50 truncate">
                                                {{ $row['product_name'] ?: 'Cerca prodotto dal catalogo...' }}
                                            </button>
                                        @endif

                                        @if($row['id_product'])
                                            <p class="text-xs text-lime-600 mt-1">
                                                <i class="fas fa-link mr-1"></i>Collegato al catalogo
                                                <button type="button" wire:click="clearRowProduct({{ $index }})" class="text-gray-400 hover:text-red-500 ml-1">(scollega)</button>
                                            </p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-1">Riga libera (non tocca il magazzino)</p>
                                        @endif
                                    </div>

                                    <div class="md:col-span-4">
                                        <input type="text" wire:model="rows.{{ $index }}.description" placeholder="Descrizione"
                                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <input type="text" inputmode="decimal" wire:model="rows.{{ $index }}.quantity" placeholder="Qtà"
                                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    </div>
                                    <div class="md:col-span-1">
                                        <input type="text" wire:model="rows.{{ $index }}.unit_of_measure" placeholder="U.M."
                                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    </div>
                                    <div class="md:col-span-1 flex justify-end">
                                        <button type="button" wire:click="removeRow({{ $index }})" class="text-red-500 hover:text-red-700 p-1.5" title="Rimuovi riga">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeFormModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="save" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> Salva Bozza
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL DETTAGLIO ==================== -->
    @if($showDetailModal && $viewingDdt)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">DDT n. {{ $viewingDdt->ddt_number }}</h3>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $viewingDdt->status_badge_class }} mt-1">
                            {{ $viewingDdt->status_label }}
                        </span>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Data</label>
                            <p class="text-sm font-medium">{{ $viewingDdt->ddt_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg col-span-2">
                            <label class="text-xs text-gray-500 uppercase font-semibold">{{ $type === 'acquisto' ? 'Fornitore' : 'Cliente' }}</label>
                            <p class="text-sm font-medium">{{ $viewingDdt->entity->ragione_sociale ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg">
                            <label class="text-xs text-gray-500 uppercase font-semibold">Proprietà</label>
                            <p class="text-sm font-medium">{{ $viewingDdt->ownership->RagAbbrev ?? '-' }}</p>
                        </div>
                    </div>

                    @if($viewingDdt->causale)
                    <div class="bg-gray-50 p-2 rounded-lg">
                        <label class="text-xs text-gray-500 uppercase font-semibold">Causale</label>
                        <p class="text-sm">{{ $viewingDdt->causale }}</p>
                    </div>
                    @endif

                    <div class="border rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium">Prodotto</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium">Quantità</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium">Nota</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($viewingDdt->rows as $row)
                                <tr>
                                    <td class="px-3 py-2 text-sm">
                                        {{ $row->description }}
                                        @if($row->id_product)
                                            <span class="text-xs text-lime-600 ml-1"><i class="fas fa-link"></i></span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right">{{ number_format((float) $row->quantity, 2, ',', '.') }} {{ $row->unit_of_measure }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $row->note ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-xs text-gray-400 border-t pt-3">
                        Inserito da {{ $viewingDdt->creator->name ?? 'Sistema' }} il {{ $viewingDdt->created_at?->format('d/m/Y H:i') }}
                        @if($viewingDdt->isIssued())
                            — Emesso il {{ $viewingDdt->issued_at?->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="flex justify-end px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE -->
    @if($deletingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
            <p class="text-sm text-gray-500 mb-4">Il DDT (bozza) verrà eliminato definitivamente. Continuare?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
            </div>
        </div>
    </div>
    @endif
</div>