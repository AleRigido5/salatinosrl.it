<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <!-- Custom Date Range -->
        <div class="flex flex-wrap items-center justify-center gap-3 mb-4 pb-3 border-b border-gray-200">
            <input type="date" wire:model.live="dateFrom" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md">
            <span class="text-gray-500">→</span>
            <input type="date" wire:model.live="dateTo" class="text-sm px-3 py-1.5 border border-gray-300 rounded-md">
            @if($dateFrom || $dateTo)
            <button wire:click="applyCustomDateRange" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-1.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            Applica
            </button>
            @endif
        </div>
        
        <!-- Advanced Filters with Autocomplete -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- Search -->
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
            <input type="text" 
                wire:model.live.debounce.500ms="search" 
                placeholder="Cerca fattura, note..." 
                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
        </div>
        
        <!-- Centro di Costo Autocomplete -->
        <div class="relative">
            <div class="relative">
                <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="costCenterSearch" 
                    wire:focus="$set('showCostCenterDropdown', true)"
                    placeholder="Cerca centro di costo..."
                    class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                @if($costCenterFilter)
                <button wire:click="clearCostCenter" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
                @endif
            </div>
            @if($showCostCenterDropdown && $costCenterSearch && isset($filteredCostCenters) && $filteredCostCenters->count() > 0)
            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                @foreach($filteredCostCenters as $cc)
                <div wire:click="selectCostCenter({{ $cc->id }}, '{{ addslashes($cc->Nome) }}')" 
                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                    <div class="font-medium text-gray-800">{{ $cc->Nome }}</div>
                    @if($cc->Localita)
                    <div class="text-xs text-gray-500">{{ $cc->Localita }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        
        <!-- Servizio Autocomplete -->
        <div class="relative">
            <div class="relative">
                <i class="fas fa-concierge-bell absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="serviceSearch" 
                    wire:focus="$set('showServiceDropdown', true)"
                    placeholder="Cerca servizio..."
                    class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                @if($serviceFilter)
                <button wire:click="clearService" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
                @endif
            </div>
            @if($showServiceDropdown && $serviceSearch && isset($filteredServices) && $filteredServices->count() > 0)
            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                @foreach($filteredServices as $service)
                <div wire:click="selectService({{ $service->id }}, '{{ addslashes($service->Titolo) }}')" 
                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                    <div class="font-medium text-gray-800">{{ $service->Titolo }}</div>
                    @if($service->Descrizione)
                    <div class="text-xs text-gray-500 truncate">{{ $service->Descrizione }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        
        <!-- Cliente/Fornitore Autocomplete -->
        <div class="relative">
            <div class="relative">
                <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="entitySearch" 
                    wire:focus="$set('showEntityDropdown', true)"
                    placeholder="Cerca cliente/fornitore..."
                    class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                @if($entityFilter)
                <button wire:click="clearEntity" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times-circle text-sm"></i>
                </button>
                @endif
            </div>
            @if($showEntityDropdown && $entitySearch && isset($filteredEntities) && $filteredEntities->count() > 0)
            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                @foreach($filteredEntities as $entity)
                @php
                    $entityName = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                @endphp
                <div wire:click="selectEntity({{ $entity->id_cliente }}, '{{ addslashes($entityName) }}')" 
                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                    <div class="font-medium text-gray-800">{{ $entityName }}</div>
                    @if($entity->partita_iva)
                    <div class="text-xs text-gray-500">P.IVA: {{ $entity->partita_iva }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        
        <!-- Per Page Selector -->
        <div class="flex items-center space-x-2">
            <select wire:model.live="perPage" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                <option value="15">15 per pagina</option>
                <option value="25">25 per pagina</option>
                <option value="50">50 per pagina</option>
                <option value="100">100 per pagina</option>
            </select>
            
            @if($search || $costCenterFilter || $serviceFilter || $entityFilter || !$useDateFilter)
            <button wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700" title="Resetta tutti i filtri">
                <i class="fas fa-sync-alt"></i>
            </button>
            @endif
        </div>
    </div>
            
        <!-- JavaScript per chiudere i dropdown quando si clicca fuori -->
        <script>
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.relative')) {
                    @this.set('showCostCenterDropdown', false);
                    @this.set('showServiceDropdown', false);
                    @this.set('showEntityDropdown', false);
                }
            });
        </script>
        
        <!-- Active Filters Tags -->
        @if($search || $costCenterFilter || $serviceFilter || $entityFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
            <span class="text-xs text-gray-500">Filtri attivi:</span>
            @if($search)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                Ricerca: "{{ $search }}"
                <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($costCenterFilter)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-building mr-1 text-xs"></i>
                {{ $costCenterName ?? $costCenterFilter }}
                <button wire:click="clearCostCenter" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($serviceFilter)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-concierge-bell mr-1 text-xs"></i>
                {{ $serviceName ?? $serviceFilter }}
                <button wire:click="clearService" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            @if($entityFilter)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-user mr-1 text-xs"></i>
                {{ $entityName ?? $entityFilter }}
                <button wire:click="clearEntity" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- Tabella Attività -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_activities')">
                            <div class="flex items-center space-x-1">
                                <span>Data</span>
                                @if($sortField === 'data_activities')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Centro di Costo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servizio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente/Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Riferimenti</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('imponibile')">
                            <div class="flex items-center justify-end space-x-1">
                                <span>Importo</span>
                                @if($sortField === 'imponibile')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                    <tr wire:key="activity-{{ $activity->id }}" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ $this->formatDate($activity->data_activities) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($activity->costCenter)
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    {{ $activity->costCenter->Nome ?? '-' }}
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-400">N/D</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($activity->service)
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                    {{ $activity->service->Titolo ?? '-' }}
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-400">N/D</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 max-w-[200px] truncate">
                            @php
                                $entity = $activity->entity;
                            @endphp
                            @if($entity)
                                <span title="{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}">
                                    {{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) ?: '-' }}
                                </span>
                            @else
                                <span class="text-gray-400 italic">N/D</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <div class="space-y-0.5">
                                @if($activity->invoice_references)
                                <div class="text-xs font-mono">{{ $activity->invoice_references }}</div>
                                @endif
                                @if($activity->note)
                                <div class="text-xs text-gray-400 truncate max-w-[150px]" title="{{ $activity->note }}">
                                    {{ Str::limit($activity->note, 30) }}
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">
                            {{ $this->formatCurrency($activity->imponibile) }}
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <div class="flex items-center justify-center space-x-2">
                                @if(auth()->guard('admin')->user()->hasPermission('view_activities'))
                                <button wire:click="viewActivity({{ $activity->id }})" 
                                        class="text-blue-500 hover:text-blue-700 transition-colors" 
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                @endif
                                @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                <button wire:click="editActivity({{ $activity->id }})" 
                                        class="text-yellow-500 hover:text-yellow-700 transition-colors" 
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-tasks text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna attività trovata</p>
                            @if(auth()->guard('admin')->user()->hasPermission('create_activities'))
                            <button wire:click="openCreateModal" class="mt-2 text-lime-500 hover:text-lime-600 transition-colors">
                                <i class="fas fa-plus-circle"></i> Clicca per aggiungere
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($activities->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }} di {{ $activities->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $activities->links() }}
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
    @if($showViewModal && $viewingActivity)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeViewModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-4 pt-4 pb-2 border-b">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i> Dettaglio Attività
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="px-4 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Data</span>
                            <p class="font-medium text-gray-800">{{ $this->formatDate($viewingActivity->data_activities) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Centro di Costo</span>
                            <p class="font-medium text-gray-800">{{ $viewingActivity->costCenter->Nome ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Servizio</span>
                            <p class="font-medium text-gray-800">{{ $viewingActivity->service->Titolo ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Cliente/Fornitore</span>
                            <p class="font-medium text-gray-800">{{ optional($viewingActivity->entity)->ragione_sociale ?: (optional($viewingActivity->entity)->nome . ' ' . optional($viewingActivity->entity)->cognome) ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Rif. Fattura</span>
                            <p class="font-mono text-sm text-gray-800">{{ $viewingActivity->invoice_references ?: '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Imponibile</span>
                            <p class="font-medium text-green-600">{{ $this->formatCurrency($viewingActivity->imponibile) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Costi Materiali</span>
                            <p class="font-medium text-orange-600">{{ $this->formatCurrency($viewingActivity->costi_mat) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Totale</span>
                            <p class="font-bold text-lime-600">{{ $this->formatCurrency(($viewingActivity->imponibile ?? 0) + ($viewingActivity->costi_mat ?? 0)) }}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase">Note</span>
                            <p class="text-gray-700">{{ $viewingActivity->note ?: '-' }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end gap-2">
                    <button wire:click="closeViewModal" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition">
                        Chiudi
                    </button>
                    @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                    <button wire:click="editActivity({{ $viewingActivity->id }})" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition">
                        <i class="fas fa-edit mr-1"></i> Modifica
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA -->
    @if($showEditModal && $editingActivity)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeEditModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-4 pt-4 pb-2 border-b">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-edit text-yellow-500 mr-2"></i> Modifica Attività
                    </h2>
                </div>
                <div class="px-4 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                            <input type="date" wire:model="editDate" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo *</label>
                            <select wire:model="editCostCenter" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($costCenters as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->Nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Servizio *</label>
                            <select wire:model="editService" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->Titolo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente/Fornitore *</label>
                            <select wire:model="editEntity" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($entities as $entity)
                                <option value="{{ $entity->id_cliente }}">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rif. Fattura</label>
                            <input type="text" wire:model="editInvoiceRef" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imponibile</label>
                            <input type="number" step="0.01" wire:model="editImponibile" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Costi Materiali</label>
                            <input type="number" step="0.01" wire:model="editCostiMat" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="editNote" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end gap-2">
                    <button wire:click="closeEditModal" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition">
                        Annulla
                    </button>
                    <button wire:click="updateActivity" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition">
                        <i class="fas fa-save mr-1"></i> Salva
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CREAZIONE -->
    @if($showCreateModal)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeCreateModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-4 pt-4 pb-2 border-b">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i> Nuova Attività
                    </h2>
                </div>
                <div class="px-4 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                            <input type="date" wire:model="createDate" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Centro di Costo *</label>
                            <select wire:model="createCostCenter" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($costCenters as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->Nome }}</option>
                                @endforeach
                            </select>
                            @error('createCostCenter') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Servizio *</label>
                            <select wire:model="createService" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->Titolo }}</option>
                                @endforeach
                            </select>
                            @error('createService') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente/Fornitore *</label>
                            <select wire:model="createEntity" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                                <option value="">Seleziona...</option>
                                @foreach($entities as $entity)
                                <option value="{{ $entity->id_cliente }}">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</option>
                                @endforeach
                            </select>
                            @error('createEntity') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rif. Fattura</label>
                            <input type="text" wire:model="createInvoiceRef" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imponibile</label>
                            <input type="number" step="0.01" wire:model="createImponibile" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Costi Materiali</label>
                            <input type="number" step="0.01" wire:model="createCostiMat" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                            <textarea wire:model="createNote" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-lime-500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end gap-2">
                    <button wire:click="closeCreateModal" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition">
                        Annulla
                    </button>
                    <button wire:click="saveActivity" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition">
                        <i class="fas fa-save mr-1"></i> Salva
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>