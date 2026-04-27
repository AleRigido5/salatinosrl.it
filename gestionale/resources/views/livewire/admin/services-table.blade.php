<div wire:key="{{ $componentKey }}">
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cerca per titolo o descrizione..." 
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
            </div>
            
            <select wire:model.live="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutte le categorie</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->valore }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                <option value="">Tutti gli stati</option>
                <option value="active">Attivi</option>
                <option value="inactive">Disattivi</option>
            </select>
        </div>
        
        <div class="flex justify-between items-center mt-4">
            @if($search || $categoryFilter || $statusFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Resetta filtri
            </button>
            @endif
        </div>
        
        @if($search || $categoryFilter || $statusFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
            @if($categoryFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Categoria: {{ $categories->firstWhere('id', $categoryFilter)->valore ?? $categoryFilter }}
                <button wire:click="$set('categoryFilter', '')" class="ml-1 hover:text-lime-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
            @if($statusFilter)
            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-lime-100 text-lime-800">
                Stato: {{ $statusFilter === 'active' ? 'Attivi' : 'Disattivi' }}
                <button wire:click="$set('statusFilter', '')" class="ml-1 hover:text-lime-900">
                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Servizi -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('Titolo')">
                            <div class="flex items-center space-x-1">
                                <span>Titolo</span>
                                @if($sortField === 'Titolo')
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('Prezzo_un')">
                            <div class="flex items-center space-x-1">
                                <span>Prezzo</span>
                                @if($sortField === 'Prezzo_un')
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition" wire:click="sortBy('Stato')">
                            <div class="flex items-center space-x-1">
                                <span>Stato</span>
                                @if($sortField === 'Stato')
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
                <tbody>
                    @forelse($services as $index => $service)
                    <tr wire:key="service-{{ $service->id }}-{{ $index }}-{{ $services->currentPage() }}" class="hover:bg-gray-50 transition-colors duration-150 border-t border-gray-200">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $service->Titolo }}
                            </div>
                            @if($service->Descrizione)
                            <div class="text-xs text-gray-500">
                                {{ Str::limit($service->Descrizione, 60) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $service->category->valore ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($service->Prezzo_un)
                                € {{ number_format($service->Prezzo_un, 2) }}
                                @if($service->unitaMisura)
                                    / {{ $service->unitaMisura->nome }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $service->Stato ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                {{ $service->Stato ? 'Attivo' : 'Disattivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                            <div class="flex space-x-3">
                                <!-- Visualizza -->
                                <button type="button" wire:click="viewService({{ $service->id }})" 
                                        class="text-blue-500 hover:text-blue-700 transition-colors p-1 text-base"
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                
                                <!-- Modifica -->
                                @if(auth()->guard('admin')->user()->hasPermission('edit_services'))
                                <button type="button" wire:click="editService({{ $service->id }})" 
                                        class="text-yellow-500 hover:text-yellow-700 transition-colors p-1 text-base"
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif
                                
                                <!-- Attiva/Disattiva -->
                                @if(auth()->guard('admin')->user()->hasPermission('edit_services'))
                                <button wire:click="toggleStatus({{ $service->id }})" 
                                        wire:key="status-{{ $service->Stato }}"
                                        class="transition-colors text-base {{ $service->Stato ? 'text-lime-600 hover:text-lime-800' : 'text-gray-400 hover:text-gray-600' }}"
                                        title="{{ $service->Stato ? 'Disattiva' : 'Attiva' }}">
                                    <i class="{{ $service->Stato ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark text-red-400' }}"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p class="mt-2 text-sm">Nessun servizio trovato</p>
                                @if($search || $categoryFilter || $statusFilter)
                                <button wire:click="resetFilters" class="mt-2 text-sm text-lime-600 hover:text-lime-800">
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
    @if($services->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $services->firstItem() ?? 0 }} - {{ $services->lastItem() ?? 0 }} di {{ $services->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $services->links() }}
        </div>
    </div>
    @endif

    <style>
        nav[role="navigation"] div.flex-1 {
            display: none !important;
        }
        nav[role="navigation"] .relative.z-0 {
            justify-content: center !important;
            display: flex !important;
        }
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
        nav[role="navigation"] p.text-sm {
            display: none !important;
        }
        nav[role="navigation"] > div:first-child {
            justify-content: center !important;
        }
        nav[role="navigation"] > div:first-child > div:first-child {
            display: none !important;
        }
    </style>

    <!-- MODAL VISUALIZZAZIONE -->
    @if($showViewModal && $viewingService)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.closeViewModal() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-4 pb-2 sm:px-6 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-bold text-gray-800 truncate">{{ $viewingService->Titolo }}</h2>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                                {{ $viewingService->Stato ? 'bg-lime-100 text-lime-800' : 'bg-red-100 text-red-800' }}">
                                {{ $viewingService->Stato ? 'Attivo' : 'Disattivo' }}
                            </span>
                            <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="px-4 pt-4 pb-2 sm:px-6 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i> Dati Servizio
                            </h3>
                            <div class="space-y-2">
                                <div class="flex"><span class="w-24 text-gray-600">Categoria:</span><span class="text-gray-800">{{ $viewingService->category->valore ?? '-' }}</span></div>
                                <div class="flex"><span class="w-24 text-gray-600">Prezzo:</span>
                                    <span class="text-gray-800">
                                        @if($viewingService->Prezzo_un)
                                            € {{ number_format($viewingService->Prezzo_un, 2) }}
                                            @if($viewingService->unitaMisura)
                                                / {{ $viewingService->unitaMisura->nome }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                                <div class="flex"><span class="w-24 text-gray-600">ID Servizio:</span><span class="text-gray-800 font-mono">{{ $viewingService->id }}</span></div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-calendar-alt mr-2 text-purple-500"></i> Date
                            </h3>
                            <div class="space-y-2">
                                <div class="flex"><span class="w-24 text-gray-600">Data creazione:</span><span class="text-gray-800">{{ $viewingService->created_at ? $viewingService->created_at->format('d/m/Y H:i') : '-' }}</span></div>
                                @if($viewingService->updated_at && $viewingService->created_at != $viewingService->updated_at)
                                <div class="flex"><span class="w-24 text-gray-600">Ultima modifica:</span><span class="text-gray-800">{{ $viewingService->updated_at->format('d/m/Y H:i') }}</span></div>
                                @endif
                            </div>
                        </div>
                        
                        @if($viewingService->Descrizione)
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-align-left mr-2 text-gray-500"></i> Descrizione
                            </h3>
                            <p class="text-gray-700">{{ $viewingService->Descrizione }}</p>
                        </div>
                        @endif
                        
                        @if($viewingService->Descr_fattura)
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-file-invoice mr-2 text-lime-500"></i> Descrizione Fattura
                            </h3>
                            <p class="text-gray-700">{{ $viewingService->Descr_fattura }}</p>
                        </div>
                        @endif
                        
                        <!-- Tracciamento -->
                        <div class="bg-gray-50 rounded-lg p-4 md:col-span-2">
                            <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">
                                <i class="fas fa-history mr-2 text-indigo-500"></i> Tracciamento
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Inserito da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <svg class="w-4 h-4 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingService->createdBy ? $viewingService->createdBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingService->created_at ? $viewingService->created_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($viewingService->updated_at && $viewingService->created_at != $viewingService->updated_at)
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Modificato da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span class="text-gray-800 font-medium">
                                                {{ $viewingService->updatedBy ? $viewingService->updatedBy->name : 'Sistema' }}
                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500 text-sm">
                                                {{ $viewingService->updated_at ? $viewingService->updated_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1 ml-6">
                                            ({{ $viewingService->updated_at->diffForHumans() }})
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="flex items-start">
                                    <div class="w-28 text-gray-600 text-sm pt-0.5">Modificato da:</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span class="text-gray-400 italic">Mai modificato</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_services'))
                    <button wire:click="editService({{ $viewingService->id }})" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Modifica
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA -->
    @if($showEditModal && $editingService)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" 
         x-show="open"
         x-init="$watch('open', value => { if (!value) $wire.closeEditModal() })"
         @keydown.escape.window="open = false">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 border-b pb-3">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Servizio
                        </h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="editTitolo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @error('editTitolo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                <select wire:model="editCategoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona categoria</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->valore }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                            <textarea wire:model="editDescrizione" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione Fattura</label>
                            <textarea wire:model="editDescrFattura" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prezzo</label>
                                <input type="number" step="0.01" wire:model="editPrezzo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                @error('editPrezzo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unità di misura</label>
                                <select wire:model="editUnitaMisura" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                                    <option value="">Seleziona unità</option>
                                    @foreach($unitaMisura as $um)
                                        <option value="{{ $um->id_um }}">{{ $um->nome }} ({{ $um->codice }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="editStato" class="rounded border-gray-300 text-lime-600">
                                <span class="ml-2 text-sm text-gray-700">Servizio attivo</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <button @click="open = false" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                        Annulla
                    </button>
                    <button wire:click="updateService" 
                            class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:text-sm">
                        <i class="fas fa-save mr-2"></i> Aggiorna
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>