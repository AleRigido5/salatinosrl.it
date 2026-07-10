<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-dolly mr-3 text-lime-600"></i>
            Fatture di Acquisto
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices-received.create') }}" 
            class="bg-gradient-to-r from-blue-400 to-blue-700 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200" title="Crea Manualmente">
                <i class="fas fa-plus"></i>
            </a>

            <a href="{{ route('admin.invoices-received.xml-import') }}" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200" title="Importa XML">
                <i class="fas fa-upload"></i> Importa XML
            </a>

            <div class="relative group">
                <button wire:click="openTrashModal" class="relative px-5 py-2.5 rounded-lg shadow-md bg-gray-200 text-gray-700 hover:bg-gray-300" title="Cestino">
                    <i class="fas fa-trash-alt"></i>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-md" style="{{ $trashCount == 0 ? 'display: none;' : '' }}">
                        {{ $trashCount }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Card unica: Date Range + Filtri di Ricerca -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ], key('date-filter-' . $dateFrom . $dateTo))
        
        <div class="border-t border-gray-200 my-4"></div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Autocomplete Proprietà -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="ownership_input"
                        wire:model.live.debounce.300ms="ownershipSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca proprietà..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedOwnershipId)
                        <button type="button"
                            wire:click="clearOwnership"
                            x-on:click="document.getElementById('ownership_input').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showOwnershipDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($ownershipResults && $ownershipResults->count() > 0)
                        @foreach($ownershipResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('ownership_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('ownershipSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedOwnershipId', '{{ $item->id }}');
                                    @this.set('selectedOwnershipName', '{{ addslashes($item->name) }}');
                                    @this.set('showOwnershipDropdown', false);
                                    @this.call('resetPage');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                @if($item->ragione_sociale)
                                    <div class="text-xs text-gray-500">{{ $item->ragione_sociale }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>

            <!-- Autocomplete Fornitore -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Fornitore</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="supplier_input"
                        wire:model.live.debounce.300ms="supplierSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca fornitore..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedSupplierId)
                        <button type="button"
                            wire:click="clearSupplier"
                            x-on:click="document.getElementById('supplier_input').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showSupplierDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($supplierResults && $supplierResults->count() > 0)
                        @foreach($supplierResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('supplier_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('supplierSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedSupplierId', '{{ $item->id }}');
                                    @this.set('selectedSupplierName', '{{ addslashes($item->name) }}');
                                    @this.set('showSupplierDropdown', false);
                                    @this.call('resetPage');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->name }}</div>
                                @if($item->piva)
                                    <div class="text-xs text-gray-500">P.IVA: {{ $item->piva }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>
            
            <!-- Autocomplete Centro di Costo -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Centro di Costo</label>
                <div class="relative">
                    <i class="fas fa-chart-pie absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="cost_center_input"
                        wire:model.live.debounce.300ms="costCenterSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca centro di costo..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedCostCenterId)
                        <button type="button"
                            wire:click="clearCostCenter"
                            x-on:click="document.getElementById('cost_center_input').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showCostCenterDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($costCenterResults && $costCenterResults->count() > 0)
                        @foreach($costCenterResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('cost_center_input').value = '{{ addslashes($item->Nome) }}';
                                    @this.set('costCenterSearch', '{{ addslashes($item->Nome) }}');
                                    @this.set('selectedCostCenterId', '{{ $item->id }}');
                                    @this.set('selectedCostCenterName', '{{ addslashes($item->Nome) }}');
                                    @this.set('showCostCenterDropdown', false);
                                    @this.call('resetPage');
                                "
                                class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-800">{{ $item->Nome }}</div>
                                @if($item->Localita)
                                    <div class="text-xs text-gray-500">{{ $item->Localita }}</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                    @endif
                </div>
            </div>
            
            <!-- Select Tipo Documento -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo Documento</label>
                <div class="relative">
                    <i class="fas fa-file-alt absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="type_invoice" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="">Tutti i tipi</option>
                        @foreach($typeDocuments as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Select Stato + Per Page -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                    <div class="relative">
                        <i class="fas fa-tag absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <select wire:model.live="status" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            <option value="">Tutti gli stati</option>
                            @foreach($statuses as $value => $statusData)
                                <option value="{{ $value }}">{{ $statusData['label'] ?? $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Per pagina</label>
                    <select wire:model.live="perPage" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="10000">Tutti</option>
                        <option value="200">200</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Active Filters Tags -->
        @if($selectedOwnershipId || $selectedSupplierId || $selectedCostCenterId || $status || $type_invoice || $search || $dateFrom || $dateTo)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>
                
                @if($selectedOwnershipId && $selectedOwnershipName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-building mr-1 text-xs"></i> {{ $selectedOwnershipName }}
                    <button wire:click="clearOwnership" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($selectedSupplierId && $selectedSupplierName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-user mr-1 text-xs"></i> {{ $selectedSupplierName }}
                    <button wire:click="clearSupplier" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($selectedCostCenterId && $selectedCostCenterName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-chart-pie mr-1 text-xs"></i> {{ $selectedCostCenterName }}
                    <button wire:click="clearCostCenter" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($status)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-tag mr-1 text-xs"></i> {{ $statuses[$status]['label'] ?? $status }}
                    <button wire:click="clearStatus" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($type_invoice)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-file-alt mr-1 text-xs"></i> {{ $typeDocuments[$type_invoice] ?? $type_invoice }}
                    <button wire:click="clearTypeInvoice" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-file-invoice mr-1 text-xs"></i> N. Fattura: "{{ $search }}"
                    <button wire:click="clearSearch" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1 text-xs"></i> {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="clearDates" class="ml-1 hover:text-lime-900">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
                @endif
                
                @if($selectedOwnershipId || $selectedSupplierId || $selectedCostCenterId || $status || $type_invoice || $search || $dateFrom || $dateTo)
                <span class="text-xs text-gray-400 ml-2">
                    <button wire:click="resetFilters" class="hover:text-red-500">
                        <i class="fas fa-trash-alt mr-1 text-xs"></i> Rimuovi tutti
                    </button>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- ==================== STATISTICHE FATTURATO PER CENTRO DI COSTO ==================== -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-700">
                <i class="fas fa-chart-pie mr-2 text-lime-600"></i>
                Statistiche Fatturato per Centro di Costo
            </h3>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model.live="excludeCreditNotes" 
                        class="rounded border-gray-300 text-lime-600 focus:ring-lime-500 h-4 w-4">
                    <label class="text-sm text-gray-600 cursor-pointer">Escludi Note di Credito (TD04)</label>
                </div>
                <select wire:model.live="statPeriod" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <option value="monthly">Mensile</option>
                    <option value="quarterly">Trimestrale</option>
                    <option value="semestral">Semestrale</option>
                    <option value="yearly">Annuale</option>
                </select>
                <button wire:click="refreshStats" class="px-3 py-1.5 bg-lime-600 hover:bg-lime-700 text-white rounded-md text-sm transition-colors">
                    <i class="fas fa-sync-alt"></i> Aggiorna
                </button>
            </div>
        </div>

        <!-- Avviso se ci sono date personalizzate -->
        {{-- @if($dateFrom || $dateTo)
        <div class="text-xs text-yellow-600 bg-yellow-50 px-3 py-2 rounded-md mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            Stai utilizzando un range di date personalizzato. Per usare i filtri periodici (mensile/trimestrale/etc.), 
            <button wire:click="clearDates" class="underline hover:text-yellow-800 font-medium">rimuovi le date</button>.
        </div>
        @endif --}}

        <!-- Loading -->
        <div wire:loading wire:target="refreshStats, statPeriod, excludeCreditNotes" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600"></div>
        </div>

        <!-- Statistiche -->
        <div wire:loading.remove>
            @if($statistics && $statistics->count() > 0)
                @php
                    $grandTotal = $statistics->sum('total');
                    $totalCredits = $statistics->sum('credit_count');
                    $totalDebits = $statistics->sum('debit_count');
                @endphp
                
                <!-- Card Totali -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-3">
                        <p class="text-xs text-blue-600 font-medium">Totale Fatturato</p>
                        <p class="text-xl font-bold {{ $grandTotal < 0 ? 'text-red-600' : 'text-blue-800' }}">
                            {{ number_format($grandTotal, 2, ',', '.') }} €
                        </p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-3">
                        <p class="text-xs text-green-600 font-medium">Totale Debiti (TD01, TD24, ecc.)</p>
                        <p class="text-xl font-bold text-green-800">
                            {{ number_format($statistics->filter(fn($s) => $s->total > 0)->sum('total'), 2, ',', '.') }} €
                        </p>
                    </div>
                    <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-lg p-3">
                        <p class="text-xs text-red-600 font-medium">Totale Crediti (TD04)</p>
                        <p class="text-xl font-bold text-red-800">
                            {{ number_format(abs($statistics->filter(fn($s) => $s->total < 0)->sum('total')), 2, ',', '.') }} €
                        </p>
                    </div>
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-3">
                        <p class="text-xs text-purple-600 font-medium">Centri di Costo</p>
                        <p class="text-xl font-bold text-purple-800">{{ $statistics->count() }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-3">
                        <p class="text-xs text-orange-600 font-medium">Periodo</p>
                        <p class="text-sm font-semibold text-orange-800">
                            {{ $periodDisplay }}
                        </p>
                    </div>
                </div>

                <!-- Tabella Statistiche -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Centro di Costo</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fatturato</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% sul Totale</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">N. Righe</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Media/Riga</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($statistics as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <i class="fas fa-building text-lime-500 mr-2"></i>
                                        {{ $stat->cost_center }}
                                        @if($stat->total < 0)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-minus-circle mr-1"></i> Credito
                                            </span>
                                        @elseif($stat->credit_count > 0 && $stat->debit_count > 0)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-arrows-left-right mr-1"></i> Misto
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold {{ $stat->total < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                        @if($stat->total < 0)
                                            <span class="text-red-600">
                                                <i class="fas fa-minus-circle mr-1"></i>
                                                {{ number_format(abs($stat->total), 2, ',', '.') }} €
                                            </span>
                                        @else
                                            {{ number_format($stat->total, 2, ',', '.') }} €
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="{{ $stat->total < 0 ? 'text-red-600' : 'text-gray-700' }}">
                                                {{ $grandTotal != 0 ? number_format(($stat->total / $grandTotal) * 100, 1) : 0 }}%
                                            </span>
                                            <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full {{ $stat->total < 0 ? 'bg-red-500' : 'bg-gradient-to-r from-lime-400 to-lime-600' }} rounded-full transition-all" 
                                                    style="width: {{ $grandTotal != 0 ? min(abs(($stat->total / $grandTotal) * 100), 100) : 0 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $stat->count }}
                                        </span>
                                        @if($stat->credit_count > 0)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700" title="{{ $stat->credit_count }} righe di credito (TD04)">
                                                <i class="fas fa-minus-circle"></i> {{ $stat->credit_count }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right {{ $stat->total < 0 ? 'text-red-600' : 'text-gray-600' }}">
                                        {{ $stat->count > 0 ? number_format($stat->total / $stat->count, 2, ',', '.') : 0 }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">TOTALE</td>
                                <td class="px-4 py-3 text-sm text-right font-bold {{ $grandTotal < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($grandTotal, 2, ',', '.') }} €
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">100%</td>
                                <td class="px-4 py-3 text-sm text-center font-bold text-gray-900">{{ $statistics->sum('count') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold {{ $grandTotal < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $statistics->sum('count') > 0 ? number_format($grandTotal / $statistics->sum('count'), 2, ',', '.') : 0 }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Grafico a barre semplificato -->
                @if($statistics->count() > 1)
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2">Distribuzione percentuale per centro di costo</p>
                    <div class="flex h-4 rounded-full overflow-hidden">
                        @foreach($statistics as $stat)
                            @php
                                $percentage = $grandTotal != 0 ? ($stat->total / $grandTotal) * 100 : 0;
                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                                $colorIndex = $loop->index % count($colors);
                            @endphp
                            @if(abs($percentage) > 0.1)
                                <div class="{{ $colors[$colorIndex] }} transition-all" style="width: {{ abs($percentage) }}%"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($statistics as $stat)
                            @php
                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                                $colorIndex = $loop->index % count($colors);
                                $percentage = $grandTotal != 0 ? ($stat->total / $grandTotal) * 100 : 0;
                            @endphp
                            @if(abs($percentage) > 0.1)
                                <span class="inline-flex items-center gap-1 text-xs">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $colors[$colorIndex] }}"></span>
                                    {{ Str::limit($stat->cost_center, 20) }}
                                    ({{ number_format($percentage, 1) }}%)
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Legenda -->
                <div class="mt-3 pt-2 border-t border-gray-200">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                            <span>Fatture Debito (TD01, TD24, ecc.)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
                            <span>Note di Credito (TD04) - in detrazione</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span>Valori misti</span>
                        </div>
                        @if($grandTotal < 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Saldo negativo
                            </span>
                        @endif
                        @if($excludeCreditNotes)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-filter mr-1"></i> Note di credito escluse
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-chart-pie text-4xl text-gray-300 mb-2"></i>
                    <p>Nessun dato disponibile per il periodo selezionato</p>
                    <p class="text-xs text-gray-400 mt-1">Modifica i filtri o il periodo per visualizzare le statistiche</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('id_ownership')">
                            Proprietà 
                            @if($sortField === 'id_ownership')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('type_invoice')">
                            Tipo Doc. 
                            @if($sortField === 'type_invoice')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_invoice')">
                            Data 
                            @if($sortField === 'data_invoice')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('n_invoice')">
                            N. Fattura 
                            @if($sortField === 'n_invoice')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('id_entities')">
                            Fornitore 
                            @if($sortField === 'id_entities')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Centro di Costo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('status')">
                            Stato 
                            @if($sortField === 'status')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('importo_totale')">
                            Totale 
                            @if($sortField === 'importo_totale')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    @php
                        $costCenterNames = $invoice->rows->pluck('costCenter.Nome')->filter()->unique()->implode(', ');
                    @endphp
                    <tr class="hover:bg-gray-50" wire:key="invoice-{{ $invoice->id }}">
                        <td class="px-4 py-3 text-sm">{{ $invoice->ownership->RagAbbrev ?? $invoice->ownership_name }}</td>
                        <td class="px-4 py-3 text-sm">
                            {{ $typeDocuments[$invoice->type_invoice] ?? $invoice->type_invoice }}
                            @if($invoice->is_manual)
                                <i class="fas fa-hand-paper text-yellow-500 ml-1" title="Fattura creata manualmente"></i>
                            @endif
                            @if(in_array($invoice->type_invoice, ['credit_note', 'NC', 'nota_credito']))
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <i class="fas fa-minus-circle mr-0.5"></i> Credito
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ $invoice->n_invoice }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm max-w-[200px] truncate" title="{{ $costCenterNames }}">{{ $costCenterNames ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusConfig = $statuses[$invoice->status] ?? null;
                                $badgeClass = $statusConfig['badge_class'] ?? 'bg-gray-100 text-gray-800';
                                $statusLabel = $statusConfig['label'] ?? $invoice->status_label;
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @if($invoice->has_attachments)
                                    @php
                                        $firstAttachment = $invoice->getFirstAttachmentUrlAttribute();
                                    @endphp
                                    @if($firstAttachment)
                                        <a href="{{ $firstAttachment }}" 
                                        target="_blank" 
                                        class="text-blue-600 hover:text-blue-900 transition-colors" 
                                        title="Visualizza allegato">
                                            <i class="fa-solid fa-file-pdf text-red-500 hover:text-red-700 text-lg"></i>
                                        </a>
                                    @endif
                                @endif
                                
                                <button wire:click="editInvoice({{ $invoice->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900" 
                                        title="Modifica">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="{{ route('admin.invoices-received.xml-view', $invoice->id) }}" 
                                target="_blank" 
                                class="text-purple-600 hover:text-purple-900" 
                                title="Visualizza XML">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                                <button wire:click="showDetails({{ $invoice->id }})" 
                                        class="text-blue-600 hover:text-blue-900" 
                                        title="Dettagli">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $invoice->id }})" 
                                        class="text-red-600 hover:text-red-900" 
                                        title="Elimina">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-8 text-gray-500">Nessuna fattura trovata</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginazione -->
    @if($perPage != 10000 && $invoices instanceof \Illuminate\Pagination\AbstractPaginator && $invoices->hasPages())
    <div class="px-6 py-4 border-t">
        <div class="text-sm text-gray-500 mb-2">
            Mostrando {{ $invoices->firstItem() ?? 0 }} - {{ $invoices->lastItem() ?? 0 }} di {{ $invoices->total() }} risultati
        </div>
        <div class="flex justify-center">
            {{ $invoices->links() }}
        </div>
    </div>
    @elseif($perPage == 10000 && $invoices->count() > 0)
    <div class="px-6 py-4 border-t">
        <div class="text-sm text-gray-500 mb-2 text-center bg-green-50 p-2 rounded-lg">
            <i class="fas fa-database text-green-500 mr-1"></i> 
            Mostrati tutti i <strong>{{ $invoices->total() }}</strong> risultati
        </div>
    </div>
    @endif

    <div class="flex gap-3 justify-end mt-5">
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

    <!-- ==================== MODAL DETTAGLI FATTURA ==================== -->
    @if($showModal && $selectedInvoice)
    <div x-data="{ open: true }" x-show="open" x-init="$watch('open', value => { if (!value) $wire.closeModal() })" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Dettaglio Fattura</h3>
                            <p class="text-sm text-gray-500 mt-1">Fattura n. {{ $selectedInvoice->n_invoice }} del {{ $selectedInvoice->data_invoice->format('d/m/Y') }}</p>
                        </div>
                        <button x-on:click="open = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    @php
                        $statusConfig = $statuses[$selectedInvoice->status] ?? null;
                        $badgeClass = $statusConfig['badge_class'] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    
                    <div class="flex justify-between items-center mb-6 p-4 bg-gradient-to-r from-gray-50 to-white rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-tag text-gray-400"></i>
                            <span class="text-sm font-medium text-gray-600">Stato fattura:</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm {{ $selectedInvoice->status === 'issued' ? 'font-bold text-red-600' : 'text-gray-400' }}">
                                    <i class="fas fa-pen mr-1"></i>Emessa
                                </span>
                                <button 
                                    wire:click="updateInvoiceStatus({{ $selectedInvoice->id }}, '{{ $selectedInvoice->status === 'issued' ? 'viewed' : 'issued' }}')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-lime-500 focus:ring-offset-2"
                                    style="background-color: {{ $selectedInvoice->status === 'viewed' ? '#3b82f6' : '#e5e7eb' }}"
                                >
                                    <span class="sr-only">Cambia stato</span>
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"
                                        style="transform: translateX({{ $selectedInvoice->status === 'viewed' ? 'calc(1.25rem)' : '0.125rem' }})">
                                    </span>
                                </button>
                                <span class="text-sm {{ $selectedInvoice->status === 'viewed' ? 'font-bold text-blue-600' : 'text-gray-400' }}">
                                    <i class="fas fa-eye mr-1"></i>Visionata
                                </span>
                            </div>
                            
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                {{ $statusConfig['label'] ?? $selectedInvoice->status_label }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Fornitore</label>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->supplier_name }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Proprietà</label>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->ownership_name }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Tipo Documento</label>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->type_invoice_label }}</p>
                            @if(in_array($selectedInvoice->type_invoice, ['credit_note', 'NC', 'nota_credito']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 mt-1">
                                    <i class="fas fa-minus-circle mr-1"></i> Nota di Credito
                                </span>
                            @endif
                        </div>
                        @if($selectedInvoice->sdi_id)
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 uppercase">SDI ID</label>
                            <p class="text-sm font-mono text-gray-900 mt-1 break-all">{{ $selectedInvoice->sdi_id }}</p>
                        </div>
                        @endif
                        @if($selectedInvoice->causale)
                        <div class="bg-gray-50 p-3 rounded-lg col-span-2">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Causale / Note</label>
                            <p class="text-sm text-gray-700 mt-1">{{ $selectedInvoice->causale }}</p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2">Riferimenti Amministrativi</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-blue-600 uppercase"><i class="fas fa-user-plus mr-1"></i> Inserito da</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->creator->name ?? 'Sistema' }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $selectedInvoice->created_at ? $selectedInvoice->created_at->format('d/m/Y H:i:s') : '-' }}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-green-600 uppercase"><i class="fas fa-user-edit mr-1"></i> Modificato da</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->updater->name ?? $selectedInvoice->creator->name ?? 'Sistema' }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $selectedInvoice->updated_at ? $selectedInvoice->updated_at->format('d/m/Y H:i:s') : '-' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    @if(!$selectedInvoice->xml_filename)
                    <div class="mb-4">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-hand-paper text-yellow-600 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">Documento importato manualmente</h4>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Questa fattura è stata creata manualmente e non proviene da un file XML.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($selectedInvoice->payments && $selectedInvoice->payments->count() > 0)
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2"><i class="fas fa-credit-card mr-2 text-green-600"></i> Piano Scadenze / Pagamenti @if($selectedInvoice->payments->count() > 1)<span class="text-xs text-gray-500 ml-2">({{ $selectedInvoice->payments->count() }} rate)</span>@endif</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Data scadenza</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Importo</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Modalità pagamento</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">IBAN</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Stato</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($selectedInvoice->payments as $payment)
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
                                        <td class="px-3 py-2 text-sm">{{ $payment->payment_method_label ?? $payment->payment_method ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm font-mono text-xs">{{ $payment->iban ?? '-' }}</td>
                                        <td class="px-3 py-2 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->status_badge_class }}">{{ $payment->status_label }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @if($selectedInvoice->payments->count() > 1)
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td class="px-3 py-2 text-right font-bold">Totale pagamenti</td>
                                        <td class="px-3 py-2 text-right font-bold text-green-600">{{ number_format($selectedInvoice->payments->sum('amount'), 2, ',', '.') }} €</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="mb-4 bg-gray-50 rounded-lg p-3">
                        <p class="text-sm text-gray-500 text-center"><i class="fas fa-info-circle mr-1"></i> Nessun dato di pagamento disponibile</p>
                    </div>
                    @endif
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2">Righe Fattura</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Descrizione</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Quantità</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Prezzo Unit.</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Sconto</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Totale</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($selectedInvoice->rows as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $row->description }}</td>
                                        <td class="px-3 py-2 text-sm text-right">{{ number_format($row->quantity, 3, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-sm text-right">{{ number_format($row->unit_price, 4, ',', '.') }} €</td>
                                        <td class="px-3 py-2 text-sm text-right">{{ $row->discount_percentage > 0 ? number_format($row->discount_percentage, 2, ',', '.') . '%' : '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($row->total, 2, ',', '.') }} €</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right font-bold">TOTALE</td>
                                        <td class="px-3 py-2 text-right font-bold text-lg">{{ number_format($selectedInvoice->importo_totale, 2, ',', '.') }} €</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="text-xs text-gray-400 border-t pt-3 mt-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div><i class="fas fa-calendar-alt mr-1"></i> Importata il: {{ $selectedInvoice->imported_at ? $selectedInvoice->imported_at->format('d/m/Y H:i:s') : '-' }}</div>
                            <div><i class="fas fa-file-code mr-1"></i> File XML: {{ basename($selectedInvoice->xml_filename ?? '') ?: 'Creata manualmente' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL CONFERMA ELIMINAZIONE ==================== -->
    @if($showDeleteModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="show = false; $wire.cancelDelete()" x-transition.scale.origin.top>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">Sei sicuro di voler eliminare <strong>{{ $invoiceNameToDelete }}</strong>?<br><span class="text-xs text-gray-400">La fattura verrà spostata nel cestino e potrà essere ripristinata.</span></p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="deleteInvoice" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL CESTINO ==================== -->
    @if($showTrashModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto" x-on:click.away="show = false; $wire.closeTrashModal()" x-transition.scale.origin.top>
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-trash-alt mr-2 text-red-600"></i>
                    Cestino - Fatture Eliminate
                    @if($trashCount > 0)
                        <span class="text-sm font-normal text-gray-500 ml-2">({{ $trashCount }} fatture)</span>
                    @endif
                </h2>
                <button wire:click="closeTrashModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" wire:model.live="trashSearch" placeholder="Cerca per numero fattura o fornitore..." class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('n_invoice')">
                                <div class="flex items-center gap-1">Numero @if($trashSortField === 'n_invoice')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('data_invoice')">
                                <div class="flex items-center gap-1">Data Fattura @if($trashSortField === 'data_invoice')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totale</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('deleted_at')">
                                <div class="flex items-center gap-1">Data eliminazione @if($trashSortField === 'deleted_at')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trashedInvoices as $invoice)
                        <tr wire:key="trash-{{ $invoice->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->n_invoice }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->supplier_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->deleted_at ? $invoice->deleted_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <button wire:click="restoreFromTrash({{ $invoice->id }})" class="text-green-600 hover:text-green-900 transition-colors" title="Ripristina">
                                        <i class="fas fa-trash-restore text-lg"></i>
                                    </button>
                                    <button 
                                        x-on:click="if(confirm('⚠️ ELIMINAZIONE DEFINITIVA! Questa operazione non è reversibile. Sei sicuro di voler eliminare permanentemente la fattura {{ $invoice->n_invoice }}?')) $wire.forceDeleteFromTrash({{ $invoice->id }})" 
                                        class="text-red-600 hover:text-red-900 transition-colors" 
                                        title="Elimina definitivamente">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-trash-alt text-gray-400 text-5xl mb-2"></i>
                                <p class="text-sm text-gray-500 mt-2">Il cestino è vuoto</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($trashedInvoices->hasPages())
            <div class="mt-4">
                <div class="text-sm text-gray-500 mb-2">{{ $trashedInvoices->firstItem() }} - {{ $trashedInvoices->lastItem() }} di {{ $trashedInvoices->total() }} elementi</div>
                <div class="flex justify-center">{{ $trashedInvoices->links() }}</div>
            </div>
            @endif
            
            <div class="flex justify-between items-center mt-6 pt-4 border-t">
                @if($trashCount > 0)
                <button 
                    x-on:click="if(confirm('⚠️ ATTENZIONE! Questa operazione eliminerà DEFINITIVAMENTE tutte le {{ $trashCount }} fatture presenti nel cestino. L\'operazione non è reversibile. Sei sicuro?')) $wire.emptyTrash()" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Svuota cestino ({{ $trashCount }})
                </button>
                @else
                <div></div>
                @endif
                
                <button wire:click="closeTrashModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <style scoped>
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

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clearOwnershipInput', () => {
                const input = document.getElementById('ownership_input');
                if (input) input.value = '';
            });
            
            Livewire.on('clearSupplierInput', () => {
                const input = document.getElementById('supplier_input');
                if (input) input.value = '';
            });
            
            Livewire.on('clearCostCenterInput', () => {
                const input = document.getElementById('cost_center_input');
                if (input) input.value = '';
            });
            
            Livewire.on('resetAllFilters', () => {
                const ownershipInput = document.getElementById('ownership_input');
                if (ownershipInput) ownershipInput.value = '';
                
                const supplierInput = document.getElementById('supplier_input');
                if (supplierInput) supplierInput.value = '';
                
                const costCenterInput = document.getElementById('cost_center_input');
                if (costCenterInput) costCenterInput.value = '';
            });
            
            Livewire.on('resetDates', () => {
                const dateInputs = document.querySelectorAll('input[type="date"]');
                dateInputs.forEach(input => {
                    if (input.id.includes('date-from') || input.id.includes('date-to')) {
                        input.value = '';
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            Livewire.on('showSuccess', (event) => {
                console.log('Success:', event.message);
            });
            
            Livewire.on('showError', (event) => {
                console.error('Error:', event.message);
            });
        });
    </script>
</div>