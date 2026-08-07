<div>
    <!-- Filtri e Ricerca -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        <!-- Filtri Data con componente DateRangeFilter -->
        <div class="p-4">
            @livewire('components.date-range-filter', [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo
            ], key('date-filter-' . $dateFrom . $dateTo))
        </div>
        
        <!-- Filtri Avanzati con Autocomplete (invariati) -->
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
            
            <!-- Per Page Selector con opzioni Tutti, 200, 100 e POSIZIONI -->
            <div class="flex items-center space-x-2">
                <!-- Select Posizioni -->
                <select wire:model.live="positionFilter" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Posizioni</option>
                    <option value="aperte">Aperte</option>
                    <option value="interne">Interne</option>
                </select>
                
                <!-- Select Per Page -->
                <select wire:model.live="perPage" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                    <option value="10000">Tutti</option>
                    <option value="200">200</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        
        <!-- Active Filters Tags -->
        @if($search || $costCenterFilter || $serviceFilter || $entityFilter || request('date_from') || request('date_to'))
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

            @if(request('date_from') || request('date_to'))
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-lime-100 text-lime-800">
                <i class="fas fa-calendar mr-1 text-xs"></i>
                {{ request('date_from', '...') }} → {{ request('date_to', '...') }}
                <a href="{{ route('admin.activities.index') }}" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </a>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- JavaScript per i filtri data con navigazione mensile -->
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const applyBtn = document.getElementById('applyDateRange');
            const prevMonthBtn = document.getElementById('prevMonth');
            const nextMonthBtn = document.getElementById('nextMonth');
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');
            const baseUrl = '{{ route("admin.activities.index") }}';
            
            // Funzione per ottenere il primo giorno del mese
            function getFirstDayOfMonth(year, month) {
                return `${year}-${month.toString().padStart(2, '0')}-01`;
            }
            
            // Funzione per ottenere l'ultimo giorno del mese
            function getLastDayOfMonth(year, month) {
                const lastDay = new Date(year, month, 0).getDate();
                return `${year}-${month.toString().padStart(2, '0')}-${lastDay}`;
            }
            
            // Funzione per aggiornare i campi data con il mese selezionato
            function updateDateFieldsWithMonth() {
                const year = parseInt(yearSelect.value);
                const month = parseInt(monthSelect.value);
                
                dateFrom.value = getFirstDayOfMonth(year, month);
                dateTo.value = getLastDayOfMonth(year, month);
            }
            
            // Funzione per sincronizzare i select con le date correnti
            function syncSelectsWithDates() {
                const fromDate = dateFrom.value;
                const toDate = dateTo.value;
                
                if (fromDate && toDate) {
                    const fromParts = fromDate.split('-');
                    const toParts = toDate.split('-');
                    
                    if (fromParts.length === 3 && toParts.length === 3) {
                        const fromYear = parseInt(fromParts[0]);
                        const fromMonth = parseInt(fromParts[1]);
                        const fromDay = parseInt(fromParts[2]);
                        const toYear = parseInt(toParts[0]);
                        const toMonth = parseInt(toParts[1]);
                        const toDay = parseInt(toParts[2]);
                        
                        const lastDayOfMonth = new Date(fromYear, fromMonth, 0).getDate();
                        
                        // Verifica se è un mese intero
                        if (fromYear === toYear && fromMonth === toMonth && fromDay === 1 && toDay === lastDayOfMonth) {
                            monthSelect.value = fromMonth.toString().padStart(2, '0');
                            yearSelect.value = fromYear;
                            return true;
                        }
                    }
                }
                return false;
            }
            
            // Naviga al mese precedente
            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', function() {
                    let year = parseInt(yearSelect.value);
                    let month = parseInt(monthSelect.value);
                    
                    if (month === 1) {
                        month = 12;
                        year--;
                    } else {
                        month--;
                    }
                    
                    monthSelect.value = month.toString().padStart(2, '0');
                    yearSelect.value = year;
                    updateDateFieldsWithMonth();
                });
            }
            
            // Naviga al mese successivo
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', function() {
                    let year = parseInt(yearSelect.value);
                    let month = parseInt(monthSelect.value);
                    
                    if (month === 12) {
                        month = 1;
                        year++;
                    } else {
                        month++;
                    }
                    
                    monthSelect.value = month.toString().padStart(2, '0');
                    yearSelect.value = year;
                    updateDateFieldsWithMonth();
                });
            }
            
            // Quando si cambia mese o anno manualmente
            if (monthSelect) {
                monthSelect.addEventListener('change', updateDateFieldsWithMonth);
            }
            
            if (yearSelect) {
                yearSelect.addEventListener('change', updateDateFieldsWithMonth);
            }
            
            // Applica i filtri data
            if (applyBtn) {
                applyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const params = new URLSearchParams(window.location.search);
                    
                    if (dateFrom.value) {
                        params.set('date_from', dateFrom.value);
                    } else {
                        params.delete('date_from');
                    }
                    
                    if (dateTo.value) {
                        params.set('date_to', dateTo.value);
                    } else {
                        params.delete('date_to');
                    }
                    
                    const queryString = params.toString();
                    const url = queryString ? baseUrl + '?' + queryString : baseUrl;
                    window.location.href = url;
                });
            }
            
            // Inizializzazione
            const urlParams = new URLSearchParams(window.location.search);
            const urlDateFrom = urlParams.get('date_from');
            const urlDateTo = urlParams.get('date_to');
            
            if (urlDateFrom && urlDateTo) {
                dateFrom.value = urlDateFrom;
                dateTo.value = urlDateTo;
                const synced = syncSelectsWithDates();
                if (!synced) {
                    // Se non è un mese intero, resetta i select al mese corrente della data da
                    const fromParts = urlDateFrom.split('-');
                    if (fromParts.length === 3) {
                        monthSelect.value = fromParts[1];
                        yearSelect.value = fromParts[0];
                    }
                }
            } else {
                // Nessuna data nell'URL, inizializza con il mese corrente
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;
                
                monthSelect.value = currentMonth.toString().padStart(2, '0');
                yearSelect.value = currentYear;
                updateDateFieldsWithMonth();
            }
        });
    </script>
    @endpush

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
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Rif. Fattura</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $lastDate = null;
                    @endphp
                    
                    @forelse($activities as $activity)
                    @php
                        $currentDate = $activity->data_activities;
                        $showSeparator = ($lastDate !== null && $lastDate != $currentDate);
                    @endphp
                    
                    <!-- Riga di separazione tra giorni diversi -->
                    @if($showSeparator)
                    @php
                        $date = \Carbon\Carbon::parse($currentDate);
                        $giorniSettimana = [
                            'Monday' => 'Lunedì',
                            'Tuesday' => 'Martedì',
                            'Wednesday' => 'Mercoledì',
                            'Thursday' => 'Giovedì',
                            'Friday' => 'Venerdì',
                            'Saturday' => 'Sabato',
                            'Sunday' => 'Domenica'
                        ];
                        $mesi = [
                            'January' => 'Gennaio',
                            'February' => 'Febbraio',
                            'March' => 'Marzo',
                            'April' => 'Aprile',
                            'May' => 'Maggio',
                            'June' => 'Giugno',
                            'July' => 'Luglio',
                            'August' => 'Agosto',
                            'September' => 'Settembre',
                            'October' => 'Ottobre',
                            'November' => 'Novembre',
                            'December' => 'Dicembre'
                        ];
                        $giornoNome = $giorniSettimana[$date->format('l')] ?? $date->format('l');
                        $meseNome = $mesi[$date->format('F')] ?? $date->format('F');
                        $dataFormattata = $giornoNome . ' ' . $date->format('d') . ' ' . $meseNome . ' ' . $date->format('Y');
                    @endphp
                    <tr class="bg-gradient-to-r from-lime-50 via-lime-100/30 to-lime-50">
                        <td colspan="9" class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-lime-400 to-transparent"></div>
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-calendar-day text-lime-500"></i>
                                    <span class="font-semibold text-lime-700">{{ $dataFormattata }}</span>
                                </div>
                                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-lime-400 to-transparent"></div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    
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
                                            
                                            // Gestione sicura del valore n_ore con virgola
                                            $oreRaw = $staffDetail->getRawOriginal('n_ore') ?? $staffDetail->n_ore ?? 0;
                                            if (is_string($oreRaw)) {
                                                $oreRaw = str_replace(',', '.', $oreRaw);
                                            }
                                            $ore = floatval($oreRaw);
                                            if (is_nan($ore)) $ore = 0;
                                        @endphp
                                        <div class="text-xs text-gray-500 whitespace-nowrap">
                                            <span>{{ $staffName }}</span>
                                            <span class="ml-1">({{ number_format($ore, 1) }} h)</span>
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
                        
                        <!-- Lat/Long con tooltip modificabile - SENZA REFRESH -->
                        <td class="px-3 py-3 text-sm relative group">
                            @if($activity->Lat_Long)
                                @php $useTextarea = substr_count($activity->Lat_Long, ',') > 1; @endphp
                                <div x-data="{ 
                                    latLong: '{{ addslashes($activity->Lat_Long) }}',
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: '{{ addslashes($activity->Lat_Long) }}',
                                    
                                    saveLatLong() {
                                        this.isEditing = true;
                                        @this.call('updateLatLong', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                this.latLong = this.editedValue;
                                                // Aggiorna il DOM senza reload
                                                const div = this.$el.querySelector('.latlong-text');
                                                if (div) {
                                                    div.innerText = this.editedValue;
                                                }
                                                @this.dispatch('showSuccess', { message: 'Coordinate aggiornate con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
                                    <div class="font-mono text-xs text-gray-600 cursor-pointer hover:text-lime-600 hover:underline break-words whitespace-normal max-w-[120px] latlong-text" 
                                        title="Clicca per modificare"
                                        x-on:click="showTooltip = true; editedValue = latLong"
                                        x-text="latLong">
                                        {{ $activity->Lat_Long }}
                                    </div>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[280px]"
                                        style="top: 100%; left: 50%; transform: translateX(-50%); margin-top: -20px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45 -translate-x-1/2"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Latitudine / Longitudine</label>
                                        @if($useTextarea)
                                            <textarea x-model="editedValue" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                                placeholder="es. 45.123456, 12.123456"
                                                rows="3"
                                                x-on:keydown.ctrl.enter="saveLatLong()"></textarea>
                                        @else
                                            <input type="text" 
                                                x-model="editedValue" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                                placeholder="es. 45.123456, 12.123456"
                                                x-on:keydown.enter="saveLatLong()">
                                        @endif
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
                                <div x-data="{ 
                                    showTooltip: false, 
                                    editedValue: '', 
                                    isEditing: false,
                                    saveLatLong() {
                                        this.isEditing = true;
                                        @this.call('updateLatLong', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                @this.dispatch('refreshActivities');
                                                @this.dispatch('showSuccess', { message: 'Coordinate aggiunte con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
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
                            @endif
                        </td>

                        <!-- Note -->
                        <td class="px-3 py-3 text-sm">
                            @if($activity->note)
                                <div class="text-xs text-gray-500 break-words whitespace-normal max-w-[180px]" title="{{ $activity->note }}">
                                    {{ $activity->note }}
                                </div>
                            @else
                                <span class="text-gray-400 italic text-xs">-</span>
                            @endif
                        </td>

                        <!-- Ettari (ha) con tooltip modificabile - SENZA REFRESH -->
                        <td class="px-3 py-3 text-sm whitespace-nowrap relative group">
                            @if($activity->ha)
                                <div x-data="{ 
                                    haValue: '{{ $activity->ha }}',
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: '{{ $activity->ha }}',
                                    
                                    saveHa() {
                                        this.isEditing = true;
                                        @this.call('updateHa', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                this.haValue = this.editedValue;
                                                // Aggiorna il DOM senza reload
                                                const span = this.$el.querySelector('.ha-value');
                                                if (span) {
                                                    span.innerText = parseFloat(this.editedValue).toFixed(2);
                                                }
                                                @this.dispatch('showSuccess', { message: 'Ettari aggiornati con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
                                    <span class="font-medium cursor-pointer hover:text-lime-600 hover:underline ha-value" 
                                        x-on:click="showTooltip = true; editedValue = haValue"
                                        x-text="parseFloat(haValue).toFixed(2)">
                                        {{ number_format(floatval($activity->ha), 2) }}
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
                                <div x-data="{ 
                                    showTooltip: false, 
                                    editedValue: '', 
                                    isEditing: false,
                                    saveHa() {
                                        this.isEditing = true;
                                        @this.call('updateHa', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                @this.dispatch('refreshActivities');
                                                @this.dispatch('showSuccess', { message: 'Ettari aggiunti con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
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
                            @endif
                        </td>

                        <!-- Rif. Fattura con icona solo per clienti esterni E solo se fattura è vuota -->
                        <td class="px-3 py-3 text-sm relative group">
                            @php
                                $isExternal = false;
                                if($activity->entity && $activity->costCenter) {
                                    // Verifica se il cliente è esterno (non di proprietà)
                                    $costCenterCheck = \App\Models\CostCenter::where('id', $activity->costCenter->id)
                                        ->where('table_references', 'entities')
                                        ->exists();
                                    $isExternal = $costCenterCheck;
                                }
                                
                                // Determina se mostrare l'icona (solo se esterno E fattura vuota)
                                $showIcon = $isExternal && empty($activity->invoice_references);
                            @endphp
                            
                            @if($activity->invoice_references)
                                <!-- Se c'è testo fattura, mostra solo il testo, nessuna icona -->
                                <div x-data="{ 
                                    invoiceRef: '{{ $activity->invoice_references }}',
                                    showTooltip: false,
                                    isEditing: false,
                                    editedValue: '{{ $activity->invoice_references }}',
                                    
                                    saveInvoiceRef() {
                                        this.isEditing = true;
                                        @this.call('updateInvoiceRef', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                this.invoiceRef = this.editedValue;
                                                // Aggiorna il DOM senza reload
                                                const span = this.$el.querySelector('span');
                                                if (span) {
                                                    span.innerText = this.editedValue.length > 20 ? this.editedValue.substring(0, 20) + '...' : this.editedValue;
                                                }
                                                @this.dispatch('showSuccess', { message: 'Riferimento fattura aggiornato con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
                                    <span class="text-xs text-gray-600 font-mono cursor-pointer hover:text-lime-600 hover:underline" 
                                        title="Clicca per modificare" 
                                        x-on:click="showTooltip = true; editedValue = invoiceRef">
                                        {{ Str::limit($activity->invoice_references, 20) }}
                                    </span>
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[280px]"
                                        style="top: 100%; left: -100px; margin-top: -30px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45 -translate-x-1/2"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Riferimento Fattura</label>
                                        <input type="text" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="es. FV-2024-001"
                                            x-on:keydown.enter="saveInvoiceRef()">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="saveInvoiceRef()"
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
                                <!-- Se fattura è vuota -->
                                <div x-data="{ showTooltip: false, editedValue: '', isEditing: false,
                                    saveInvoiceRef() {
                                        this.isEditing = true;
                                        @this.call('updateInvoiceRef', {{ $activity->id }}, this.editedValue)
                                            .then(() => {
                                                this.isEditing = false;
                                                this.showTooltip = false;
                                                // Ricarica solo il componente Livewire senza refresh pagina
                                                @this.dispatch('refreshActivities');
                                                @this.dispatch('showSuccess', { message: 'Riferimento fattura aggiunto con successo!' });
                                            })
                                            .catch(() => {
                                                this.isEditing = false;
                                                @this.dispatch('showError', { message: 'Errore durante l\'aggiornamento' });
                                            });
                                    }
                                }">
                                    @if($showIcon)
                                        <!-- Solo per clienti esterni: mostra l'icona grande -->
                                        <div class="cursor-pointer hover:text-lime-600 inline-flex items-center justify-center" 
                                            x-on:click="showTooltip = true">
                                            <i class="fa-solid fa-file-invoice-dollar bg-red-500 px-2.5 py-1.5 rounded-lg text-gray-100 text-md"></i>
                                        </div>
                                    @else
                                        <!-- Clienti interni: lascia vuoto -->
                                        <span class="text-gray-400 italic text-xs"></span>
                                    @endif
                                    
                                    <div x-show="showTooltip" 
                                        x-on:click.away="showTooltip = false"
                                        class="absolute z-[100] bg-white border border-gray-300 rounded-lg shadow-xl p-3 min-w-[280px]"
                                        style="top: 100%; left: -100px; margin-top: -30px;"
                                        x-cloak>
                                        <div class="absolute -top-2 left-1/2 w-4 h-4 bg-white border-l border-t border-gray-300 transform rotate-45 -translate-x-1/2"></div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Riferimento Fattura</label>
                                        <input type="text" 
                                            x-model="editedValue" 
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            placeholder="es. FV-2024-001"
                                            x-on:keydown.enter="saveInvoiceRef()">
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" 
                                                    x-on:click="showTooltip = false"
                                                    class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                <i class="fas fa-times"></i> Annulla
                                            </button>
                                            <button type="button" 
                                                    x-on:click="saveInvoiceRef()"
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
                                <!-- Pulsante Sotto-attività (Lat/Long) -->
                                <button wire:click="viewCoordinates({{ $activity->id }})" 
                                        class="text-purple-500 hover:text-purple-700 transition-colors p-1" 
                                        title="Sotto-attività (Lat/Long)">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </button>
                                 <!-- Pulsante Immagini -->
                                <a href="{{ route('admin.activities.images.index', $activity->id) }}" 
                                class="text-indigo-600 hover:text-indigo-900 transition-colors relative" 
                                title="Gestisci Immagini">
                                    <i class="fas fa-paperclip"></i>
                                    @if($activity->images_count > 0)
                                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center">
                                        {{ $activity->images_count }}
                                    </span>
                                    @endif
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    @php
                        $lastDate = $currentDate;
                    @endphp
                    
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-500">
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
                 <!-- FOOTER TOTALI -->
                @if($activities->count() > 0)
                <tfoot class="bg-lime-50 border-t-2 border-lime-200">
                    <tr>
                        <td colspan="6" class="px-3 py-3 text-right font-bold text-gray-700">
                            <i class="fas fa-calculator text-lime-500 mr-2"></i>
                            TOTALI:
                        </td>
                        <td class="px-3 py-3 text-left font-bold text-lime-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-chart-line"></i>
                                <span>{{ number_format($totalHa, 2) }} ha</span>
                            </div>
                        </td>
                        <td colspan="2" class="px-3 py-3">
                            @if($totalPiante > 0)
                            <div class="text-sm text-gray-600">
                                🌱 Piante totali: {{ number_format($totalPiante, 0) }}
                            </div>
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($perPage != 10000 && $activities instanceof \Illuminate\Pagination\AbstractPaginator && $activities->hasPages())
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }} di {{ $activities->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $activities->appends(request()->query())->links() }}
        </div>
    </div>
    @elseif($perPage == 10000 && $activities->count() > 0)
    <div class="mt-6">
        <div class="text-sm text-gray-500 mb-2 text-center bg-green-50 p-2 rounded-lg">
            <i class="fas fa-database text-green-500 mr-1"></i> 
            Mostrati tutti i <strong>{{ $activities->count() }}</strong> risultati
        </div>
    </div>
    @endif

    <div class="flex gap-3 justify-end">
        <a href="{{ $this->getExportPdfUrl() }}" 
        class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg shadow-md flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-file-pdf text-xl"></i> 
            <span>Esporta PDF</span>
        </a>
        <a href="{{ $this->getExportExcelUrl() }}" 
        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow-md flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-file-excel text-xl"></i> 
            <span>Esporta Excel</span>
        </a>
    </div>

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
                            
                            <!-- Rif. Fattura -->
                            @if($viewingActivity->invoice_references)
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Rif. Fattura</span>
                                <p class="font-medium text-gray-800 mt-1">{{ $viewingActivity->invoice_references }}</p>
                            </div>
                            @endif
                            
                            <!-- Imponibile -->
                            @if($viewingActivity->imponibile > 0)
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Imponibile</span>
                                <p class="font-medium text-gray-800 mt-1">{{ number_format($viewingActivity->imponibile, 2) }} €</p>
                            </div>
                            @endif
                            
                            <!-- Costi Materiali -->
                            @if($viewingActivity->costi_mat > 0)
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Costi Materiali</span>
                                <p class="font-medium text-gray-800 mt-1">{{ number_format($viewingActivity->costi_mat, 2) }} €</p>
                            </div>
                            @endif
                            
                            <!-- Totale -->
                            @if(($viewingActivity->imponibile ?? 0) + ($viewingActivity->costi_mat ?? 0) > 0)
                            <div class="bg-lime-50 p-3 rounded-lg border border-lime-200">
                                <span class="text-xs text-lime-600 uppercase font-semibold">Totale</span>
                                <p class="text-lg font-bold text-lime-600 mt-1">{{ number_format(($viewingActivity->imponibile ?? 0) + ($viewingActivity->costi_mat ?? 0), 2) }} €</p>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Personale Associato -->
                        @if($viewingActivity->staffDetails && $viewingActivity->staffDetails->count() > 0)
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase font-semibold">Personale Associato</span>
                            <div class="mt-2 space-y-1">
                                @foreach($viewingActivity->staffDetails as $staffDetail)
                                <div class="text-sm text-gray-700">
                                    <span class="font-medium">{{ $staffDetail->staff->CognomePers ?? '' }} {{ $staffDetail->staff->NomePers ?? '' }}</span>
                                    <span class="text-gray-500 ml-2">({{ number_format($staffDetail->n_ore, 1) }} ore)</span>
                                    @if($staffDetail->spese > 0)
                                    <span class="text-gray-500 ml-2">Spese: {{ number_format($staffDetail->spese, 2) }} €</span>
                                    @endif
                                    @if($staffDetail->note)
                                    <div class="text-xs text-gray-500 mt-0.5 ml-2">{{ $staffDetail->note }}</div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <!-- Note -->
                        @if($viewingActivity->note)
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <span class="text-xs text-gray-500 uppercase font-semibold">Note</span>
                            <div class="mt-1 text-gray-700 whitespace-pre-wrap">{{ $viewingActivity->note }}</div>
                        </div>
                        @endif
                        
                        <!-- Lat/Long e Ha -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($viewingActivity->Lat_Long)
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Latitudine / Longitudine</span>
                                <p class="font-mono text-sm text-gray-700 mt-1">{{ $viewingActivity->Lat_Long }}</p>
                            </div>
                            @endif
                            
                            @if($viewingActivity->ha)
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Ettari (ha)</span>
                                <p class="font-medium text-gray-800 mt-1">{{ $viewingActivity->ha }}</p>
                            </div>
                            @endif
                        </div>
                        
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
                            <a href="{{ route('admin.activities.edit', $viewingActivity->id) }}" 
                                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-md text-white transition-colors inline-flex items-center">
                                <i class="fas fa-edit mr-2"></i> Modifica
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL SOTTO-ATTIVITÀ (LAT/LONG) -->
    @if($showCoordinatesModal && $viewingCoordinatesActivity)
    <div wire:ignore.self class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.closeCoordinatesModal()" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl transform transition-all sm:my-8 sm:align-middle w-[95%] sm:w-[90vw] sm:max-w-[90vw]">
                <div class="bg-white rounded-lg max-h-[90vh] flex flex-col">
                    <!-- Header fisso -->
                    <div class="px-6 pt-4 pb-3 border-b sticky top-0 bg-white rounded-t-lg z-10">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-800">
                                <i class="fa-solid fa-map-location-dot text-purple-500 mr-2"></i>
                                Sotto-attività (Lat/Long) — {{ $this->formatDate($viewingCoordinatesActivity->data_activities) }}
                            </h2>
                            <button wire:click="closeCoordinatesModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ optional($viewingCoordinatesActivity->costCenter)->Nome ?? '-' }}
                            @if($viewingCoordinatesActivity->entity)
                                — {{ $viewingCoordinatesActivity->entity->ragione_sociale ?: ($viewingCoordinatesActivity->entity->nome . ' ' . $viewingCoordinatesActivity->entity->cognome) }}
                            @endif
                        </p>
                    </div>

                    <!-- Contenuto scrollabile -->
                    <div class="flex-1 overflow-y-auto p-6">

                        @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                        <!-- Toolbar -->
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                {{ count($activityCoordinates) }} {{ count($activityCoordinates) == 1 ? 'sotto-attività registrata' : 'sotto-attività registrate' }}
                            </div>
                            <button type="button"
                                    wire:click="addEmptyCoordinateRow"
                                    wire:loading.attr="disabled" wire:target="addEmptyCoordinateRow"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded-md bg-lime-500 hover:bg-lime-600 text-white transition-colors disabled:opacity-50">
                                <i class="fa-solid fa-circle-plus" wire:loading.remove wire:target="addEmptyCoordinateRow"></i>
                                <i class="fa-solid fa-spinner fa-spin" wire:loading wire:target="addEmptyCoordinateRow"></i>
                                Aggiungi sotto-attività
                            </button>
                        </div>
                        @endif

                        @if(count($activityCoordinates) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-14">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lat/Long Inizio</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lat/Long Fine</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-28">Ha</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-32">Verificato</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-16">Elimina</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($activityCoordinates as $index => $coord)
                                    <tr wire:key="coord-{{ $coord->id_att_LatLong }}" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-sm text-gray-500 whitespace-nowrap">{{ $index + 1 }} blocco</td>

                                        <!-- Lat/Long Inizio (editabile) -->
                                        <td class="px-2 py-1.5">
                                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                            <input type="text"
                                                value="{{ $coord->Lat_inizio }}"
                                                wire:change="updateCoordinateField({{ $coord->id_att_LatLong }}, 'Lat_inizio', $event.target.value)"
                                                placeholder="es. 45.123456,12.123456"
                                                class="w-full min-w-[160px] px-2 py-1.5 text-sm font-mono border border-transparent rounded-md hover:border-gray-300 focus:border-lime-500 focus:ring-1 focus:ring-lime-500 focus:outline-none transition-colors">
                                            @else
                                            <span class="text-sm font-mono text-gray-700">{{ $coord->Lat_inizio ?: '-' }}</span>
                                            @endif
                                        </td>

                                        <!-- Lat/Long Fine (editabile) -->
                                        <td class="px-2 py-1.5">
                                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                            <input type="text"
                                                value="{{ $coord->Lat_fine }}"
                                                wire:change="updateCoordinateField({{ $coord->id_att_LatLong }}, 'Lat_fine', $event.target.value)"
                                                placeholder="es. 45.123456,12.123456"
                                                class="w-full min-w-[160px] px-2 py-1.5 text-sm font-mono border border-transparent rounded-md hover:border-gray-300 focus:border-lime-500 focus:ring-1 focus:ring-lime-500 focus:outline-none transition-colors">
                                            @else
                                            <span class="text-sm font-mono text-gray-700">{{ $coord->Lat_fine ?: '-' }}</span>
                                            @endif
                                        </td>

                                        <!-- Note (editabile) -->
                                        <td class="px-2 py-1.5">
                                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                            <input type="text"
                                                value="{{ $coord->NoteAtt }}"
                                                wire:change="updateCoordinateField({{ $coord->id_att_LatLong }}, 'NoteAtt', $event.target.value)"
                                                placeholder="Note..."
                                                class="w-full min-w-[180px] px-2 py-1.5 text-sm border border-transparent rounded-md hover:border-gray-300 focus:border-lime-500 focus:ring-1 focus:ring-lime-500 focus:outline-none transition-colors">
                                            @else
                                            <span class="text-sm text-gray-600">{{ $coord->NoteAtt ?: '-' }}</span>
                                            @endif
                                        </td>

                                        <!-- Ha (editabile) -->
                                        <td class="px-2 py-1.5">
                                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                            <input type="text"
                                                value="{{ $coord->ha }}"
                                                wire:change="updateCoordinateField({{ $coord->id_att_LatLong }}, 'ha', $event.target.value)"
                                                placeholder="0.00"
                                                class="w-full min-w-[80px] px-2 py-1.5 text-sm font-medium border border-transparent rounded-md hover:border-gray-300 focus:border-lime-500 focus:ring-1 focus:ring-lime-500 focus:outline-none transition-colors">
                                            @else
                                            <span class="text-sm font-medium text-gray-800">{{ $coord->ha ?: '-' }}</span>
                                            @endif
                                        </td>

                                        <!-- Verificato: toggle on/off + select Y/N -->
                                        <td class="px-3 py-2 text-sm text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- Toggle Verificato: X rossa / spunta verde -->
                                                @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                                <button type="button"
                                                    wire:click="toggleCoordinateVerificato({{ $coord->id_att_LatLong }})"
                                                    wire:loading.attr="disabled" wire:target="toggleCoordinateVerificato({{ $coord->id_att_LatLong }})"
                                                    title="{{ $coord->verificato === 'Y' ? 'Verificato — clicca per annullare' : 'Non verificato — clicca per confermare' }}"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-full text-white transition-colors disabled:opacity-50 {{ $coord->verificato === 'Y' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600' }}">
                                                    <i class="fa-solid {{ $coord->verificato === 'Y' ? 'fa-check' : 'fa-xmark' }} text-xs"></i>
                                                </button>
                                                @else
                                                <span title="{{ $coord->verificato === 'Y' ? 'Verificato' : 'Non verificato' }}"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-full text-white {{ $coord->verificato === 'Y' ? 'bg-green-500' : 'bg-red-500' }}">
                                                    <i class="fa-solid {{ $coord->verificato === 'Y' ? 'fa-check' : 'fa-xmark' }} text-xs"></i>
                                                </span>
                                                @endif

                                                <!-- Select Y/N (in aggiunta al toggle) -->
                                                @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                                <select
                                                    wire:change="updateCoordinateVerificato({{ $coord->id_att_LatLong }}, $event.target.value)"
                                                    class="text-xs px-2 py-1 rounded border font-medium {{ $coord->verificato === 'Y' ? 'bg-green-50 border-green-300 text-green-700' : 'bg-orange-50 border-orange-300 text-orange-700' }}">
                                                    <option value="N" @selected($coord->verificato === 'N')>N</option>
                                                    <option value="Y" @selected($coord->verificato === 'Y')>Y</option>
                                                </select>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Elimina -->
                                        <td class="px-3 py-2 text-sm text-center">
                                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                                            <button type="button"
                                                    wire:click="confirmDeleteCoordinate({{ $coord->id_att_LatLong }})"
                                                    class="text-red-500 hover:text-red-700 transition-colors p-1"
                                                    title="Elimina">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-map-marker-alt text-4xl mb-2 text-gray-300"></i>
                            <p>Nessuna sotto-attività trovata</p>
                            @if(auth()->guard('admin')->user()->hasPermission('edit_activities'))
                            <button type="button"
                                    wire:click="addEmptyCoordinateRow"
                                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded-md bg-lime-500 hover:bg-lime-600 text-white transition-colors">
                                <i class="fa-solid fa-circle-plus"></i> Aggiungi la prima sotto-attività
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Footer fisso -->
                    <div class="px-6 py-3 border-t bg-gray-50 rounded-b-lg sticky bottom-0">
                        <div class="flex justify-end">
                            <button wire:click="closeCoordinatesModal" 
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i> Chiudi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL CONFERMA ELIMINAZIONE SOTTO-ATTIVITÀ -->
    @if($confirmingDeleteCoordinateId)
    <div wire:ignore.self class="fixed inset-0 z-[60] overflow-y-auto" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.150ms>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="show = false; $wire.cancelDeleteCoordinate()" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full text-left">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center w-11 h-11 rounded-full bg-red-100">
                            <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Eliminare questa sotto-attività?</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Il blocco lat/long verrà eliminato definitivamente. L'operazione non è reversibile.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-3 bg-gray-50 rounded-b-lg flex justify-end gap-2">
                    <button type="button" wire:click="cancelDeleteCoordinate"
                            class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-md text-gray-700 transition-colors">
                        Annulla
                    </button>
                    <button type="button" wire:click="deleteCoordinate({{ $confirmingDeleteCoordinateId }})"
                            wire:loading.attr="disabled" wire:target="deleteCoordinate({{ $confirmingDeleteCoordinateId }})"
                            class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 rounded-md text-white transition-colors inline-flex items-center gap-2 disabled:opacity-50">
                        <i class="fa-solid fa-trash" wire:loading.remove wire:target="deleteCoordinate({{ $confirmingDeleteCoordinateId }})"></i>
                        <i class="fa-solid fa-spinner fa-spin" wire:loading wire:target="deleteCoordinate({{ $confirmingDeleteCoordinateId }})"></i>
                        Elimina
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