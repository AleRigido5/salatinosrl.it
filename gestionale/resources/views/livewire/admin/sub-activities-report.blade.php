<div>
    <!-- Header pagina -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-800">
            <i class="fa-solid fa-map-location-dot text-purple-500 mr-2"></i>
            Sotto-attività (Lat/Long) per Cliente
        </h1>
    </div>

    <!-- Filtri e Ricerca (identici alla vista Attività) -->
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

            <!-- Cliente/Fornitore Autocomplete (OBBLIGATORIO per questo report) -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="entity_input"
                        wire:model.live.debounce.300ms="entitySearch"
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('entitySearch', $event.target.value)"
                        placeholder="Cerca cliente/fornitore..."
                        class="w-full pl-9 pr-8 py-2 text-sm border rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 {{ $entityFilter ? 'border-gray-300' : 'border-red-300' }}"
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
                @if(!$entityFilter)
                <p class="text-xs text-red-500 mt-1">Cliente obbligatorio per avviare la ricerca</p>
                @endif
            </div>

            <!-- Per Page Selector con opzioni Tutti, 200, 100 e POSIZIONI -->
            <div class="flex items-center space-x-2">
                <!-- Select Posizioni -->
                <select wire:model.live="positionFilter" class="text-sm px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Posizioni</option>
                    <option value="aperte">Aperte</option>
                    <option value="interne">Interne</option>
                </select>

                <!-- Select Per Page (non usato in questo report, mantenuto per coerenza UI) -->
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
                <a href="{{ route('admin.activities.sub-activities') }}" class="ml-1 hover:text-lime-900">
                    <i class="fas fa-times text-xs"></i>
                </a>
            </span>
            @endif
        </div>
        @endif
    </div>

    <!-- JavaScript per i filtri data con navigazione mensile (identico alla vista Attività) -->
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
            const baseUrl = '{{ route("admin.activities.sub-activities") }}';

            function getFirstDayOfMonth(year, month) {
                return `${year}-${month.toString().padStart(2, '0')}-01`;
            }

            function getLastDayOfMonth(year, month) {
                const lastDay = new Date(year, month, 0).getDate();
                return `${year}-${month.toString().padStart(2, '0')}-${lastDay}`;
            }

            function updateDateFieldsWithMonth() {
                const year = parseInt(yearSelect.value);
                const month = parseInt(monthSelect.value);

                dateFrom.value = getFirstDayOfMonth(year, month);
                dateTo.value = getLastDayOfMonth(year, month);
            }

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

                        if (fromYear === toYear && fromMonth === toMonth && fromDay === 1 && toDay === lastDayOfMonth) {
                            monthSelect.value = fromMonth.toString().padStart(2, '0');
                            yearSelect.value = fromYear;
                            return true;
                        }
                    }
                }
                return false;
            }

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

            if (monthSelect) {
                monthSelect.addEventListener('change', updateDateFieldsWithMonth);
            }

            if (yearSelect) {
                yearSelect.addEventListener('change', updateDateFieldsWithMonth);
            }

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

            const urlParams = new URLSearchParams(window.location.search);
            const urlDateFrom = urlParams.get('date_from');
            const urlDateTo = urlParams.get('date_to');

            if (urlDateFrom && urlDateTo) {
                dateFrom.value = urlDateFrom;
                dateTo.value = urlDateTo;
                const synced = syncSelectsWithDates();
                if (!synced) {
                    const fromParts = urlDateFrom.split('-');
                    if (fromParts.length === 3) {
                        monthSelect.value = fromParts[1];
                        yearSelect.value = fromParts[0];
                    }
                }
            } else {
                // Nessuna data nell'URL: usa il default impostato dal componente (anno corrente)
                const initFrom = dateFrom.value;
                if (initFrom) {
                    const fromParts = initFrom.split('-');
                    if (fromParts.length === 3) {
                        monthSelect.value = fromParts[1];
                        yearSelect.value = fromParts[0];
                    }
                }
            }
        });
    </script>
    @endpush


    <!-- Contenuto -->
    @if(!$hasSearched)
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center text-gray-500">
            <i class="fa-solid fa-map-location-dot text-5xl mb-3 text-gray-300"></i>
            <p class="font-medium">Seleziona almeno un Cliente/Fornitore</p>
            <p class="text-sm text-gray-400 mt-1">le sotto-attività (blocchi lat/long) nel periodo selezionato verranno mostrate subito.</p>
        </div>
    @elseif(empty($groups))
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center text-gray-500">
            <i class="fa-solid fa-inbox text-5xl mb-3 text-gray-300"></i>
            <p>Nessuna sotto-attività trovata per i filtri selezionati.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($groups as $group)
            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-4 py-3">
                    <h2 class="text-center font-bold text-gray-800">
                        {{ $group['entity_name'] }} - {{ $group['service_name'] }}
                    </h2>
                    @if($group['cost_center_name'] && $group['cost_center_name'] !== '-')
                    <p class="text-center text-xs text-gray-500 mt-0.5">{{ $group['cost_center_name'] }}</p>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase w-28">Data</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Coord. GPS</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Coord. GPS</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Note</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-24">ha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php $lastRowDate = null; @endphp
                            @foreach($group['rows'] as $row)
                                @php
                                    $currentRowDate = optional($row->activity)->data_activities
                                        ? $row->activity->data_activities->format('Y-m-d')
                                        : null;
                                    $showSpacer = ($lastRowDate !== null && $lastRowDate !== $currentRowDate);
                                @endphp

                                @if($showSpacer)
                                <tr><td colspan="5" class="py-1.5"></td></tr>
                                @endif

                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-sm text-gray-700 whitespace-nowrap">
                                        {{ optional($row->activity)->data_activities?->format('d/m/Y') ?? '-' }}
                                    </td>
                                    <td class="px-3 py-1.5 text-sm font-mono text-gray-700">{{ $row->Lat_inizio ?: '-' }}</td>
                                    <td class="px-3 py-1.5 text-sm font-mono text-gray-700">{{ $row->Lat_fine ?: '-' }}</td>
                                    <td class="px-3 py-1.5 text-sm text-gray-600">{{ $row->NoteAtt ?: '-' }}</td>
                                    <td class="px-3 py-1.5 text-sm text-right font-medium text-gray-800">
                                        {{ $row->ha ? number_format(floatval(str_replace(',', '.', $row->ha)), 2, ',', '.') : '' }}
                                    </td>
                                </tr>

                                @php $lastRowDate = $currentRowDate; @endphp
                            @endforeach
                        </tbody>
                        <tfoot class="bg-lime-50 border-t-2 border-lime-200">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-bold text-gray-700 uppercase text-sm">
                                    Totale {{ $group['service_name'] }} {{ \Carbon\Carbon::parse($dateFrom)->format('Y') }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-lime-700">
                                    {{ number_format($group['total_ha'], 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach

            @if(count($groups) > 1)
            <div class="bg-gray-800 text-white rounded-lg shadow px-4 py-3 flex items-center justify-between">
                <span class="font-semibold">Totale complessivo</span>
                <span class="font-bold text-lg">{{ number_format($grandTotalHa, 2, ',', '.') }} ha</span>
            </div>
            @endif

            <div class="flex gap-3 justify-end">
                <a href="{{ $this->getExportPdfUrl() }}" target="_blank"
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
        </div>
    @endif
</div>