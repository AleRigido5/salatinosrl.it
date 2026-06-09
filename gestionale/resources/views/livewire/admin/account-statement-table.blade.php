<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fa-solid fa-file-invoice-dollar text-lime-500 mr-2"></i> 
                Estratto Conto
            </h1>
            <div class="mt-1">
                <p class="text-lg font-semibold text-gray-700">{{ $entity->full_name }}</p>
                <p class="text-sm text-gray-500">
                    @if($entity->entity_type == 'cliente')
                        <i class="fas fa-user text-lime-600 mr-1"></i> Cliente
                    @elseif($entity->entity_type == 'fornitore')
                        <i class="fas fa-truck text-blue-600 mr-1"></i> Fornitore
                    @else
                        <i class="fas fa-handshake text-purple-600 mr-1"></i> Cliente / Fornitore
                    @endif
                    @if($entity->partita_iva) | P.IVA: {{ $entity->partita_iva }} @endif
                    @if($entity->codice_fiscale) | CF: {{ $entity->codice_fiscale }} @endif
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.entities.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Torna ai Clienti/Fornitori
            </a>
        </div>
    </div>

    <!-- Date Range + Filtri di Ricerca -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- RIGA SUPERIORE: Date Range Filter -->
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom, 
            'dateTo' => $dateTo
        ], key('date-filter-' . $dateFrom . $dateTo))
        
        <!-- Linea di separazione -->
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- RIGA INFERIORE: Filtri -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Autocomplete Proprietà -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
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
        @if($selectedOwnershipId || $selectedCostCenterId || $status || $type_invoice || $search || $dateFrom || $dateTo)
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
                
                @if($selectedOwnershipId || $selectedCostCenterId || $status || $type_invoice || $search || $dateFrom || $dateTo)
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

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proprietà</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrizione</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N. Fattura</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">DARE (€)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">AVERE (€)</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SALDO (€)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm">{{ $transaction['proprieta'] }}</td>
                    <td class="px-4 py-3 text-sm">{{ $transaction['descrizione'] }}</td>
                    <td class="px-4 py-3 text-sm whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction['data'])->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm font-mono">{{ $transaction['n_fattura'] }}</td>
                    <td class="px-4 py-3 text-sm text-right">
                        @if($transaction['dare'] > 0)
                            <span class="text-red-600 font-semibold">{{ number_format($transaction['dare'], 2, ',', '.') }}</span>
                        @else
                            <span class="text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-right">
                        @if($transaction['avere'] > 0)
                            <span class="text-green-600 font-semibold">{{ number_format($transaction['avere'], 2, ',', '.') }}</span>
                        @else
                            <span class="text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-bold">
                        <span class="@if($transaction['saldo'] > 0) text-green-600 @elseif($transaction['saldo'] < 0) text-red-600 @endif">
                            {{ number_format($transaction['saldo'], 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                        Nessun movimento trovato nel periodo selezionato
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($transactions) > 0)
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-700">TOTALI:</td>
                    <td class="px-4 py-3 text-right font-bold text-red-600">{{ number_format($totalDebit, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-600">{{ number_format($totalCredit, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold">
                        <span class="@if($finalBalance > 0) text-green-600 @elseif($finalBalance < 0) text-red-600 @endif">
                            {{ number_format($finalBalance, 2, ',', '.') }}
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Card Riepilogo -->
    @if(count($transactions) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 uppercase font-semibold">Totale DARE</p>
                    <p class="text-2xl font-bold text-red-700">{{ number_format($totalDebit, 2, ',', '.') }} €</p>
                    <p class="text-xs text-red-500 mt-1">Il cliente deve pagare</p>
                </div>
                <i class="fas fa-arrow-up text-red-400 text-3xl"></i>
            </div>
        </div>
        
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 uppercase font-semibold">Totale AVERE</p>
                    <p class="text-2xl font-bold text-green-700">{{ number_format($totalCredit, 2, ',', '.') }} €</p>
                    <p class="text-xs text-green-500 mt-1">Il cliente ha pagato</p>
                </div>
                <i class="fas fa-arrow-down text-green-400 text-3xl"></i>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">SALDO FINALE</p>
                    <p class="text-2xl font-bold {{ $finalBalance > 0 ? 'text-red-700' : ($finalBalance < 0 ? 'text-green-700' : 'text-gray-700') }}">
                        {{ number_format($finalBalance, 2, ',', '.') }} €
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($finalBalance > 0)
                            Il cliente deve ancora pagare
                        @elseif($finalBalance < 0)
                            Credito residuo verso il cliente
                        @else
                            Saldo pari a zero
                        @endif
                    </p>
                </div>
                <i class="fas fa-balance-scale text-gray-400 text-3xl"></i>
            </div>
        </div>
    </div>
    @endif
</div>