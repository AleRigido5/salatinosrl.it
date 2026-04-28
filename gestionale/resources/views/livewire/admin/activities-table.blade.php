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
                                            <span class="text-sm text-gray-700">{{ $staffName }}</span>
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
                        
                        <!-- Lat/Long con tooltip modificabile (SOTTO il campo) -->
                        <td class="px-3 py-3 text-sm relative group">
                            @if($activity->Lat_Long)
                                <div x-data="{ 
                                    latLong: '{{ $activity->Lat_Long }}',
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: '{{ $activity->Lat_Long }}',
                                    
                                    saveLatLong() {
                                        this.isEditing = true;
                                        @this.call('updateLatLong', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                // Ricarica la pagina dopo il salvataggio
                                                window.location.reload();
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                            });
                                    }
                                }">
                                    <div class="font-mono text-xs text-gray-600 cursor-pointer hover:text-lime-600 hover:underline break-words whitespace-normal max-w-[120px]" 
                                        title="Clicca per modificare"
                                        x-on:click="showTooltip = true; editedValue = latLong">
                                        {{ $activity->Lat_Long }}
                                    </div>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[280px]"
                                        style="top: 100%; left: 0; margin-top: 10px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-4 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Latitudine / Longitudine</label>
                                        <input type="text" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="es. 45.123456, 12.123456"
                                            x-on:keydown.enter="saveLatLong()">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="saveLatLong()"
                                                    x-bind:disabled="isEditing"
                                                    class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                                <i class="fas fa-check" x-show="!isEditing"></i>
                                                <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                                <span x-show="!isEditing"> Salva</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div x-data="{ showTooltip: false, editedValue: '', isEditing: false }">
                                    <span class="text-gray-400 italic cursor-pointer hover:text-lime-600" 
                                        x-on:click="showTooltip = true">
                                        -
                                    </span>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[280px]"
                                        style="top: 100%; left: 0; margin-top: 10px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-4 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Latitudine / Longitudine</label>
                                        <input type="text" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="es. 45.123456, 12.123456"
                                            x-on:keydown.enter="isEditing = true; @this.call('updateLatLong', {{ $activity->id }}, editedValue).then(() => { window.location.reload(); })">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="isEditing = true; @this.call('updateLatLong', {{ $activity->id }}, editedValue).then(() => { window.location.reload(); })"
                                                    x-bind:disabled="isEditing"
                                                    class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                                <i class="fas fa-check" x-show="!isEditing"></i>
                                                <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                                <span x-show="!isEditing"> Salva</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>

                        <!-- Note -->
                        <td class="px-3 py-3 text-sm">
                            @if($activity->note)
                                <div class="break-words whitespace-normal max-w-[180px]" title="{{ $activity->note }}">
                                    {{ $activity->note }}
                                </div>
                            @else
                                <span class="text-gray-400 italic">
                                    -
                                </span>
                            @endif
                        </td>

                        <!-- Ettari (ha) con tooltip modificabile (SOTTO il campo) -->
                        <td class="px-3 py-3 text-sm whitespace-nowrap relative group">
                            @if($activity->ha)
                                <div x-data="{ 
                                    ha: '{{ $activity->ha }}',
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: '{{ $activity->ha }}',
                                    
                                    saveHa() {
                                        this.isEditing = true;
                                        @this.call('updateHa', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                window.location.reload();
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                            });
                                    }
                                }">
                                    <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline" 
                                        x-on:click="showTooltip = true; editedValue = ha">
                                        {{ $activity->ha }}
                                    </span>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                        style="top: 100%; left: 0; margin-top: 10px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-4 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Ettari (ha)</label>
                                        <input type="number" 
                                            step="0.01" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="0.00"
                                            x-on:keydown.enter="saveHa()">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="saveHa()"
                                                    x-bind:disabled="isEditing"
                                                    class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                                <i class="fas fa-check" x-show="!isEditing"></i>
                                                <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                                <span x-show="!isEditing"> Salva</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div x-data="{ showTooltip: false, editedValue: '', isEditing: false }">
                                    <span class="text-gray-400 italic cursor-pointer hover:text-lime-600" 
                                        x-on:click="showTooltip = true">
                                        -
                                    </span>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[200px]"
                                        style="top: 100%; left: 0; margin-top: 10px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-4 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Ettari (ha)</label>
                                        <input type="number" 
                                            step="0.01" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="0.00"
                                            x-on:keydown.enter="isEditing = true; @this.call('updateHa', {{ $activity->id }}, editedValue).then(() => { window.location.reload(); })">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="isEditing = true; @this.call('updateHa', {{ $activity->id }}, editedValue).then(() => { window.location.reload(); })"
                                                    x-bind:disabled="isEditing"
                                                    class="px-2 py-1 text-xs bg-lime-500 hover:bg-lime-600 text-white rounded disabled:opacity-50">
                                                <i class="fas fa-check" x-show="!isEditing"></i>
                                                <i class="fas fa-spinner fa-spin" x-show="isEditing"></i>
                                                <span x-show="!isEditing"> Salva</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeViewModal()" aria-hidden="true"></div>
            
            <!-- Questo span è necessario per l'allineamento verticale -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white rounded-lg max-h-[90vh] flex flex-col">
                    <!-- Header fisso -->
                    <div class="px-6 pt-4 pb-3 border-b sticky top-0 bg-white rounded-t-lg z-10">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i> Dettaglio Attività
                            </h2>
                            <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Contenuto scrollabile -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <!-- Informazioni principali in griglia -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Data -->
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Data</span>
                                <p class="font-medium text-gray-800 mt-1">{{ $this->formatDate($viewingActivity->data_activities) }}</p>
                            </div>
                            
                            <!-- Centro di Costo -->
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Centro di Costo</span>
                                <p class="font-medium text-gray-800 mt-1">{{ $viewingActivity->costCenter->Nome ?? '-' }}</p>
                                @if($viewingActivity->costCenter && $viewingActivity->costCenter->Localita)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $viewingActivity->costCenter->Localita }}</p>
                                @endif
                            </div>
                            
                            <!-- Servizio -->
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Servizio</span>
                                <p class="font-medium text-gray-800 mt-1">{{ $viewingActivity->service->Titolo ?? '-' }}</p>
                                @if($viewingActivity->service && $viewingActivity->service->Descrizione)
                                <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($viewingActivity->service->Descrizione, 60) }}</p>
                                @endif
                            </div>
                            
                            <!-- Cliente/Fornitore -->
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Cliente/Fornitore</span>
                                <p class="font-medium text-gray-800 mt-1">
                                    {{ optional($viewingActivity->entity)->ragione_sociale ?: (optional($viewingActivity->entity)->nome . ' ' . optional($viewingActivity->entity)->cognome) ?? '-' }}
                                </p>
                                @if($viewingActivity->entity && $viewingActivity->entity->partita_iva)
                                <p class="text-xs text-gray-500 mt-0.5">P.IVA: {{ $viewingActivity->entity->partita_iva }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Note -->
                        @if($viewingActivity->note)
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase font-semibold">Note</span>
                            <div class="mt-1 text-gray-700 whitespace-pre-wrap">{{ $viewingActivity->note }}</div>
                        </div>
                        @endif
                        
                        <!-- Tracciamento (Created/Updated) -->
                        <div class="border-t pt-3 mt-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-500">
                                @if($viewingActivity->created_at)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-green-500"></i>
                                    <span>Inserito il {{ $viewingActivity->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                                @if($viewingActivity->updated_at && $viewingActivity->created_at != $viewingActivity->updated_at)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-edit text-blue-500"></i>
                                    <span>Modificato il {{ $viewingActivity->updated_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer fisso con pulsanti -->
                    <div class="px-6 py-3 border-t bg-gray-50 rounded-b-lg sticky bottom-0">
                        <div class="flex justify-end gap-3">
                            <button wire:click="closeViewModal" 
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i> Chiudi
                            </button>
                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                            <button wire:click="editActivity({{ $viewingActivity->id }})" 
                                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-md text-white transition-colors">
                                <i class="fas fa-edit mr-2"></i> Modifica
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL MODIFICA -->
    @if($showEditModal && $editingActivity)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeEditModal()" aria-hidden="true"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white rounded-lg max-h-[90vh] flex flex-col">
                    <!-- Header fisso -->
                    <div class="px-6 pt-4 pb-3 border-b sticky top-0 bg-white rounded-t-lg z-10">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-edit text-yellow-500 mr-2"></i> Modifica Attività
                            </h2>
                            <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Form scrollabile -->
                    <form wire:submit.prevent="updateActivity" class="flex-1 overflow-y-auto">
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Data -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Data <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" 
                                        wire:model="editDate" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                    @error('editDate') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Centro di Costo Autocomplete -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Centro di Costo <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                        <div class="relative">
                                            <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                            <input type="text" 
                                                id="edit_cost_center_input"
                                                wire:model.live.debounce.300ms="editCostCenterSearch" 
                                                x-on:focus="open = true"
                                                x-on:input="open = true; @this.set('editCostCenterSearch', $event.target.value)"
                                                placeholder="Cerca centro di costo..."
                                                class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                                autocomplete="off">
                                            @if($editCostCenter)
                                            <button type="button" 
                                                wire:click="clearEditCostCenter" 
                                                x-on:click="document.getElementById('edit_cost_center_input').value = ''"
                                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-times-circle text-sm"></i>
                                            </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Dropdown risultati -->
                                        <div x-show="open && @entangle('showEditCostCenterDropdown')" 
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            @if($filteredEditCostCenters && $filteredEditCostCenters->count() > 0)
                                                @foreach($filteredEditCostCenters as $cc)
                                                <div 
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('edit_cost_center_input').value = '{{ addslashes($cc->Nome) }}';
                                                        @this.set('editCostCenterSearch', '{{ addslashes($cc->Nome) }}');
                                                        @this.set('editCostCenter', {{ $cc->id }});
                                                        @this.set('showEditCostCenterDropdown', false);
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
                                    @error('editCostCenter') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Servizio Autocomplete -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Servizio <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                        <div class="relative">
                                            <i class="fas fa-concierge-bell absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                            <input type="text" 
                                                id="edit_service_input"
                                                wire:model.live.debounce.300ms="editServiceSearch" 
                                                x-on:focus="open = true"
                                                x-on:input="open = true; @this.set('editServiceSearch', $event.target.value)"
                                                placeholder="Cerca servizio..."
                                                class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                                autocomplete="off">
                                            @if($editService)
                                            <button type="button" 
                                                wire:click="clearEditService" 
                                                x-on:click="document.getElementById('edit_service_input').value = ''"
                                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-times-circle text-sm"></i>
                                            </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Dropdown risultati -->
                                        <div x-show="open && @entangle('showEditServiceDropdown')" 
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            @if($filteredEditServices && $filteredEditServices->count() > 0)
                                                @foreach($filteredEditServices as $service)
                                                <div 
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('edit_service_input').value = '{{ addslashes($service->Titolo) }}';
                                                        @this.set('editServiceSearch', '{{ addslashes($service->Titolo) }}');
                                                        @this.set('editService', {{ $service->id }});
                                                        @this.set('showEditServiceDropdown', false);
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
                                    @error('editService') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Cliente/Fornitore Autocomplete -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Cliente/Fornitore <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                        <div class="relative">
                                            <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                            <input type="text" 
                                                id="edit_entity_input"
                                                wire:model.live.debounce.300ms="editEntitySearch" 
                                                x-on:focus="open = true"
                                                x-on:input="open = true; @this.set('editEntitySearch', $event.target.value)"
                                                placeholder="Cerca cliente/fornitore..."
                                                class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                                autocomplete="off">
                                            @if($editEntity)
                                            <button type="button" 
                                                wire:click="clearEditEntity" 
                                                x-on:click="document.getElementById('edit_entity_input').value = ''"
                                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-times-circle text-sm"></i>
                                            </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Dropdown risultati -->
                                        <div x-show="open && @entangle('showEditEntityDropdown')" 
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            @if($filteredEditEntities && $filteredEditEntities->count() > 0)
                                                @foreach($filteredEditEntities as $entity)
                                                @php
                                                    $entityName = addslashes($entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome));
                                                @endphp
                                                <div 
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('edit_entity_input').value = '{{ $entityName }}';
                                                        @this.set('editEntitySearch', '{{ $entityName }}');
                                                        @this.set('editEntity', {{ $entity->id_cliente }});
                                                        @this.set('showEditEntityDropdown', false);
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
                                    @error('editEntity') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Rif. Fattura -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Rif. Fattura</label>
                                    <input type="text" 
                                        wire:model="editInvoiceRef" 
                                        placeholder="es. FV-2024-001"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                </div>
                                
                                <!-- Imponibile -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Imponibile (€)</label>
                                    <input type="number" 
                                        step="0.01" 
                                        wire:model="editImponibile" 
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                </div>
                                
                                <!-- Costi Materiali -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Costi Materiali (€)</label>
                                    <input type="number" 
                                        step="0.01" 
                                        wire:model="editCostiMat" 
                                        placeholder="0.00"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                </div>
                                
                                <!-- Totale (solo visualizzazione) -->
                                <div class="bg-lime-50 p-3 rounded-lg border border-lime-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Totale (calcolato)</label>
                                    <p class="text-lg font-bold text-lime-600">
                                        {{ $this->formatCurrency(($editImponibile ?? 0) + ($editCostiMat ?? 0)) }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Note (full width) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                <textarea wire:model="editNote" 
                                        rows="4" 
                                        placeholder="Inserisci note aggiuntive..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Footer fisso con pulsanti -->
                    <div class="px-6 py-3 border-t bg-gray-50 rounded-b-lg sticky bottom-0">
                        <div class="flex justify-end gap-3">
                            <button type="button" 
                                    wire:click="closeEditModal" 
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i> Annulla
                            </button>
                            <button type="button" 
                                    wire:click="updateActivity" 
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-md text-white transition-colors disabled:opacity-50">
                                <span wire:loading.remove><i class="fas fa-save mr-2"></i> Salva</span>
                                <span wire:loading><i class="fas fa-spinner fa-spin mr-2"></i> Salvataggio...</span>
                            </button>
                        </div>
                    </div>
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