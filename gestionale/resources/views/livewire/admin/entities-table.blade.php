<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-2xl shadow-sm mb-6 p-5 border border-gray-100" wire:key="filters-{{ $activeSearch }}-{{ $activeTypeFilter }}-{{ $activeStatusFilter }}-{{ $activeRatingFilter }}">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="relative md:col-span-2" x-data="{ open: false }" x-on:click.away="open = false">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                <input type="text"
                    id="entities_search_input"
                    wire:model.live.debounce.300ms="tempSearch"
                    x-on:focus="open = true"
                    x-on:keydown="open = true"
                    placeholder="Cerca per: P.IVA, Ragione Sociale, Nome, Cognome, Persona Riferimento, Città, Telefono, Email..."
                    autocomplete="off"
                    class="w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 placeholder-gray-400 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-lime-500/40 focus:border-lime-400 focus:bg-white transition-all duration-150">

                @if($tempSearch)
                <button type="button"
                    wire:click="clearSearch"
                    x-on:click="document.getElementById('entities_search_input').value = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
                @endif

                <div x-show="open && @entangle('showSearchDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-72 overflow-y-auto">
                    @if($searchResults && $searchResults->count() > 0)
                        @foreach($searchResults as $result)
                        <div wire:key="search-result-{{ $result->id_cliente }}"
                            x-on:click="open = false; @this.call('selectSearchResult', {{ $result->id_cliente }}, '{{ addslashes($result->full_name) }}')"
                            class="px-4 py-2.5 hover:bg-lime-50 cursor-pointer border-b border-gray-100 last:border-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-800 text-sm truncate">{{ $result->full_name }}</div>
                                @if($result->partita_iva)
                                <div class="text-xs text-gray-500">P.IVA: {{ $result->partita_iva }}</div>
                                @endif
                            </div>
                            <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                @if($result->entity_type == 'cliente') bg-lime-100 text-lime-800
                                @elseif($result->entity_type == 'fornitore') bg-blue-100 text-blue-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ $entityTypes[$result->entity_type] ?? $result->entity_type }}
                            </span>
                        </div>
                        @endforeach
                    @elseif(strlen($tempSearch) >= 2)
                        <div class="px-4 py-3 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>

            <div class="relative">
                <i class="fas fa-tags absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                <select wire:model="tempTypeFilter"
                    class="w-full appearance-none pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 cursor-pointer hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-lime-500/40 focus:border-lime-400 focus:bg-white transition-all duration-150">
                    <option value="">Tutti i tipi</option>
                    @foreach($entityTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <div class="relative">
                <i class="fas fa-star absolute left-3.5 top-1/2 -translate-y-1/2 text-amber-400 text-sm pointer-events-none"></i>
                <select wire:model="tempRatingFilter"
                    class="w-full appearance-none pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 cursor-pointer hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-lime-500/40 focus:border-lime-400 focus:bg-white transition-all duration-150">
                    <option value="">Tutte le valutazioni</option>
                    <option value="0">0 stelle · Bannato</option>
                    <option value="1">1 stella</option>
                    <option value="2">2 stelle</option>
                    <option value="3">3 stelle</option>
                    <option value="4">4 stelle</option>
                    <option value="5">5 stelle · Eccellente</option>
                </select>
                <i class="fas fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <div class="flex gap-2">
                <div class="relative flex-1">
                    <i class="fas fa-circle-check absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <select wire:model="tempStatusFilter"
                        class="w-full appearance-none pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 cursor-pointer hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-lime-500/40 focus:border-lime-400 focus:bg-white transition-all duration-150">
                        <option value="">Tutti gli stati</option>
                        <option value="active">Attivi</option>
                        <option value="inactive">Disattivi</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <button wire:click="applyFilters"
                        class="flex items-center gap-2 bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md hover:from-lime-600 hover:to-lime-700 active:scale-[0.98] transition-all duration-150 whitespace-nowrap">
                    <i class="fas fa-search text-sm"></i>
                    <span class="text-sm font-medium">Applica</span>
                </button>
            </div>
        </div>

        <div class="flex justify-between items-center mt-3">
            @if($activeSearch || $activeTypeFilter || $activeStatusFilter || $activeRatingFilter !== '')
            <button wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-800 transition-colors flex items-center gap-1.5">
                <i class="fas fa-rotate-left text-xs"></i>
                Resetta filtri
            </button>
            @endif
        </div>

        @if($activeSearch || $activeTypeFilter || $activeStatusFilter || $activeRatingFilter !== '')
        <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Filtri attivi</span>
            @if($activeSearch)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-lime-50 text-lime-700 border border-lime-200">
                <i class="fas fa-search text-[10px]"></i>
                "{{ $activeSearch }}"
                <button wire:click="removeFilter('search')" class="hover:text-lime-900">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
            @endif
            @if($activeTypeFilter)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-lime-50 text-lime-700 border border-lime-200">
                <i class="fas fa-tags text-[10px]"></i>
                {{ $entityTypes[$activeTypeFilter] ?? $activeTypeFilter }}
                <button wire:click="removeFilter('type')" class="hover:text-lime-900">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
            @endif
            @if($activeStatusFilter)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-lime-50 text-lime-700 border border-lime-200">
                <i class="fas fa-circle-check text-[10px]"></i>
                {{ $activeStatusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="removeFilter('status')" class="hover:text-lime-900">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
            @endif
            @if($activeRatingFilter !== '')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium {{ $activeRatingFilter === '0' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-lime-50 text-lime-700 border border-lime-200' }}">
                <i class="fas fa-star text-[10px]"></i>
                {{ $activeRatingFilter }} {{ $activeRatingFilter == 1 ? 'stella' : 'stelle' }}{{ $activeRatingFilter == '0' ? ' · Bannati' : '' }}
                <button wire:click="removeFilter('rating')" class="hover:text-lime-900">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Clienti / Fornitori -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('ragione_sociale')">
                            <div class="flex items-center space-x-1">
                                <span>Cliente / Fornitore</span>
                                @if($sortField === 'ragione_sociale')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('entity_type')">
                            <div class="flex items-center space-x-1">
                                <span>Tipo</span>
                                @if($sortField === 'entity_type')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contatti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('partita_iva')">
                            <div class="flex items-center space-x-1">
                                <span>P.IVA / CF</span>
                                @if($sortField === 'partita_iva')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('valid')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'valid')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('rating')">
                            <div class="flex items-center space-x-1">
                                <span>Valutazione</span>
                                @if($sortField === 'rating')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('created_at')">
                            <div class="flex items-center space-x-1">
                                <span>Data inserimento</span>
                                @if($sortField === 'created_at')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($entities as $entity)
                    @php
                        $phone = $entity->contacts->firstWhere('id_settings', 1);
                        $mobile = $entity->contacts->firstWhere('id_settings', 2);
                        $email = $entity->contacts->firstWhere('id_settings', 4);

                        if(!$email) {
                            $email = $entity->contacts->first(function($c) {
                                return filter_var($c->valore, FILTER_VALIDATE_EMAIL) !== false;
                            });
                        }

                        $emailValue = $email ? $email->valore : ($entity->email ?? null);

                        // Riga "bannata": valutazione a 0 stelle
                        $isBanned = (int) ($entity->rating ?? 3) === 0;
                        $rowTextClass = $isBanned ? 'text-white' : 'text-gray-900';
                        $rowMutedTextClass = $isBanned ? 'text-red-100' : 'text-gray-500';
                        $rowIconMutedClass = $isBanned ? 'text-red-200' : 'text-gray-400';
                        $actionIconClass = $isBanned ? 'text-white hover:text-red-100' : null;
                    @endphp
                    <tr wire:key="entity-{{ $entity->id_cliente }}" class="transition-colors duration-150 {{ $isBanned ? 'bg-red-600 hover:bg-red-700' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center
                                    @if($entity->entity_type == 'cliente') bg-lime-100
                                    @elseif($entity->entity_type == 'fornitore') bg-blue-100
                                    @else bg-purple-100
                                    @endif">

                                    @if($entity->entity_type == 'cliente')
                                        <i class="fas fa-user text-lime-600 text-lg"></i>
                                    @elseif($entity->entity_type == 'fornitore')
                                        <i class="fas fa-truck text-blue-600 text-lg"></i>
                                    @else
                                        <i class="fas fa-handshake text-purple-600 text-lg"></i>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium {{ $rowTextClass }}">
                                        {{ $entity->full_name }}
                                    </div>
                                    @if($entity->persona_riferimento)
                                    <div class="text-xs {{ $rowMutedTextClass }}">
                                        <i class="fas fa-user-tag mr-1 {{ $rowIconMutedClass }}"></i>
                                        {{ $entity->persona_riferimento }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($entity->entity_type == 'cliente') bg-lime-100 text-lime-800
                                @elseif($entity->entity_type == 'fornitore') bg-blue-100 text-blue-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ $entityTypes[$entity->entity_type] ?? $entity->entity_type }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-sm {{ $rowMutedTextClass }}">
                            <div class="flex flex-col space-y-1">
                                @if($phone)
                                    <div class="flex items-center">
                                        <i class="fas fa-phone {{ $rowIconMutedClass }} mr-2"></i>
                                        <span class="truncate">{{ $phone->valore }}</span>
                                    </div>
                                @endif

                                @if($mobile)
                                    <div class="flex items-center">
                                        <i class="fas fa-mobile-alt {{ $rowIconMutedClass }} mr-2"></i>
                                        <span class="truncate">{{ $mobile->valore }}</span>
                                    </div>
                                @endif

                                @if($emailValue)
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope {{ $rowIconMutedClass }} mr-2"></i>
                                        <span class="truncate">{{ Str::limit($emailValue, 30) }}</span>
                                    </div>
                                @endif

                                @if(!$phone && !$mobile && !$emailValue)
                                    <span class="{{ $isBanned ? 'text-red-100' : 'text-gray-400' }} italic text-xs">Nessun contatto</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm {{ $rowMutedTextClass }}">
                            <div class="flex flex-col space-y-1">
                                @if($entity->partita_iva)
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs">{{ $entity->partita_iva }}</span>
                                    </div>
                                @endif
                                @if($entity->codice_fiscale && $entity->codice_fiscale != $entity->partita_iva)
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs">{{ $entity->codice_fiscale }}</span>
                                    </div>
                                @endif
                                @if(!$entity->partita_iva && !$entity->codice_fiscale)
                                    <span class="{{ $isBanned ? 'text-red-100' : 'text-gray-400' }} italic text-xs">-</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!$entity->trashed())
                            <button wire:click="toggleStatus({{ $entity->id_cliente }})"
                                    wire:key="toggle-{{ $entity->id_cliente }}"
                                    class="px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200
                                        {{ $entity->valid ? 'bg-lime-100 text-lime-800 hover:bg-lime-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $entity->valid ? 'Attivo' : 'Disattivo' }}
                            </button>
                            @else
                            <span class="px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-500">
                                <i class="fas fa-trash-alt mr-1"></i> Cancellato
                            </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @php
                                    $entityRating = $entity->rating ?? 3;
                                    $ratingColorClass = $entityRating == 0 ? 'text-red-500' : ($entityRating == 5 ? 'text-green-500' : 'text-yellow-400');
                                    $emptyStarClass = $isBanned ? 'text-white/50' : 'text-gray-300';
                                @endphp
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star text-sm {{ $i <= $entityRating ? $ratingColorClass : $emptyStarClass }}"></i>
                                @endfor
                                @if($isBanned)
                                    <span class="ml-2 text-xs font-semibold text-white uppercase">Bannato</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm {{ $rowMutedTextClass }} whitespace-nowrap">
                            {{ $entity->created_at ? $entity->created_at->format('d/m/Y') : ($entity->data_inserimento ? date('d/m/Y', strtotime($entity->data_inserimento)) : '-') }}
                        </td>

                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                {{-- Icona Comunicazioni --}}
                                <a href="{{ route('admin.entities.communications.index', $entity->id_cliente) }}"
                                class="{{ $actionIconClass ?? 'text-indigo-600 hover:text-indigo-900' }} transition-colors"
                                title="Gestione Comunicazioni">
                                    <i class="fa-solid fa-envelope text-lg"></i>
                                </a>

                                <a href="{{ route('admin.entities.account-statement', $entity->id_cliente) }}"
                                class="{{ $actionIconClass ?? 'text-lime-600 hover:text-lime-900' }} transition-colors"
                                title="Estratto Conto">
                                    <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                                </a>

                                @if(!$entity->trashed())
                                    @if(auth()->guard('admin')->user()->hasPermission('view_entities'))
                                    <button wire:click="viewEntity({{ $entity->id_cliente }})"
                                            wire:key="view-{{ $entity->id_cliente }}"
                                            class="{{ $actionIconClass ?? 'text-blue-600 hover:text-blue-900' }} transition-colors"
                                            title="Visualizza">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    @endif

                                    @if(auth()->guard('admin')->user()->hasPermission('edit_entities'))
                                    <button wire:click="openEditPage({{ $entity->id_cliente }})"
                                            wire:key="edit-{{ $entity->id_cliente }}"
                                            class="{{ $actionIconClass ?? 'text-yellow-600 hover:text-yellow-900' }} transition-colors"
                                            title="Modifica">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @endif

                                    @if(auth()->guard('admin')->user()->hasPermission('delete_entities'))
                                    <button wire:click="confirmDelete({{ $entity->id_cliente }})"
                                            wire:key="delete-{{ $entity->id_cliente }}"
                                            class="{{ $actionIconClass ?? 'text-red-600 hover:text-red-900' }} transition-colors"
                                            title="Elimina">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    @endif
                                @else
                                    <button wire:click="restoreFromTrash({{ $entity->id_cliente }})"
                                            class="{{ $actionIconClass ?? 'text-green-600 hover:text-green-900' }} transition-colors"
                                            title="Ripristina">
                                        <i class="fas fa-trash-restore"></i>
                                    </button>
                                    <button wire:click="forceDeleteFromTrash({{ $entity->id_cliente }})"
                                            onclick="return confirm('Eliminazione definitiva? Questa operazione non può essere annullata.')"
                                            class="{{ $actionIconClass ?? 'text-red-600 hover:text-red-900' }} transition-colors"
                                            title="Elimina definitivamente">
                                        <i class="fas fa-skull-crosswalk"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-building text-gray-400 text-5xl"></i>
                                <p class="mt-2 text-sm">Nessun cliente/fornitore trovato</p>
                                @if($activeSearch || $activeTypeFilter || $activeStatusFilter || $activeRatingFilter !== '')
                                <button wire:click="resetFilters" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    Resetta filtri
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($entities->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500">
            Mostrando {{ $entities->firstItem() ?? 0 }} - {{ $entities->lastItem() ?? 0 }} di {{ $entities->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $entities->links() }}
        </div>
    </div>
    @endif

    <!-- Modal di inserimento -->
    @if($showCreateModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-data="{ show: true }"
        x-show="show"
        x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto"
            x-on:click.away="show = false; $wire.closeCreateModal()"
            x-transition.scale.origin.top>

            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle mr-2 text-lime-600"></i>
                    Nuovo Cliente / Fornitore
                </h2>
                <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Ragione Sociale - OBBLIGATORIO -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ragione Sociale <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        wire:model="formRagioneSociale"
                        placeholder="Ragione Sociale"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formRagioneSociale') border-red-500 @enderror">
                    @error('formRagioneSociale')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Tipologia - OBBLIGATORIA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tipologia <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="formTipologia"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('formTipologia') border-red-500 @enderror">
                        <option value="">Seleziona Tipologia</option>
                        <option value="cliente">Cliente</option>
                        <option value="fornitore">Fornitore</option>
                        <option value="entrambi">Entrambi</option>
                    </select>
                    @error('formTipologia')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Partita IVA - OBBLIGATORIA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Partita IVA <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        wire:model="formPartitaIva"
                        placeholder="Es: IT023750907498 o 023750907498"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formPartitaIva') border-red-500 @enderror">
                    @error('formPartitaIva')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Puoi inserire la partita IVA con o senza prefisso paese (IT, DE, FR, ecc.)
                    </p>
                </div>

                <!-- Codice Fiscale -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                    <input type="text"
                        wire:model="formCodiceFiscale"
                        placeholder="Codice Fiscale"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('formCodiceFiscale')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Nome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text"
                        wire:model="formNome"
                        placeholder="Inserisci il nome"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Cognome -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                    <input type="text"
                        wire:model="formCognome"
                        placeholder="Inserisci il cognome"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Persona di Riferimento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                    <input type="text"
                        wire:model="formRiferimento"
                        placeholder="Persona di riferimento"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email"
                        wire:model="formEmail"
                        placeholder="Email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formEmail') border-red-500 @enderror">
                    @error('formEmail')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Messaggio informativo campi obbligatori -->
            <div class="mt-4 text-xs text-gray-500 border-t pt-3">
                <i class="fas fa-asterisk text-red-500 text-xs mr-1"></i>
                Campi obbligatori
            </div>

            <div class="flex justify-end space-x-3 mt-4 pt-4 border-t">
                <button wire:click="closeCreateModal"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Annulla
                </button>
                <button wire:click="save"
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Salva
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di visualizzazione dettagli -->
    @if($showViewModal && $viewingEntity)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-data="{ show: true }"
        x-show="show"
        x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl p-6 max-h-[90vh] overflow-y-auto"
            x-on:click.away="show = false; $wire.closeViewModal()"
            x-transition.scale.origin.top>

            <!-- Header con stato -->
            <div class="flex justify-between items-start mb-6 border-b pb-3">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center
                        @if($viewingEntity->entity_type == 'cliente') bg-lime-100
                        @elseif($viewingEntity->entity_type == 'fornitore') bg-blue-100
                        @else bg-purple-100
                        @endif">
                        @if($viewingEntity->entity_type == 'cliente')
                            <i class="fas fa-user text-lime-600 text-xl"></i>
                        @elseif($viewingEntity->entity_type == 'fornitore')
                            <i class="fas fa-truck text-blue-600 text-xl"></i>
                        @else
                            <i class="fas fa-handshake text-purple-600 text-xl"></i>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">{{ $viewingEntity->full_name }}</h2>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            @if($viewingEntity->entity_type == 'cliente') bg-lime-100 text-lime-800
                            @elseif($viewingEntity->entity_type == 'fornitore') bg-blue-100 text-blue-800
                            @else bg-purple-100 text-purple-800
                            @endif">
                            {{ $entityTypes[$viewingEntity->entity_type] ?? $viewingEntity->entity_type }}
                        </span>
                        <div class="flex items-center mt-1">
                            @php
                                $viewRating = $viewingEntity->rating ?? 3;
                                $viewRatingColor = $viewRating == 0 ? 'text-red-500' : ($viewRating == 5 ? 'text-green-500' : 'text-yellow-400');
                            @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-sm {{ $i <= $viewRating ? $viewRatingColor : 'text-gray-300' }}"></i>
                            @endfor
                            @if($viewRating == 0)
                                <span class="ml-2 text-xs font-semibold text-red-600 uppercase">Bannato</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Badge Stato -->
                    <div class="px-3 py-1 rounded-full text-sm font-semibold
                        {{ $viewingEntity->valid ? 'bg-lime-100 text-lime-800 border border-lime-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                        <i class="fas {{ $viewingEntity->valid ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                        {{ $viewingEntity->valid ? 'Attivo' : 'Disattivo' }}
                    </div>
                    <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- RIGA 1: Informazioni Anagrafiche (full width) -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Informazioni Anagrafiche
                    </h3>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cognome e Nome -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600 font-medium text-sm block">Cognome</span>
                                    <span class="text-gray-800">{{ $viewingEntity->cognome ?: '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 font-medium text-sm block">Nome</span>
                                    <span class="text-gray-800">{{ $viewingEntity->nome ?: '-' }}</span>
                                </div>
                            </div>

                            <!-- Partita IVA e Codice Fiscale -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600 font-medium text-sm block">Partita IVA</span>
                                    <span class="text-gray-800 font-mono text-sm">{{ $viewingEntity->partita_iva ?: '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 font-medium text-sm block">Codice Fiscale</span>
                                    <span class="text-gray-800 font-mono text-sm">{{ $viewingEntity->codice_fiscale ?: '-' }}</span>
                                </div>
                            </div>

                            <!-- Persona di Riferimento -->
                            <div>
                                <span class="text-gray-600 font-medium text-sm block">Persona di Riferimento</span>
                                <span class="text-gray-800">{{ $viewingEntity->persona_riferimento ?: '-' }}</span>
                            </div>
                        </div>

                        @php
                            // Indirizzo principale (per il blocco copia rapida)
                            $copyAddressLine = null;
                            if ($viewingEntity->addresses && $viewingEntity->addresses->count() > 0) {
                                $copyPrimaryAddress = $viewingEntity->addresses->firstWhere('sede', 'principale') ?? $viewingEntity->addresses->first();
                                if ($copyPrimaryAddress) {
                                    $copyStreetPart = trim($copyPrimaryAddress->indirizzo ?? '');
                                    $copyCityPart = trim(trim(($copyPrimaryAddress->cap ?? '') . ' ' . ($copyPrimaryAddress->citta ?? '')) . ' ' . ($copyPrimaryAddress->provincia ?? ''));
                                    $copyAddressLine = trim($copyStreetPart . ($copyCityPart ? ' - ' . $copyCityPart : ''));
                                    if (!empty($copyPrimaryAddress->nazione)) {
                                        $copyAddressLine = trim($copyAddressLine . ' ' . $copyPrimaryAddress->nazione);
                                    }
                                    $copyAddressLine = $copyAddressLine ?: null;
                                }
                            }

                            // Telefono (fisso o cellulare, il primo disponibile)
                            $copyPhoneContact = $viewingEntity->contacts->firstWhere('id_settings', 1) ?? $viewingEntity->contacts->firstWhere('id_settings', 2);
                            $copyPhoneValue = $copyPhoneContact ? $copyPhoneContact->valore : null;

                            // Email
                            $copyEmailContact = $viewingEntity->contacts->firstWhere('id_settings', 4);
                            if (!$copyEmailContact) {
                                $copyEmailContact = $viewingEntity->contacts->first(function ($c) {
                                    return filter_var($c->valore, FILTER_VALIDATE_EMAIL) !== false;
                                });
                            }
                            $copyEmailValue = $copyEmailContact ? $copyEmailContact->valore : ($viewingEntity->email ?? null);

                            $copyLines = [];
                            $copyLines[] = $viewingEntity->full_name;
                            if ($copyAddressLine) {
                                $copyLines[] = $copyAddressLine;
                            }
                            $copyLines[] = 'Partita IVA ' . ($viewingEntity->partita_iva ?: '-');
                            $copyLines[] = 'PEC ' . ($viewingEntity->pec ?: '-');
                            $copyLines[] = 'Codice SDI ' . ($viewingEntity->codice_sdi ?: '-');
                            $copyLines[] = 'Email ' . ($copyEmailValue ?: '-');
                            $copyLines[] = 'Telefono ' . ($copyPhoneValue ?: '-');

                            $copyEntityText = implode("\n", $copyLines);
                        @endphp
                        <div class="bg-white border border-gray-200 rounded-lg p-3 flex flex-col justify-between" x-data="{ copied: false }">
                            <div class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $copyEntityText }}</div>
                            <button type="button"
                                x-on:click="
                                    navigator.clipboard.writeText(@js($copyEntityText));
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="mt-3 self-end inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium text-white bg-lime-600 hover:bg-lime-700 transition-colors">
                                <i class="fa-regular fa-copy" x-show="!copied"></i>
                                <i class="fas fa-check" x-show="copied" x-cloak></i>
                                <span x-text="copied ? 'Copiato!' : 'COPIA'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGA 2: Dati Fattura Elettronica e Date (affiancati) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Dati Fattura Elettronica -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-file-invoice-dollar mr-2 text-lime-500"></i> Dati Fattura Elettronica
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">PEC</span>
                            <span class="text-gray-800 text-sm break-all">{{ $viewingEntity->pec ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">Codice SDI</span>
                            <span class="text-gray-800 font-mono text-sm">{{ $viewingEntity->codice_sdi ?: '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Date e Informazioni -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-calendar-alt mr-2 text-purple-500"></i> Date e Informazioni
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">Data inserimento</span>
                            <span class="text-gray-800 text-sm">{{ $viewingEntity->created_at ? $viewingEntity->created_at->format('d/m/Y H:i') : ($viewingEntity->data_inserimento ? date('d/m/Y H:i', strtotime($viewingEntity->data_inserimento)) : '-') }}</span>
                        </div>
                        @if($viewingEntity->updated_at && $viewingEntity->updated_at != $viewingEntity->created_at)
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">Ultima modifica</span>
                            <span class="text-gray-800 text-sm">{{ $viewingEntity->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">ID</span>
                            <span class="text-gray-800 text-sm font-mono">{{ $viewingEntity->id_cliente }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGA 3: Tabella Indirizzi (full width) -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i> Indirizzi
                    </h3>
                    @if($viewingEntity->addresses && $viewingEntity->addresses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Sede</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Indirizzo</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Città</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Provincia</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">CAP</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Nazione</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($viewingEntity->addresses as $address)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 font-medium">
                                        @php
                                            $nomeSede = $address->sede ?? '-';
                                            if($nomeSede == 'principale') $nomeSede = 'Sede Principale';
                                            elseif($nomeSede == 'legale') $nomeSede = 'Sede Legale';
                                            elseif($nomeSede == 'operativa') $nomeSede = 'Sede Operativa';
                                            elseif($nomeSede == 'amministrativa') $nomeSede = 'Sede Amministrativa';
                                            elseif($nomeSede == 'fiscale') $nomeSede = 'Sede Fiscale';
                                        @endphp
                                        {{ $nomeSede }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->indirizzo ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->citta ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->provincia ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->cap ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->nazione ?: 'Italia' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-gray-500 text-sm text-center py-4">
                        <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i> Nessun indirizzo disponibile
                    </p>
                    @endif
                </div>
            </div>

            <!-- RIGA 4: Tabella Contatti (full width) -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-address-card mr-2 text-purple-500"></i> Contatti
                    </h3>
                    @if($viewingEntity->contacts && $viewingEntity->contacts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Tipo</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Valore</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Principale</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($viewingEntity->contacts as $contact)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">
                                        @php
                                            $icona = 'fa-phone';
                                            $tipoContatto = $contact->setting->valore ?? $contact->tipo ?? 'Contatto';
                                            if(str_contains(strtolower($tipoContatto), 'cell') || str_contains(strtolower($tipoContatto), 'mobile') || str_contains(strtolower($tipoContatto), 'cellulare')) {
                                                $icona = 'fa-mobile-alt';
                                            } elseif(str_contains(strtolower($tipoContatto), 'fax')) {
                                                $icona = 'fa-fax';
                                            } elseif(str_contains(strtolower($tipoContatto), 'email')) {
                                                $icona = 'fa-envelope';
                                            } elseif(str_contains(strtolower($tipoContatto), 'whatsapp')) {
                                                $icona = 'fa-whatsapp';
                                            } elseif(str_contains(strtolower($tipoContatto), 'telefono')) {
                                                $icona = 'fa-phone';
                                            }
                                        @endphp
                                        <i class="fas {{ $icona }} text-gray-500 mr-2 w-4"></i>
                                        {{ $tipoContatto }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @if(filter_var($contact->valore, FILTER_VALIDATE_EMAIL))
                                            <a href="mailto:{{ $contact->valore }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $contact->valore }}
                                            </a>
                                        @elseif(preg_match('/^[0-9+\-\s\(\)]+$/', $contact->valore))
                                            <a href="tel:{{ $contact->valore }}" class="text-gray-800 hover:text-blue-600">
                                                {{ $contact->valore }}
                                            </a>
                                        @else
                                            <span class="text-gray-800">{{ $contact->valore }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if($contact->principale)
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Principale
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-gray-500 text-sm text-center py-4">
                        <i class="fas fa-address-card text-gray-400 mr-1"></i> Nessun contatto disponibile
                    </p>
                    @endif
                </div>
            </div>

            <!-- RIGA 5: TRACCIAMENTO -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-history mr-2 text-indigo-500"></i> Tracciamento
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Creato da -->
                        <div class="bg-white rounded-md p-3 shadow-sm border border-gray-100">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-plus-circle text-green-500 mt-0.5"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">Inserito da</h4>
                                    <div class="mt-1">
                                        <p class="text-sm text-gray-700 font-semibold">
                                            {{ $viewingEntity->createdBy ? $viewingEntity->createdBy->name : 'Sistema' }}
                                        </p>
                                        @if($viewingEntity->createdBy && $viewingEntity->createdBy->email)
                                        <p class="text-xs text-gray-500">{{ $viewingEntity->createdBy->email }}</p>
                                        @endif
                                        <p class="text-xs text-gray-400 mt-1">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            {{ $viewingEntity->created_at ? $viewingEntity->created_at->format('d/m/Y H:i:s') : ($viewingEntity->data_inserimento ? date('d/m/Y H:i:s', strtotime($viewingEntity->data_inserimento)) : '-') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modificato da -->
                        <div class="bg-white rounded-md p-3 shadow-sm border border-gray-100">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-edit text-blue-500 mt-0.5"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">Modificato da</h4>
                                    <div class="mt-1">
                                        @if($viewingEntity->updated_at && $viewingEntity->created_at != $viewingEntity->updated_at)
                                            <p class="text-sm text-gray-700 font-semibold">
                                                {{ $viewingEntity->updatedBy ? $viewingEntity->updatedBy->name : 'Sistema' }}
                                            </p>
                                            @if($viewingEntity->updatedBy && $viewingEntity->updatedBy->email)
                                            <p class="text-xs text-gray-500">{{ $viewingEntity->updatedBy->email }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400 mt-1">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                {{ $viewingEntity->updated_at->format('d/m/Y H:i:s') }}
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $viewingEntity->updated_at->diffForHumans() }}
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-400 italic">Mai modificato</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info aggiuntive sul tracciamento -->
                    @if($viewingEntity->created_at && $viewingEntity->updated_at && $viewingEntity->created_at != $viewingEntity->updated_at)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock"></i>
                                <span>Ultima modifica: {{ $viewingEntity->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-database"></i>
                                <span>ID Record: {{ $viewingEntity->id_cliente }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Footer con bottoni -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                @if(auth()->guard('admin')->user()->hasPermission('edit_entities'))
                <button wire:click="redirectToEdit({{ $viewingEntity->id_cliente }})"
                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                    <i class="fas fa-edit mr-2"></i> Modifica
                </button>
                @endif
                <button wire:click="closeViewModal"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di conferma eliminazione -->
    @if($showDeleteModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6"
             x-on:click.away="show = false; $wire.cancelDelete()"
             x-transition.scale.origin.top>

            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare <strong>{{ $entityNameToDelete }}</strong>?
                    <br>
                    <span class="text-xs text-gray-400">L'elemento verrà spostato nel cestino e potrà essere ripristinato.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="deleteEntity"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Cestino -->
    @if($showTrashModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl p-6 max-h-[90vh] overflow-y-auto"
             x-on:click.away="show = false; $wire.closeTrashModal()"
             x-transition.scale.origin.top>

            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-trash-alt mr-2 text-red-600"></i>
                    Cestino - Elementi Eliminati
                </h2>
                <button wire:click="closeTrashModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Filtri Cestino -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative md:col-span-2">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                        <input type="text"
                               wire:model.live="trashSearch"
                               placeholder="Cerca nel cestino..."
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <select wire:model.live="trashTypeFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tutti i tipi</option>
                        @foreach($entityTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($trashSearch || $trashTypeFilter)
                <div class="mt-3">
                    <button wire:click="resetTrashFilters" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-sync-alt mr-1"></i> Resetta filtri
                    </button>
                </div>
                @endif
            </div>

            <!-- Tabella Elementi Cancellati -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('ragione_sociale')">
                                <div class="flex items-center space-x-1">
                                    <span>Cliente / Fornitore</span>
                                    @if($trashSortField === 'ragione_sociale')
                                        <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('entity_type')">
                                <div class="flex items-center space-x-1">
                                    <span>Tipo</span>
                                    @if($trashSortField === 'entity_type')
                                        <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">P.IVA / CF</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('deleted_at')">
                                <div class="flex items-center space-x-1">
                                    <span>Data eliminazione</span>
                                    @if($trashSortField === 'deleted_at')
                                        <i class="fas fa-arrow-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Eliminato da</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trashedEntities as $entity)
                        <tr wire:key="trash-{{ $entity->id_cliente }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center
                                        @if($entity->entity_type == 'cliente') bg-lime-100
                                        @elseif($entity->entity_type == 'fornitore') bg-blue-100
                                        @else bg-purple-100
                                        @endif">
                                        @if($entity->entity_type == 'cliente')
                                            <i class="fas fa-user text-lime-600 text-lg"></i>
                                        @elseif($entity->entity_type == 'fornitore')
                                            <i class="fas fa-truck text-blue-600 text-lg"></i>
                                        @else
                                            <i class="fas fa-handshake text-purple-600 text-lg"></i>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $entity->full_name }}
                                        </div>
                                        @if($entity->persona_riferimento)
                                        <div class="text-xs text-gray-500">
                                            {{ $entity->persona_riferimento }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($entity->entity_type == 'cliente') bg-lime-100 text-lime-800
                                    @elseif($entity->entity_type == 'fornitore') bg-blue-100 text-blue-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ $entityTypes[$entity->entity_type] ?? $entity->entity_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="flex flex-col">
                                    @if($entity->partita_iva)
                                        <span class="font-mono text-xs">{{ $entity->partita_iva }}</span>
                                    @endif
                                    @if($entity->codice_fiscale && $entity->codice_fiscale != $entity->partita_iva)
                                        <span class="font-mono text-xs">{{ $entity->codice_fiscale }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $entity->deleted_at ? $entity->deleted_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $entity->deletedBy ? $entity->deletedBy->name : 'Sistema' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-3">
                                    <button wire:click="restoreFromTrash({{ $entity->id_cliente }})"
                                            class="text-green-600 hover:text-green-900 transition-colors"
                                            title="Ripristina">
                                        <i class="fas fa-trash-restore text-lg"></i>
                                    </button>
                                    <button wire:click="forceDeleteFromTrash({{ $entity->id_cliente }})"
                                            onclick="return confirm('Eliminazione definitiva? Questa operazione non può essere annullata.')"
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            title="Elimina definitivamente">
                                        <i class="fas fa-skull-crosswalk text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-trash-alt text-gray-400 text-5xl mb-2"></i>
                                    <p class="text-sm">Il cestino è vuoto</p>
                                    <p class="text-xs text-gray-400 mt-1">Nessun elemento eliminato</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginazione Cestino -->
            @if($trashedEntities->hasPages())
            <div class="mt-6">
                <div class="text-sm text-gray-500 mb-2">
                    Mostrando {{ $trashedEntities->firstItem() ?? 0 }} - {{ $trashedEntities->lastItem() ?? 0 }} di {{ $trashedEntities->total() }} elementi nel cestino
                </div>
                <div class="flex justify-center">
                    {{ $trashedEntities->links() }}
                </div>
            </div>
            @endif

            <!-- Footer -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button wire:click="closeTrashModal"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Stile paginazione - inserito all'interno del div root -->
    <style>
        /* Stile paginazione bianco */
        nav[role="navigation"] div.flex-1 {
            display: none !important;
        }

        nav[role="navigation"] .relative.z-0 {
            justify-content: center !important;
            display: flex !important;
        }

        /* Personalizzazione link paginazione */
        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] .relative.inline-flex.items-center {
            background-color: white !important;
            border-color: #e5e7eb !important;
            color: #374151 !important;
        }

        nav[role="navigation"] span[aria-current="page"] span {
            background-color: #10b981 !important;
            border-color: #10b981 !important;
            color: white !important;
        }

        nav[role="navigation"] .relative.inline-flex.items-center:hover {
            background-color: #f9fafb !important;
            border-color: #d1d5db !important;
        }

        /* Nasconde il testo "Showing" e "to" e "results" */
        nav[role="navigation"] p.text-sm {
            display: none !important;
        }

        /* Centra completamente la paginazione */
        nav[role="navigation"] > div:first-child {
            justify-content: center !important;
        }

        nav[role="navigation"] > div:first-child > div:first-child {
            display: none !important;
        }
    </style>
</div>