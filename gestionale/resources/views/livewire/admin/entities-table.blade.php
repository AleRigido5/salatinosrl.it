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
                       placeholder="Cerca per: P.IVA, Ragione Sociale, Nome, Cognome, Persona Riferimento, Città, Telefono, Email..." 
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
                    <tr wire:key="entity-{{ $entity->id_cliente }}" class="hover:bg-gray-50 transition-colors duration-150">
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
                                        <i class="fas fa-user-tag mr-1 text-gray-400"></i>
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
                                    wire:key="toggle-{{ $entity->id_cliente }}"
                                    class="px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200
                                        {{ $entity->valid ? 'bg-lime-100 text-lime-800 hover:bg-lime-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
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
                                        wire:key="view-{{ $entity->id_cliente }}"
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                @if(auth()->guard('admin')->user()->hasPermission('edit_entities'))
                                <button wire:click="openEditPage({{ $entity->id_cliente }})" 
                                        wire:key="edit-{{ $entity->id_cliente }}"
                                        class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                        title="Modifica">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
        <div class="text-sm text-gray-500">
            Mostrando {{ $entities->firstItem() ?? 0 }} - {{ $entities->lastItem() ?? 0 }} di {{ $entities->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $entities->links() }}
        </div>
    </div>
    @endif

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
                    <svg class="inline-block w-6 h-6 mr-2 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale</label>
                    <input type="text" 
                        wire:model="formRagioneSociale" 
                        placeholder="Ragione Sociale (opzionale)" 
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cognome</label>
                    <input type="text" 
                           wire:model="formCognome" 
                           placeholder="Inserisci il cognome" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text" 
                           wire:model="formNome" 
                           placeholder="Inserisci il nome" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Persona di Riferimento</label>
                    <input type="text" 
                           wire:model="formRiferimento" 
                           placeholder="Persona di riferimento" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                    <input type="text" 
                           wire:model="formPartitaIva" 
                           placeholder="Partita IVA" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button wire:click="closeCreateModal" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </button>
                <button wire:click="save" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    Salva
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
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- RIGA 1: Informazioni Anagrafiche (full width) -->
            <div class="mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Informazioni Anagrafiche
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        
                        <!-- Persona di Riferimento -->
                        <div>
                            <span class="text-gray-600 font-medium text-sm block">Persona di Riferimento</span>
                            <span class="text-gray-800">{{ $viewingEntity->persona_riferimento ?: '-' }}</span>
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
                                            // Formatta il nome della sede in modo più leggibile
                                            $nomeSede = $address->sede ?? '-';
                                            if($nomeSede == 'principale') $nomeSede = 'Sede Principale';
                                            elseif($nomeSede == 'legale') $nomeSede = 'Sede Legale';
                                            elseif($nomeSede == 'operativa') $nomeSede = 'Sede Operativa';
                                            elseif($nomeSede == 'amministrativa') $nomeSede = 'Sede Amministrativa';
                                            elseif($nomeSede == 'fiscale') $nomeSede = 'Sede Fiscale';
                                        @endphp
                                        {{ $nomeSede }}
                                    </div>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->indirizzo ?: '-' }}</div>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->citta ?: '-' }}</div>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->provincia ?: '-' }}</div>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->cap ?: '-' }}</div>
                                    <td class="px-3 py-2 text-gray-700">{{ $address->nazione ?: 'Italia' }}</div>
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
                                    </div>
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
                                    </div>
                                    <td class="px-3 py-2 text-center">
                                        @if($contact->principale)
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Principale
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </div>
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
            
            <!-- ========== RIGA 5: TRACCIAMENTO (aggiunto) ========== -->
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
                                    <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
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
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
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
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Ultima modifica: {{ $viewingEntity->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <span>ID Record: {{ $viewingEntity->id_cliente }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <!-- ========== FINE TRACCIAMENTO ========== -->
            
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
                    Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif
</div>