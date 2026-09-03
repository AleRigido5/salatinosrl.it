<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-book mr-3 text-lime-600"></i>
            Prima Nota - Scritture Contabili
        </h1>
        <div class="flex gap-2">
            @if($canCreate)
            <button wire:click="openCreateModal"
                class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200" title="Nuova Scrittura [ALT + N]">
                <i class="fas fa-plus"></i>
            </button>
            <button wire:click="openImportModal"
                class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200" title="Importa CSV">
                <i class="fas fa-file-csv"></i>
            </button>
            @endif

            <!-- Pulsante Cestino -->
            <div class="relative group">
                <button wire:click="openTrashModal"
                    class="relative px-5 py-2.5 rounded-lg shadow-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-all duration-200"
                    title="Cestino">
                    <i class="fas fa-trash-alt"></i>
                    @if($trashCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-md">
                        {{ $trashCount }}
                    </span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- Card Filtri -->
    <div class="bg-white rounded-lg shadow mb-4 border border-gray-200">

        <!-- RIGA SUPERIORE: Date Range Filter -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-end gap-6 mb-2">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                    <input type="radio" wire:model.live="dateFilterMode" value="entry_date"
                        class="text-lime-600 focus:ring-lime-500">
                    Data Pagamento
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                    <input type="radio" wire:model.live="dateFilterMode" value="registration_date"
                        class="text-lime-600 focus:ring-lime-500">
                    Data Registrazione Pagamento
                </label>
            </div>
            @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
        </div>

        <!-- RIGA INFERIORE: Filtri -->
        <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-3">

                <!-- Ricerca -->
                <div class="xl:col-span-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ricerca</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Cerca in descrizione..."
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    </div>
                </div>

                <!-- Autocomplete Proprietà -->
                <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                    <div class="relative">
                        <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text"
                            id="ownership_input"
                            wire:model.live.debounce.300ms="ownershipSearch"
                            x-on:focus="open = true"
                            x-on:input="open = true; @this.set('ownershipSearch', $event.target.value)"
                            placeholder="Cerca proprietà..."
                            class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                            autocomplete="off">
                        @if($selectedOwnershipId)
                            <button type="button" wire:click="clearOwnership"
                                x-on:click="document.getElementById('ownership_input').value = ''"
                                class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                        @endif
                    </div>
                    <div x-show="open && @entangle('showOwnershipDropdown')"
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @if($ownershipResults && $ownershipResults->count() > 0)
                            @foreach($ownershipResults as $item)
                                <div x-on:click="
                                        open = false;
                                        document.getElementById('ownership_input').value = '{{ addslashes($item->RagAbbrev ?? $item->Rag_Soc_intest) }}';
                                        @this.set('ownershipSearch', '{{ addslashes($item->RagAbbrev ?? $item->Rag_Soc_intest) }}');
                                        @this.set('selectedOwnershipId', '{{ $item->id_proprieta }}');
                                        @this.set('selectedOwnershipName', '{{ addslashes($item->RagAbbrev ?? $item->Rag_Soc_intest) }}');
                                        @this.set('showOwnershipDropdown', false);
                                        @this.call('resetPage');
                                    "
                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-800">{{ $item->RagAbbrev ?? $item->Rag_Soc_intest }}</div>
                                    @if($item->RagSocialePr)
                                        <div class="text-xs text-gray-500">{{ $item->RagSocialePr }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                        @endif
                    </div>
                </div>

                <!-- Cliente/Fornitore Autocomplete -->
                <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cliente / Fornitore</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text"
                            id="entity_input"
                            wire:model.live.debounce.300ms="entitySearch"
                            x-on:focus="open = true"
                            x-on:input="open = true; @this.set('entitySearch', $event.target.value)"
                            placeholder="Cerca cliente/fornitore..."
                            class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                            autocomplete="off">
                        @if($entityFilter)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                            <i class="fas fa-users mr-1"></i> {{ $entityName }}
                            <button wire:click="clearEntity" class="ml-1 hover:text-lime-900">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </span>
                        @endif
                    </div>

                    <div x-show="open && @entangle('showEntityDropdown')"
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @if($filteredEntities && $filteredEntities->count() > 0)
                            @foreach($filteredEntities as $entity)
                            @php
                                $entityName = addslashes($entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome));
                            @endphp
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('entity_input').value = '{{ $entityName }}';
                                    @this.set('entitySearch', '{{ $entityName }}');
                                    @this.set('entityFilter', {{ $entity->id_cliente }});
                                    @this.set('entityName', '{{ $entityName }}');
                                    @this.set('showEntityDropdown', false);
                                    @this.call('resetPage');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</div>
                                @if($entity->partita_iva)
                                <div class="text-xs text-gray-500">P.IVA: {{ $entity->partita_iva }}</div>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <div class="px-3 py-2 text-sm text-gray-500 text-center">
                                Nessun risultato trovato
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                    <select wire:model.live="type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        <option value="entrata">Entrata</option>
                        <option value="uscita">Uscita</option>
                    </select>
                </div>

                <!-- Stato Pagamento -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Stato Pag.</label>
                    <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($paymentStatuses as $statusOption)
                            <option value="{{ $statusOption->valore }}" title="{{ $statusOption->descrizione }}">
                                {{ ucfirst(strtolower($statusOption->valore)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Conto Bancario -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Conto Bancario</label>
                    <select wire:model.live="bankAccountId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Metodo Pagamento -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Metodo Pag.</label>
                    <select wire:model.live="paymentMethodId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Per pagina</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="100000">Tutti</option>
                        <option value="200">200</option>
                        <option value="100">100</option>
                    </select>
                </div>

            </div>

            <!-- Active Filters Tags -->
            @if($search || $type || $statusFilter || $bankAccountId || $paymentMethodId || $dateFrom || $dateTo || $entityFilter)
            <div class="mt-4 pt-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>

                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1 text-xs"></i>
                    {{ $dateFilterMode === 'registration_date' ? 'Registrazione: ' : '' }}{{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="clearDates" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Ricerca: "{{ $search }}"
                    <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($type)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Tipo: {{ $type === 'entrata' ? 'Entrata' : 'Uscita' }}
                    <button wire:click="$set('type', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($statusFilter)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Stato: {{ $statusFilter }}
                    <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($bankAccountId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Conto Bancario
                    <button wire:click="$set('bankAccountId', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                @if($paymentMethodId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    Metodo Pag.
                    <button wire:click="$set('paymentMethodId', '')" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif

                <button wire:click="resetFilters" class="text-xs text-red-500 hover:text-red-700">
                    <i class="fas fa-trash-alt mr-1"></i> Rimuovi tutti
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Card Totali -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 uppercase font-semibold">Totale Entrate</p>
                    <p class="text-2xl font-bold text-green-700">{{ number_format($totalEntrate, 2, ',', '.') }} €</p>
                </div>
                <i class="fas fa-arrow-down text-green-500 text-3xl"></i>
            </div>
        </div>
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 uppercase font-semibold">Totale Uscite</p>
                    <p class="text-2xl font-bold text-red-700">{{ number_format($totalUscite, 2, ',', '.') }} €</p>
                </div>
                <i class="fas fa-arrow-up text-red-500 text-3xl"></i>
            </div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-600 uppercase font-semibold">Saldo</p>
                    <p class="text-2xl font-bold {{ $saldo >= 0 ? 'text-blue-700' : 'text-red-700' }}">
                        {{ number_format($saldo, 2, ',', '.') }} €
                    </p>
                </div>
                <i class="fas fa-balance-scale text-blue-500 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer" wire:click="sortBy('entry_date')">
                            Data @if($sortField === 'entry_date')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Descrizione</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Entità</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Conto Bancario</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer" wire:click="sortBy('amount')">
                            Importo @if($sortField === 'amount')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Metodo Pag.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50" wire:key="entry-{{ $entry->id }}">
                        <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $entry->entry_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $entry->description }}">{{ Str::limit($entry->description, 50) }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($entry->entity)
                                <span class="text-gray-900">{{ $entry->entity_name }}</span>
                                @if($entry->linked_invoice_details)
                                    <br><small class="text-gray-400 text-xs">Fattura: {{ $entry->linked_invoice_details['n_invoice'] ?? '-' }}</small>
                                @endif
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $entry->type_badge_class }}">
                                {{ $entry->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $entry->bankAccount->name ?? '-' }}<br><small class="text-gray-400">{{ $entry->bankAccount->n_conto ?? '' }}</small></td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($entry->amount, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3 text-sm">{{ $entry->paymentMethod->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium cursor-help {{ $entry->status_badge_class }}"
                                  title="{{ $entry->status_tooltip }}">
                                {{ $entry->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @if($canView)
                                <button wire:click="viewEntry({{ $entry->id }})" class="text-blue-600 hover:text-blue-900" title="Dettagli">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                @endif
                                @if($canEdit)
                                <button wire:click="openEditModal({{ $entry->id }})" class="text-yellow-600 hover:text-yellow-900" title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif
                                @if($canDelete)
                                <button wire:click="confirmDelete({{ $entry->id }})" class="text-red-600 hover:text-red-900" title="Elimina">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">Nessuna scrittura contabile trovata</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($perPage != 10000 && $entries instanceof \Illuminate\Pagination\AbstractPaginator && $entries->hasPages())
    <div class="mt-4">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $entries->firstItem() ?? 0 }} - {{ $entries->lastItem() ?? 0 }} di {{ $entries->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $entries->links() }}
        </div>
    </div>
    @endif

        <!-- MODAL CREAZIONE/MODIFICA -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeModal()"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $isEditing ? 'Modifica Scrittura Contabile' : 'Nuova Scrittura Contabile' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit.prevent="save">
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                        <!-- Descrizione -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione <span class="text-red-500">*</span></label>
                            <textarea wire:model="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500" placeholder="Inserisci una descrizione..."></textarea>
                            @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Data, Tipo, Stato -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="entry_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @error('entry_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select wire:model.live="type_value" class="w-full pl-9 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                        <option value="">Seleziona tipo</option>
                                        <option value="entrata">Entrata</option>
                                        <option value="uscita">Uscita</option>
                                    </select>
                                    @if($type_value === 'entrata')
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <i class="fas fa-arrow-down text-green-500"></i>
                                        </div>
                                    @elseif($type_value === 'uscita')
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <i class="fas fa-arrow-up text-red-500"></i>
                                        </div>
                                    @endif
                                </div>
                                @error('type_value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stato <span class="text-red-500">*</span></label>
                                <select wire:model="status_value" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    @foreach($paymentStatuses as $statusOption)
                                        <option value="{{ $statusOption->valore }}" title="{{ $statusOption->descrizione }}">
                                            {{ ucfirst(strtolower($statusOption->valore)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Conto Bancario e Metodo Pagamento -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Conto Bancario</label>
                                <select wire:model="bank_account_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona conto...</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Metodo Pagamento</label>
                                <select wire:model="id_payments_methods" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona metodo...</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Cliente/Fornitore e Centro di Costo (Autocomplete) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Fornitore</label>
                                <div class="relative">
                                    <input type="text"
                                        id="form_entity_input"
                                        wire:model.live.debounce.300ms="formEntitySearch"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Digita almeno 2 caratteri..."
                                        class="w-full pr-16 pl-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    @if($formEntityId)
                                    <span class="absolute right-8 top-2 text-xs text-gray-400">ID: {{ $formEntityId }}</span>
                                    <button type="button"
                                        wire:click="clearFormEntity"
                                        x-on:click="document.getElementById('form_entity_input').value = ''; open = false"
                                        class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>

                                <div x-show="open && @entangle('showFormEntityDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($formEntityResults && $formEntityResults->count() > 0)
                                        @foreach($formEntityResults as $item)
                                        @php
                                            $itemName = addslashes($item->ragione_sociale ?: trim($item->nome . ' ' . $item->cognome));
                                        @endphp
                                        <div
                                            wire:click="selectFormEntity({{ $item->id_cliente }}, '{{ $itemName }}')"
                                            x-on:click="document.getElementById('form_entity_input').value = '{{ $itemName }}'; open = false"
                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                            <div class="font-medium text-gray-800">{{ $item->ragione_sociale ?: trim($item->nome . ' ' . $item->cognome) }}</div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                                    @endif
                                </div>
                                @error('formEntityId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo</label>
                                <div class="relative">
                                    <input type="text"
                                        id="cost_center_input"
                                        wire:model.live.debounce.300ms="costCenterSearch"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Digita almeno 2 caratteri..."
                                        class="w-full pr-16 pl-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    @if($costCenterId)
                                    <span class="absolute right-8 top-2 text-xs text-gray-400">ID: {{ $costCenterId }}</span>
                                    <button type="button"
                                        wire:click="clearCostCenter"
                                        x-on:click="document.getElementById('cost_center_input').value = ''; open = false"
                                        class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>

                                <div x-show="open && @entangle('showCostCenterDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($costCenterResults && $costCenterResults->count() > 0)
                                        @foreach($costCenterResults as $item)
                                        <div
                                            wire:click="selectCostCenter({{ $item->id }}, '{{ addslashes($item->Nome) }}')"
                                            x-on:click="document.getElementById('cost_center_input').value = '{{ addslashes($item->Nome) }}'; open = false"
                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                            <div class="font-medium text-gray-800">{{ $item->Nome }}</div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                                    @endif
                                </div>
                                @error('costCenterId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Importo (col-8 input + col-4 totale) -->
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-8">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Importo (€) <span class="text-red-500">*</span></label>
                                <input type="number"
                                    step="0.01"
                                    wire:model.live="amount"
                                    wire:blur="formatAmount"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                    placeholder="0.00">
                                @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-4 bg-gray-100 p-3 rounded-md text-right">
                                <p class="text-sm text-gray-600">Totale</p>
                                <p class="text-xl font-bold {{ $isAmountChanged ? 'text-orange-600' : 'text-lime-600' }}">
                                    {{ number_format((float)$amount, 2, ',', '.') }} €
                                </p>
                                @if($isEditing && $isAmountChanged)
                                    <p class="text-xs {{ $amountDifference > 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                                        @if($amountDifference > 0)
                                            ↑ +{{ number_format($amountDifference, 2, ',', '.') }} €
                                        @else
                                            ↓ {{ number_format($amountDifference, 2, ',', '.') }} €
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            Annulla
                        </button>
                        <button type="submit" class="px-4 py-2 bg-lime-500 hover:bg-lime-600 text-white rounded-md transition-colors">
                            <i class="fas fa-save mr-2"></i> {{ $isEditing ? 'Aggiorna' : 'Salva' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

        <!-- MODAL IMPORT CSV -->
    @if($showImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeImportModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeImportModal()"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-file-csv mr-2 text-blue-600"></i> Importa Scritture da CSV
                        </h3>
                        <button type="button" wire:click="closeImportModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 space-y-4 max-h-[75vh] overflow-y-auto">

                    @if(!$importDone)
                        <!-- Selettore File -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">File CSV <span class="text-red-500">*</span></label>
                            <input type="file" wire:model="importFile" accept=".csv,.txt"
                                class="w-full text-sm border border-gray-300 rounded-md file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100">
                            <p class="text-xs text-gray-500 mt-1">
                                Colonne attese: <code>entry_date, description, type, id_payments_methods, bank_account_id, amount</code>
                            </p>
                            <div wire:loading wire:target="importFile" class="text-xs text-lime-600 mt-1">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Lettura del file in corso...
                            </div>
                            @error('importFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Campi globali applicati a tutte le righe -->
                        <div class="grid grid-cols-3 gap-4 p-3 bg-gray-50 rounded-lg">
                            <!-- Cliente/Fornitore (opzionale) -->
                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente / Fornitore</label>
                                <div class="relative">
                                    <input type="text"
                                        id="import_entity_input"
                                        wire:model.live.debounce.300ms="importEntitySearch"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Digita almeno 2 caratteri..."
                                        class="w-full pr-8 pl-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    @if($importEntityId)
                                    <button type="button"
                                        wire:click="clearImportEntity"
                                        x-on:click="document.getElementById('import_entity_input').value = ''; open = false"
                                        class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>
                                <div x-show="open && @entangle('showImportEntityDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($importEntityResults && $importEntityResults->count() > 0)
                                        @foreach($importEntityResults as $item)
                                        @php $itemName = addslashes($item->ragione_sociale ?: trim($item->nome . ' ' . $item->cognome)); @endphp
                                        <div
                                            wire:click="selectImportEntity({{ $item->id_cliente }}, '{{ $itemName }}')"
                                            x-on:click="document.getElementById('import_entity_input').value = '{{ $itemName }}'; open = false"
                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                            {{ $item->ragione_sociale ?: trim($item->nome . ' ' . $item->cognome) }}
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Centro di Costo -->
                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo</label>
                                <div class="relative">
                                    <input type="text"
                                        id="import_cost_center_input"
                                        wire:model.live.debounce.300ms="importCostCenterSearch"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Digita almeno 2 caratteri..."
                                        class="w-full pr-8 pl-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    @if($importCostCenterId)
                                    <button type="button"
                                        wire:click="clearImportCostCenter"
                                        x-on:click="document.getElementById('import_cost_center_input').value = ''; open = false"
                                        class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times-circle text-sm"></i>
                                    </button>
                                    @endif
                                </div>
                                <div x-show="open && @entangle('showImportCostCenterDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($importCostCenterResults && $importCostCenterResults->count() > 0)
                                        @foreach($importCostCenterResults as $item)
                                        <div
                                            wire:click="selectImportCostCenter({{ $item->id }}, '{{ addslashes($item->Nome) }}')"
                                            x-on:click="document.getElementById('import_cost_center_input').value = '{{ addslashes($item->Nome) }}'; open = false"
                                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                            {{ $item->Nome }}
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Stato -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stato <span class="text-red-500">*</span></label>
                                <select wire:model="importStatus" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    @foreach($paymentStatuses as $statusOption)
                                        <option value="{{ $statusOption->valore }}" title="{{ $statusOption->descrizione }}">
                                            {{ ucfirst(strtolower($statusOption->valore)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Anteprima -->
                        @if(!empty($importPreview))
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Anteprima ({{ count($importPreview) }} righe —
                                    <span class="text-green-600">{{ $importValidRowsCount }} valide</span>
                                    @if(count($importPreview) - $importValidRowsCount > 0)
                                        , <span class="text-red-600">{{ count($importPreview) - $importValidRowsCount }} con errori</span>
                                    @endif
                                    )
                                </label>
                            </div>
                            <div class="border rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-2 py-1 text-left">Riga</th>
                                            <th class="px-2 py-1 text-left">Data</th>
                                            <th class="px-2 py-1 text-left">Descrizione</th>
                                            <th class="px-2 py-1 text-left">Tipo</th>
                                            <th class="px-2 py-1 text-right">Importo</th>
                                            <th class="px-2 py-1 text-left">Esito</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($importPreview as $row)
                                        <tr class="{{ $row['is_valid'] ? '' : 'bg-red-50' }}">
                                            <td class="px-2 py-1">{{ $row['row_number'] }}</td>
                                            <td class="px-2 py-1 whitespace-nowrap">{{ $row['entry_date'] }}</td>
                                            <td class="px-2 py-1 max-w-xs truncate" title="{{ $row['description'] }}">{{ $row['description'] }}</td>
                                            <td class="px-2 py-1">{{ $row['type'] }}</td>
                                            <td class="px-2 py-1 text-right">{{ $row['amount'] !== null ? number_format($row['amount'], 2, ',', '.') . ' €' : '-' }}</td>
                                            <td class="px-2 py-1">
                                                @if($row['is_valid'])
                                                    <span class="text-green-600"><i class="fas fa-check-circle"></i> OK</span>
                                                @else
                                                    <span class="text-red-600" title="{{ implode(', ', $row['errors']) }}">
                                                        <i class="fas fa-exclamation-circle"></i> {{ implode(', ', $row['errors']) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    @else
                        <!-- Esito import -->
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                            <p class="text-lg font-medium text-gray-800">{{ $importedCount }} scritture importate con successo</p>
                        </div>
                    @endif

                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    @if(!$importDone)
                        <button type="button" wire:click="closeImportModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            Annulla
                        </button>
                        <button type="button" wire:click="confirmImport"
                            {{ empty($importPreview) || $importValidRowsCount === 0 ? 'disabled' : '' }}
                            class="px-4 py-2 bg-lime-500 hover:bg-lime-600 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-upload mr-2"></i> Importa {{ $importValidRowsCount }} righe
                        </button>
                    @else
                        <button type="button" wire:click="closeImportModal" class="px-4 py-2 bg-lime-500 hover:bg-lime-600 text-white rounded-md transition-colors">
                            Chiudi
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL VISUALIZZAZIONE DETTAGLIO -->
    @if($showViewModal && $viewingEntry)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeViewModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeViewModal()"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Dettaglio Scrittura Contabile</h3>
                        <button type="button" wire:click="closeViewModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Data</label>
                            <p class="font-medium">{{ $viewingEntry->entry_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Tipo</label>
                            <p><span class="px-2 py-1 rounded-full text-xs font-medium {{ $viewingEntry->type_badge_class }}">{{ $viewingEntry->type_label }}</span></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Descrizione</label>
                        <p class="whitespace-pre-wrap">{{ $viewingEntry->description }}</p>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Conto Bancario</label>
                        <p class="font-medium">{{ $viewingEntry->bankAccount->name ?? '-' }} - {{ $viewingEntry->bankAccount->n_conto ?? '' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Importo</label>
                            <p class="text-xl font-bold text-lime-600">{{ number_format($viewingEntry->amount, 2, ',', '.') }} €</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs text-gray-500 uppercase">Metodo Pagamento</label>
                            <p>{{ $viewingEntry->paymentMethod->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Stato</label>
                        <p>
                            <span class="px-2 py-1 rounded-full text-xs font-medium cursor-help {{ $viewingEntry->status_badge_class }}"
                                  title="{{ $viewingEntry->status_tooltip }}">
                                {{ $viewingEntry->status_label }}
                            </span>
                        </p>
                    </div>

                    @if($viewingEntry->invoice)
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <label class="block text-xs text-gray-500 uppercase">Fattura Associata</label>
                        <p>N. {{ $viewingEntry->invoice->n_invoice }} del {{ $viewingEntry->invoice->data_invoice->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" wire:click="closeViewModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE -->
    @if($showDeleteModal && $entryToDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="show = false; $wire.cancelDelete()">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare questa scrittura contabile?<br>
                    <span class="text-xs text-gray-400">L'operazione è irreversibile.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                        Annulla
                    </button>
                    <button wire:click="deleteEntry" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CESTINO -->
    @if($showTrashModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto" x-on:click.away="show = false; $wire.closeTrashModal()" x-transition.scale.origin.top>
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-trash-alt mr-2 text-red-600"></i>
                    Cestino - Scritture Contabili Eliminate
                </h2>
                <button wire:click="closeTrashModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" wire:model.live="trashSearch"
                        placeholder="Cerca per descrizione o importo..."
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('entry_date')">
                                Data @if($trashSortField === 'entry_date')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrizione</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Importo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('deleted_at')">
                                Data eliminazione @if($trashSortField === 'deleted_at')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trashedEntries as $entry)
                        <tr wire:key="trash-{{ $entry->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $entry->entry_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate" title="{{ $entry->description }}">{{ Str::limit($entry->description, 50) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $entry->type === 'entrata' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $entry->type === 'entrata' ? 'Entrata' : 'Uscita' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">{{ number_format($entry->amount, 2, ',', '.') }} €</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->deleted_at ? $entry->deleted_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3 justify-center">
                                    <!-- Bottone RIPRISTINA -->
                                    <button wire:click="restoreFromTrash({{ $entry->id }})"
                                        class="text-green-600 hover:text-green-900 transition-colors"
                                        title="Ripristina">
                                        <i class="fas fa-trash-restore text-lg"></i>
                                    </button>
                                    <!-- Bottone ELIMINA DEFINITIVAMENTE -->
                                    <button wire:click="forceDeleteFromTrash({{ $entry->id }})"
                                        onclick="return confirm('Eliminazione definitiva? Operazione non reversibile.')"
                                        class="text-red-600 hover:text-red-900 transition-colors"
                                        title="Elimina definitivamente">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-trash-alt text-gray-400 text-5xl mb-2"></i>
                                <p class="text-sm text-gray-500 mt-2">Il cestino è vuoto</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trashedEntries->hasPages())
            <div class="mt-4">
                <div class="text-sm text-gray-500 mb-2">{{ $trashedEntries->firstItem() }} - {{ $trashedEntries->lastItem() }} di {{ $trashedEntries->total() }} elementi</div>
                <div class="flex justify-center">{{ $trashedEntries->links() }}</div>
            </div>
            @endif

            <div class="flex justify-end mt-6 pt-4 border-t">
                <button wire:click="closeTrashModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <style scoped>
        nav[role="navigation"] div.flex-1 { display: none !important; }
        nav[role="navigation"] .relative.z-0 { justify-content: center !important; display: flex !important; }
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #84cc16 !important;
            border-color: #84cc16 !important;
            color: white !important;
        }
        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important;
            border-color: #d1d5db !important;
        }
        nav[role="navigation"] p.text-sm { display: none !important; }
        nav[role="navigation"] > div:first-child { justify-content: center !important; }
        nav[role="navigation"] > div:first-child > div:first-child { display: none !important; }
    </style>
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