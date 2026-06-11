<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fa-solid fa-scale-unbalanced mr-2 text-lime-600"></i> 
                            Dettaglio Centro di Costo
                        </h1>
                        <p class="text-gray-600 mt-1">
                            <strong>{{ $costCenter->Nome }}</strong>
                            @if($costCenter->Localita)
                                - {{ $costCenter->Localita }}
                            @endif
                            @if($costCenter->Coltura)
                                <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    {{ $costCenter->Coltura }}
                                </span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500">
                            @if($costCenter->table_references === 'ownership' && $costCenter->ownership)
                                <i class="fas fa-building mr-1"></i> Proprietà: {{ $costCenter->ownership->RagAbbrev ?? $costCenter->ownership->Rag_Soc_intest }}
                            @elseif($costCenter->table_references === 'entities' && $costCenter->entity)
                                <i class="fas fa-user mr-1"></i> Cliente/Fornitore: {{ $costCenter->entity->ragione_sociale ?? ($costCenter->entity->nome . ' ' . $costCenter->entity->cognome) }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.cost_centers.index') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors" title="Torna alla lista">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow mb-6 p-4 border border-gray-200">
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom, 
            'dateTo' => $dateTo
        ], key('date-filter-' . $dateFrom . $dateTo))
        
        {{-- <div class="text-sm text-gray-500 mt-3 text-center">
            <i class="fas fa-calendar-alt mr-1"></i>
            Periodo: {{ $formattedDateFrom }} - {{ $formattedDateTo }}
        </div> --}}
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-4">
            <button 
                wire:click="setActiveTab('invoices')"
                class="px-4 py-2 font-medium transition-colors {{ $activeTab === 'invoices' ? 'text-lime-600 border-b-2 border-lime-600' : 'text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-file-invoice-dollar mr-2"></i>
                Fatture
                <span class="ml-1 text-xs bg-gray-100 rounded-full px-2 py-0.5">{{ $invoices->count() }}</span>
            </button>
            <button 
                wire:click="setActiveTab('activities')"
                class="px-4 py-2 font-medium transition-colors {{ $activeTab === 'activities' ? 'text-lime-600 border-b-2 border-lime-600' : 'text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-tasks mr-2"></i>
                Attività
                <span class="ml-1 text-xs bg-gray-100 rounded-full px-2 py-0.5">{{ $activities->count() }}</span>
            </button>
        </nav>
    </div>

    <!-- TAB FATTURE -->
    @if($activeTab === 'invoices')
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" 
                            wire:click="sortInvoicesBy('invoice_date')">
                            <div class="flex items-center gap-1">
                                Data
                                @if($invoiceSortField === 'invoice_date')
                                    <i class="fas fa-arrow-{{ $invoiceSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N. Fattura</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente / Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proprietà</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" 
                            wire:click="sortInvoicesBy('total')">
                            <div class="flex items-center justify-end gap-1">
                                Importo
                                @if($invoiceSortField === 'total')
                                    <i class="fas fa-arrow-{{ $invoiceSortDirection === 'asc' ? 'up' : 'down' }} text-gray-600"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-mono font-medium">
                            {{ $invoice['number'] }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($invoice['type'] === 'received')
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <i class="fas fa-arrow-down mr-1"></i> Acquisto
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    <i class="fas fa-arrow-up mr-1"></i> Vendita
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $invoice['entity_name'] }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $invoice['ownership'] }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">
                            € {{ number_format($invoice['total'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($invoice['type'] === 'received')
                                <a href="{{ route('admin.invoices-received.show', $invoice['id']) }}" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800"
                                   title="Visualizza fattura">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @else
                                <a href="{{ route('admin.invoices-sent.show', $invoice['id']) }}" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800"
                                   title="Visualizza fattura">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-file-invoice text-gray-300 text-4xl mb-2 block"></i>
                            Nessuna fattura trovata per questo centro di costo nel periodo selezionato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->count() > 0)
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">TOTALE FATTURE:</td>
                        <td class="px-4 py-3 text-right font-bold text-lime-600">
                            € {{ number_format($totals['invoice_total'], 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif

    <!-- TAB ATTIVITÀ -->
    @if($activeTab === 'activities')
    <div class="space-y-4">
        <!-- Filtri Attività -->
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" 
                           wire:model.live.debounce.300ms="activitySearch"
                           placeholder="Cerca in note o rif. fattura..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <select wire:model.live="activityServiceFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutti i servizi</option>
                        @foreach($servicesList as $service)
                            <option value="{{ $service->id }}">{{ $service->Titolo }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <select wire:model.live="activityStaffFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md">
                        <option value="">Tutto il personale</option>
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->Soprannome ?? $staff->NomePers . ' ' . $staff->CognomePers }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <button wire:click="clearActivityFilters" class="w-full px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                        <i class="fas fa-times mr-1"></i> Resetta filtri
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Tabella Attività -->
        <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                wire:click="sortActivitiesBy('data_activities')">
                                <div class="flex items-center gap-1">
                                    Data
                                    @if($activitySortField === 'data_activities')
                                        <i class="fas fa-arrow-{{ $activitySortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servizio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Personale</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                wire:click="sortActivitiesBy('ha')">
                                <div class="flex items-center justify-end gap-1">
                                    Ha
                                    @if($activitySortField === 'ha')
                                        <i class="fas fa-arrow-{{ $activitySortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rif. Fattura</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($activity->data_activities)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $activity->service->Titolo ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($activity->staffDetails as $staffDetail)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                            <i class="fas fa-user mr-1 text-gray-500"></i>
                                            {{ $staffDetail->staff->Soprannome ?? $staffDetail->staff->NomePers }}
                                            ({{ number_format($staffDetail->n_ore, 1) }} h)
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">
                                {{ number_format($activity->ha ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $activity->invoice_references ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $activity->note }}">
                                {{ \Illuminate\Support\Str::limit($activity->note ?? '-', 50) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="$dispatch('viewActivity', { id: {{ $activity->id }} })"
                                        class="text-blue-600 hover:text-blue-800"
                                        title="Visualizza attività">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <i class="fas fa-tasks text-gray-300 text-4xl mb-2 block"></i>
                                Nessuna attività trovata per questo centro di costo nel periodo selezionato
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($activities->count() > 0)
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">TOTALI:</td>
                            <td class="px-4 py-3 text-right font-bold text-lime-600">
                                {{ number_format($totals['total_ha'], 2) }} ha
                            </td>
                            <td colspan="3" class="px-4 py-3 text-right font-bold text-blue-600">
                                Totale Ore: {{ number_format($totals['total_ore'], 1) }} h
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endif
</div>