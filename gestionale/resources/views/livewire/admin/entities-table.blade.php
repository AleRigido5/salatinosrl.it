<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per nome, cognome, ragione sociale, email..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="typeFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Tutti i tipi</option>
                @foreach($entityTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $typeFilter || $statusFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search || $typeFilter || $statusFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-blue-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
            @if($typeFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-800">
                Tipo: {{ $entityTypes[$typeFilter] ?? $typeFilter }}
                <button wire:click="$set('typeFilter', '')" class="ml-1 hover:text-blue-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-800">
                Stato: {{ $statusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-blue-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
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
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('entity_type')">
                            <div class="flex items-center space-x-1">
                                <span>Tipo</span>
                                @if($sortField === 'entity_type')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contatti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('partita_iva')">
                            <div class="flex items-center space-x-1">
                                <span>P.IVA / CF</span>
                                @if($sortField === 'partita_iva')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('valid')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'valid')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('created_at')">
                            <div class="flex items-center space-x-1">
                                <span>Data inserimento</span>
                                @if($sortField === 'created_at')
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
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
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center
                                    @if($entity->entity_type == 'cliente') bg-green-100
                                    @elseif($entity->entity_type == 'fornitore') bg-blue-100
                                    @else bg-purple-100
                                    @endif">
                                    
                                    @if($entity->entity_type == 'cliente')
                                        <i class="fas fa-user text-green-600 text-lg"></i>
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
                                        <i class="fas fa-user-tag mr-1 text-gray-400"></i>
                                        {{ $entity->persona_riferimento }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($entity->entity_type == 'cliente') bg-green-100 text-green-800
                                @elseif($entity->entity_type == 'fornitore') bg-blue-100 text-blue-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ $entityTypes[$entity->entity_type] ?? $entity->entity_type }}
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="flex flex-col space-y-1">
                                @if($phone)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <span class="truncate">{{ $phone->valore }}</span>
                                    </div>
                                @endif
                                
                                @if($mobile)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="truncate">{{ $mobile->valore }}</span>
                                    </div>
                                @endif
                                
                                @if($emailValue)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="truncate">{{ Str::limit($emailValue, 30) }}</span>
                                    </div>
                                @endif
                                
                                @if(!$phone && !$mobile && !$emailValue)
                                    <span class="text-gray-400 italic text-xs">Nessun contatto</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 text-sm text-gray-500">
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
                                    <span class="text-gray-400 italic text-xs">-</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $entity->id_cliente }})" 
                                    class="px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200
                                        {{ $entity->valid ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $entity->valid ? 'Attivo' : 'Disattivo' }}
                            </button>
                        </td>
                        
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            {{ $entity->created_at ? $entity->created_at->format('d/m/Y') : ($entity->data_inserimento ? date('d/m/Y', strtotime($entity->data_inserimento)) : '-') }}
                        </td>
                        
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                @if(auth()->guard('admin')->user()->hasPermission('view_entities'))
                                <button wire:click="viewEntity({{ $entity->id_cliente }})" 
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_entities'))
                                <button wire:click="openEditModal({{ $entity->id_cliente }})" 
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                        title="Modifica">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('delete_entities'))
                                <button wire:click="confirmDelete({{ $entity->id_cliente }})" 
                                        class="text-red-600 hover:text-red-900 transition-colors"
                                        title="Elimina">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p class="mt-2 text-sm">Nessun cliente/fornitore trovato</p>
                                @if($search || $typeFilter || $statusFilter)
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
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Mostrando {{ $entities->firstItem() ?? 0 }} - {{ $entities->lastItem() ?? 0 }} di {{ $entities->total() }} risultati
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    @if ($entities->onFirstPage())
                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </span>
                    @else
                        <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                    @endif

                    @foreach ($entities->links()->elements as $element)
                        @if (is_string($element))
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $entities->currentPage())
                                    <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">{{ $page }}</span>
                                @else
                                    <button wire:click="setPage({{ $page }})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">{{ $page }}</button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($entities->hasMorePages())
                        <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    @else
                        <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300 cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    @endif
                </nav>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di conferma eliminazione -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" 
             x-on:click.away="show = false"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Conferma Eliminazione</h2>
                <button wire:click="cancelDelete" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="mb-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="bg-red-100 rounded-full p-3">
                        <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-center text-gray-700 mb-2">
                    Sei sicuro di voler eliminare il seguente elemento?
                </p>
                <p class="text-center font-semibold text-gray-900 bg-gray-50 p-2 rounded">
                    {{ $entityNameToDelete }}
                </p>
                <p class="text-center text-sm text-gray-500 mt-3">
                    L'elemento verrà spostato nel cestino e potrà essere ripristinato in seguito.
                </p>
            </div>
            
            <div class="flex justify-end space-x-3">
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
    @endif

    <!-- Modal di inserimento -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto" 
             x-on:click.away="show = false"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <svg class="inline-block w-6 h-6 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuovo Cliente / Fornitore
                </h2>
                <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text" 
                           wire:model="formNome" 
                           placeholder="Inserisci il nome" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                    <input type="text" 
                           wire:model="formCognome" 
                           placeholder="Inserisci il cognome" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tipologia <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="formTipologia" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleziona Tipologia</option>
                        <option value="cliente">Cliente</option>
                        <option value="fornitore">Fornitore</option>
                        <option value="entrambi">Entrambi</option>
                    </select>
                    @error('formTipologia') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                    <input type="text" 
                           wire:model="formRiferimento" 
                           placeholder="Persona di riferimento" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale</label>
                    <input type="text" 
                           wire:model="formRagioneSociale" 
                           placeholder="Ragione Sociale (opzionale)" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                    <input type="text" 
                           wire:model="formPartitaIva" 
                           placeholder="Partita IVA" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" 
                           wire:model="formEmail" 
                           placeholder="Email" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('formEmail') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button wire:click="closeCreateModal" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button wire:click="save" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    Salva
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di modifica -->
    @if($showEditModal && $editingEntity)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto" 
             x-on:click.away="show = false"
             x-transition.scale.origin.top>
            
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <svg class="inline-block w-6 h-6 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Modifica Cliente / Fornitore
                </h2>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Colonna Sinistra - Dati Anagrafici -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-user-circle mr-2 text-blue-500"></i> Dati Anagrafici
                        </h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                <input type="text" 
                                       wire:model="editNome" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                                <input type="text" 
                                       wire:model="editCognome" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipologia <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editTipologia" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="cliente">Cliente</option>
                                    <option value="fornitore">Fornitore</option>
                                    <option value="entrambi">Entrambi</option>
                                </select>
                                @error('editTipologia') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                                <input type="text" 
                                       wire:model="editRiferimento" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale</label>
                                <input type="text" 
                                       wire:model="editRagioneSociale" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                                <input type="text" 
                                       wire:model="editPartitaIva" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" 
                                       wire:model="editEmail" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editEmail') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                                <input type="text" 
                                       wire:model="editCodiceFiscale" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colonna Destra - Dati Fattura Elettronica -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Dati Fattura Elettronica
                        </h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PEC (Posta Elettronica Certificata)</label>
                                <input type="email" 
                                       wire:model="editPec" 
                                       placeholder="esempio@pec.it"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Indirizzo PEC per l'invio delle fatture elettroniche</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Codice SDI (Sistema di Interscambio)</label>
                                <input type="text" 
                                       wire:model="editCodiceSdi" 
                                       placeholder="es: XXXXXXX"
                                       maxlength="7"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase">
                                <p class="text-xs text-gray-500 mt-1">Codice a 7 caratteri per la ricezione delle fatture elettroniche</p>
                            </div>
                            
                            <div class="mt-4 p-3 bg-blue-50 rounded-md border border-blue-200">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                                    <div class="text-xs text-blue-700">
                                        <p class="font-medium mb-1">Informazioni sulla Fattura Elettronica:</p>
                                        <p>Il codice SDI (anche chiamato "codice destinatario") è obbligatorio per ricevere fatture elettroniche dalla Pubblica Amministrazione. Se non specificato, verrà utilizzata la PEC.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stato -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-toggle-on mr-2 text-purple-500"></i> Stato
                        </h3>
                        
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" 
                                       wire:model="editValid" 
                                       class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Account attivo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer con azioni -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button wire:click="closeEditModal" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button wire:click="updateEntity" 
                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Aggiorna
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal di visualizzazione dettagli -->
    @if($showViewModal && $viewingEntity)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" 
         x-data="{ show: true }" 
         x-show="show" 
         x-transition.opacity.duration.200ms>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto" 
             x-on:click.away="show = false"
             x-transition.scale.origin.top>
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center
                        @if($viewingEntity->entity_type == 'cliente') bg-green-100
                        @elseif($viewingEntity->entity_type == 'fornitore') bg-blue-100
                        @else bg-purple-100
                        @endif">
                        @if($viewingEntity->entity_type == 'cliente')
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        @elseif($viewingEntity->entity_type == 'fornitore')
                            <i class="fas fa-truck text-blue-600 text-xl"></i>
                        @else
                            <i class="fas fa-handshake text-purple-600 text-xl"></i>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">{{ $viewingEntity->full_name }}</h2>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($viewingEntity->entity_type == 'cliente') bg-green-100 text-green-800
                            @elseif($viewingEntity->entity_type == 'fornitore') bg-blue-100 text-blue-800
                            @else bg-purple-100 text-purple-800
                            @endif">
                            {{ $entityTypes[$viewingEntity->entity_type] ?? $viewingEntity->entity_type }}
                        </span>
                    </div>
                </div>
                <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Contenuto -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Colonna Sinistra - Info Anagrafiche -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i> Informazioni Anagrafiche
                        </h3>
                        <div class="space-y-2">
                            @if($viewingEntity->ragione_sociale)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Ragione Sociale:</span>
                                <span class="text-gray-800">{{ $viewingEntity->ragione_sociale }}</span>
                            </div>
                            @endif
                            
                            @if($viewingEntity->nome || $viewingEntity->cognome)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Nome/Cognome:</span>
                                <span class="text-gray-800">{{ $viewingEntity->nome }} {{ $viewingEntity->cognome }}</span>
                            </div>
                            @endif
                            
                            @if($viewingEntity->persona_riferimento)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Riferimento:</span>
                                <span class="text-gray-800">{{ $viewingEntity->persona_riferimento }}</span>
                            </div>
                            @endif
                            
                            @if($viewingEntity->partita_iva)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Partita IVA:</span>
                                <span class="text-gray-800 font-mono">{{ $viewingEntity->partita_iva }}</span>
                            </div>
                            @endif
                            
                            @if($viewingEntity->codice_fiscale)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Codice Fiscale:</span>
                                <span class="text-gray-800 font-mono">{{ $viewingEntity->codice_fiscale }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Dati Fattura Elettronica -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Dati Fattura Elettronica
                        </h3>
                        <div class="space-y-2">
                            @if($viewingEntity->pec)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">PEC:</span>
                                <span class="text-gray-800">{{ $viewingEntity->pec }}</span>
                            </div>
                            @else
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">PEC:</span>
                                <span class="text-gray-400 italic">Non impostata</span>
                            </div>
                            @endif
                            
                            @if($viewingEntity->codice_sdi)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Codice SDI:</span>
                                <span class="text-gray-800 font-mono">{{ $viewingEntity->codice_sdi }}</span>
                            </div>
                            @else
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Codice SDI:</span>
                                <span class="text-gray-400 italic">Non impostato</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Stato e Date -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-calendar-alt mr-2 text-purple-500"></i> Stato e Date
                        </h3>
                        <div class="space-y-2">
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Stato:</span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $viewingEntity->valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $viewingEntity->valid ? 'Attivo' : 'Disattivo' }}
                                </span>
                            </div>
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Data inserimento:</span>
                                <span class="text-gray-800">{{ $viewingEntity->created_at ? $viewingEntity->created_at->format('d/m/Y H:i') : ($viewingEntity->data_inserimento ? date('d/m/Y H:i', strtotime($viewingEntity->data_inserimento)) : '-') }}</span>
                            </div>
                            @if($viewingEntity->updated_at && $viewingEntity->updated_at != $viewingEntity->created_at)
                            <div class="flex">
                                <span class="w-32 text-gray-600 font-medium">Ultima modifica:</span>
                                <span class="text-gray-800">{{ $viewingEntity->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Colonna Destra - Contatti -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                            <i class="fas fa-address-card mr-2 text-orange-500"></i> Contatti
                        </h3>
                        <div class="space-y-3">
                            @if($viewingEntity->email)
                            <div class="flex items-start">
                                <i class="fas fa-envelope w-5 text-gray-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">Email</span>
                                    <a href="mailto:{{ $viewingEntity->email }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $viewingEntity->email }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            
                            @php
                                $phoneContact = $viewingEntity->contacts->firstWhere('id_settings', 1);
                                $mobileContact = $viewingEntity->contacts->firstWhere('id_settings', 2);
                                $faxContact = $viewingEntity->contacts->firstWhere('id_settings', 3);
                                $emailContact = $viewingEntity->contacts->firstWhere('id_settings', 4);
                                $otherContacts = $viewingEntity->contacts->filter(function($c) {
                                    return !in_array($c->id_settings, [1, 2, 3, 4]);
                                });
                            @endphp
                            
                            @if($phoneContact)
                            <div class="flex items-start">
                                <i class="fas fa-phone w-5 text-gray-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">Telefono</span>
                                    <a href="tel:{{ $phoneContact->valore }}" class="text-gray-800">
                                        {{ $phoneContact->valore }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            
                            @if($mobileContact)
                            <div class="flex items-start">
                                <i class="fab fa-whatsapp w-5 text-green-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">Cellulare / WhatsApp</span>
                                    <a href="tel:{{ $mobileContact->valore }}" class="text-gray-800">
                                        {{ $mobileContact->valore }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            
                            @if($faxContact)
                            <div class="flex items-start">
                                <i class="fas fa-fax w-5 text-gray-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">Fax</span>
                                    <span class="text-gray-800">{{ $faxContact->valore }}</span>
                                </div>
                            </div>
                            @endif
                            
                            @if($emailContact)
                            <div class="flex items-start">
                                <i class="fas fa-envelope w-5 text-gray-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">Email (contatto)</span>
                                    <a href="mailto:{{ $emailContact->valore }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $emailContact->valore }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            
                            @foreach($otherContacts as $contact)
                            <div class="flex items-start">
                                <i class="fas fa-address-card w-5 text-gray-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="text-gray-600 font-medium block text-sm">
                                        {{ $contact->setting->nome ?? 'Altro contatto' }}
                                    </span>
                                    <span class="text-gray-800">{{ $contact->valore }}</span>
                                </div>
                            </div>
                            @endforeach
                            
                            @if(!$viewingEntity->email && !$phoneContact && !$mobileContact && !$faxContact && !$emailContact && $otherContacts->isEmpty())
                            <div class="text-center py-4">
                                <i class="fas fa-ban text-gray-400 text-3xl mb-2"></i>
                                <p class="text-gray-500">Nessun contatto disponibile</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer con azioni -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button wire:click="closeViewModal" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif
</div>