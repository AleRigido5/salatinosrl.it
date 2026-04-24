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
        
        <!-- Filtri Avanzati con Autocomplete -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cerca fattura, note, ha, lat/long..." 
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
            </div>
            
            <!-- Centro di Costo Autocomplete -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" 
                        id="cost_center_input"
                        wire:model.live.debounce.300ms="costCenterSearch" 
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('costCenterSearch', $event.target.value)"
                        placeholder="Cerca centro di costo..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($costCenterFilter)
                    <button type="button" 
                        wire:click="clearCostCenter" 
                        x-on:click="document.getElementById('cost_center_input').value = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>
                    @endif
                </div>
                
                <!-- Dropdown risultati -->
                <div x-show="open && @entangle('showCostCenterDropdown')" 
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($filteredCostCenters && $filteredCostCenters->count() > 0)
                        @foreach($filteredCostCenters as $cc)
                        <div 
                            x-on:click="
                                open = false;
                                document.getElementById('cost_center_input').value = '{{ addslashes($cc->Nome) }}';
                                @this.set('costCenterSearch', '{{ addslashes($cc->Nome) }}');
                                @this.set('costCenterFilter', {{ $cc->id }});
                                @this.set('costCenterName', '{{ addslashes($cc->Nome) }}');
                                @this.set('showCostCenterDropdown', false);
                                @this.call('resetPage');
                            " 
                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            <div class="font-medium text-gray-800">{{ $cc->Nome }}</div>
                            @if($cc->Localita)
                            <div class="text-xs text-gray-500">{{ $cc->Localita }}</div>
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
            
            <!-- Servizio Autocomplete -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <div class="relative">
                    <i class="fas fa-concierge-bell absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text" 
                        id="service_input"
                        wire:model.live.debounce.300ms="serviceSearch" 
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('serviceSearch', $event.target.value)"
                        placeholder="Cerca servizio..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($serviceFilter)
                    <button type="button" 
                        wire:click="clearService" 
                        x-on:click="document.getElementById('service_input').value = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>
                    @endif
                </div>
                
                <!-- Dropdown risultati -->
                <div x-show="open && @entangle('showServiceDropdown')" 
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($filteredServices && $filteredServices->count() > 0)
                        @foreach($filteredServices as $service)
                        <div 
                            x-on:click="
                                open = false;
                                document.getElementById('service_input').value = '{{ addslashes($service->Titolo) }}';
                                @this.set('serviceSearch', '{{ addslashes($service->Titolo) }}');
                                @this.set('serviceFilter', {{ $service->id }});
                                @this.set('serviceName', '{{ addslashes($service->Titolo) }}');
                                @this.set('showServiceDropdown', false);
                                @this.call('resetPage');
                            " 
                            class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                            <div class="font-medium text-gray-800">{{ $service->Titolo }}</div>
                            @if($service->Descrizione)
                            <div class="text-xs text-gray-500 truncate">{{ $service->Descrizione }}</div>
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
            
            <!-- Cliente/Fornitore Autocomplete -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
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
                    <button type="button" 
                        wire:click="clearEntity" 
                        x-on:click="document.getElementById('entity_input').value = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>
                    @endif
                </div>
                
                <!-- Dropdown risultati -->
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
            
            <!-- Per Page Selector -->
            <div class="flex items-center space-x-2">
                <select wire:model.live="perPage" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                    <option value="15">15 per pagina</option>
                    <option value="25">25 per pagina</option>
                    <option value="50">50 per pagina</option>
                    <option value="100">100 per pagina</option>
                </select>
                
                @if($search || $costCenterFilter || $serviceFilter || $entityFilter || !$useDateFilter)
                <button type="button" wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700" title="Resetta tutti i filtri">
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
                {{ $costCenterName }}
                <button 
                    wire:click="clearCostCenter" 
                    x-on:click="document.getElementById('cost_center_input').value = ''"
                    class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            
            @if($serviceFilter)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-concierge-bell mr-1 text-xs"></i>
                {{ $serviceName }}
                <button 
                    wire:click="clearService" 
                    x-on:click="document.getElementById('service_input').value = ''"
                    class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
            @endif
            
            @if($entityFilter)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-user mr-1 text-xs"></i>
                {{ $entityName }}
                <button 
                    wire:click="clearEntity" 
                    x-on:click="document.getElementById('entity_input').value = ''"
                    class="ml-1 hover:text-lime-900">
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
            <table class="table-fixed w-full divide-y divide-gray-200" style="min-width: 1200px;">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 w-24" wire:click="sortBy('data_activities')">
                            <div class="flex items-center space-x-1">
                                <span>Data</span>
                                @if($sortField === 'data_activities')
                                    <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-gray-600 text-xs"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Cliente / Cantiere</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-64">Personale (ore)</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-44">Titolo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Lat/Long</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Note</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ha</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                    <tr wire:key="activity-{{ $activity->id }}" class="hover:bg-gray-50 transition-colors">
                        <!-- Data Attività -->
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ $this->formatDate($activity->data_activities) }}
                        </td>
                        
                        <!-- Cliente / Cantiere -->
                        <td class="px-3 py-3 text-sm">
                            @php
                                $entity = $activity->entity;
                                $costCenter = $activity->costCenter;
                                $clienteNome = $entity ? ($entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome)) : '-';
                                $cantiereNome = $costCenter ? $costCenter->Nome : '-';
                            @endphp
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-800 truncate max-w-[180px]" title="{{ $clienteNome }}">{{ $clienteNome }}</span>
                                @if($cantiereNome != '-')
                                    <span class="text-xs text-gray-500 mt-0.5 truncate max-w-[180px]" title="{{ $cantiereNome }}">{{ $cantiereNome }}</span>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Personale (ore) -->
                        <td class="px-3 py-3 text-sm">
                            @if($activity->staffDetails && $activity->staffDetails->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($activity->staffDetails as $staffDetail)
                                        @php
                                            $staff = $staffDetail->staff;
                                            $staffName = $staff ? ($staff->CognomePers . ' ' . $staff->NomePers) : '-';
                                            $ore = $staffDetail->n_ore ?: 0;
                                        @endphp
                                        <div class="text-sm whitespace-nowrap">
                                            <span class="text-gray-700">{{ $staffName }}</span>
                                            <span class="text-xs text-gray-500 ml-1">({{ number_format($ore, 1) }} h)</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        
                        <!-- Titolo (Servizio) -->
                        <td class="px-3 py-3 text-sm">
                            @if($activity->service)
                                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700 truncate max-w-[160px]" title="{{ $activity->service->Titolo ?? '-' }}">
                                    {{ Str::limit($activity->service->Titolo ?? '-', 30) }}
                                </span>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        
                        <!-- Lat/Long -->
                        <td class="px-3 py-3 text-sm">
                            @if($activity->Lat_Long)
                                <span class="font-mono text-xs text-gray-600 truncate block max-w-[120px]" title="{{ $activity->Lat_Long }}">
                                    {{ Str::limit($activity->Lat_Long, 20) }}
                                </span>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        
                        <!-- Note -->
                        <td class="px-3 py-3 text-sm text-gray-500">
                            @if($activity->note)
                                <div class="truncate max-w-[180px]" title="{{ $activity->note }}">
                                    {{ Str::limit($activity->note, 40) }}
                                </div>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        
                        <!-- Ettari (ha) -->
                        <td class="px-3 py-3 text-sm whitespace-nowrap">
                            @if($activity->ha)
                                <span class="font-medium">{{ $activity->ha }}</span>
                            @else
                                <span class="text-gray-400 italic">-</span>
                            @endif
                        </td>
                        
                        <!-- Azioni -->
                        <td class="px-3 py-3 text-sm whitespace-nowrap">
                            <div class="flex items-center justify-center space-x-2">
                                @if(auth()->guard('admin')->user()->hasPermission('view_activities'))
                                <button wire:click="viewActivity({{ $activity->id }})" 
                                        class="text-blue-500 hover:text-blue-700 transition-colors p-1" 
                                        title="Visualizza">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                @endif
                                @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                <button wire:click="editActivity({{ $activity->id }})" 
                                        class="text-yellow-500 hover:text-yellow-700 transition-colors p-1" 
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-tasks text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna attività trovata</p>
                            @if(auth()->guard('admin')->user()->hasPermission('create_activities'))
                            <a href="{{ route('admin.activities.create') }}" class="mt-2 text-lime-500 hover:text-lime-600 transition-colors inline-flex items-center">
                                <i class="fas fa-plus-circle mr-1"></i> Clicca per aggiungere
                            </a>
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

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Ascolta gli eventi per pulire i campi
            Livewire.on('clear-cost-center', () => {
                document.getElementById('cost_center_input').value = '';
            });
            
            Livewire.on('clear-service', () => {
                document.getElementById('service_input').value = '';
            });
            
            Livewire.on('clear-entity', () => {
                document.getElementById('entity_input').value = '';
            });
        });
    </script>
    @endpush
</div>