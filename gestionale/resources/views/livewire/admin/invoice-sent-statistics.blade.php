<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-chart-bar mr-3 text-lime-600"></i>
            Statistiche Fatturato - Vendite
        </h1>
    </div>

    <!-- Card Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        @livewire('components.date-range-filter', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ], key('stats-date-filter-' . $dateFrom . $dateTo))

        <div class="border-t border-gray-200 my-4"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Autocomplete Proprietà -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="stats_ownership_input"
                        wire:model.live.debounce.300ms="ownershipSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca proprietà..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedOwnershipId)
                        <button type="button"
                            wire:click="clearOwnership"
                            x-on:click="document.getElementById('stats_ownership_input').value = ''"
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
                                    document.getElementById('stats_ownership_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('ownershipSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedOwnershipId', '{{ $item->id }}');
                                    @this.set('selectedOwnershipName', '{{ addslashes($item->name) }}');
                                    @this.set('showOwnershipDropdown', false);
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

            <!-- Autocomplete Cliente -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Cliente</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="stats_customer_input"
                        wire:model.live.debounce.300ms="customerSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca cliente..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedCustomerId)
                        <button type="button"
                            wire:click="clearCustomer"
                            x-on:click="document.getElementById('stats_customer_input').value = ''"
                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    @endif
                </div>

                <div x-show="open && @entangle('showCustomerDropdown')"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    @if($customerResults && $customerResults->count() > 0)
                        @foreach($customerResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('stats_customer_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('customerSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedCustomerId', '{{ $item->id }}');
                                    @this.set('selectedCustomerName', '{{ addslashes($item->name) }}');
                                    @this.set('showCustomerDropdown', false);
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
                        id="stats_cost_center_input"
                        wire:model.live.debounce.300ms="costCenterSearch"
                        x-on:focus="open = true"
                        x-on:keydown="open = true"
                        placeholder="Cerca centro di costo..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedCostCenterId)
                        <button type="button"
                            wire:click="clearCostCenter"
                            x-on:click="document.getElementById('stats_cost_center_input').value = ''"
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
                                    document.getElementById('stats_cost_center_input').value = '{{ addslashes($item->Nome) }}';
                                    @this.set('costCenterSearch', '{{ addslashes($item->Nome) }}');
                                    @this.set('selectedCostCenterId', '{{ $item->id }}');
                                    @this.set('selectedCostCenterName', '{{ addslashes($item->Nome) }}');
                                    @this.set('showCostCenterDropdown', false);
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
        </div>
    </div>

    <!-- ==================== STATISTICHE FATTURATO PER CATEGORIA ==================== -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-700">
                <i class="fas fa-chart-bar mr-2 text-lime-600"></i>
                Statistiche Fatturato per Categoria
            </h3>
            <div class="flex gap-2">
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

        <!-- Loading -->
        <div wire:loading wire:target="refreshStats, statPeriod" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600"></div>
        </div>

        <!-- Statistiche -->
        <div wire:loading.remove>
            @if($statistics && $statistics->count() > 0)
                <!-- Card Totali -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-3">
                        <p class="text-xs text-blue-600 font-medium">Totale Fatturato</p>
                        <p class="text-xl font-bold text-blue-800">{{ number_format($statistics->sum('total'), 2, ',', '.') }} €</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-3">
                        <p class="text-xs text-green-600 font-medium">Numero Fatture</p>
                        <p class="text-xl font-bold text-green-800">{{ $statistics->sum('count') }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-3">
                        <p class="text-xs text-purple-600 font-medium">Categorie</p>
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
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria Servizio</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fatturato</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% sul Totale</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">N. Fatture</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Media/Fattura</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                                $grandTotal = $statistics->sum('total');
                                $maxTotal = $statistics->max('total') ?: 1;
                            @endphp
                            @foreach($statistics as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <i class="fas fa-tag text-lime-500 mr-2"></i>
                                        {{ $stat->service_category ?? 'Non categorizzato' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                        {{ number_format($stat->total, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="text-gray-700">{{ $grandTotal > 0 ? number_format(($stat->total / $grandTotal) * 100, 1) : 0 }}%</span>
                                            <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-lime-400 to-lime-600 rounded-full transition-all" 
                                                     style="width: {{ $grandTotal > 0 ? min(($stat->total / $grandTotal) * 100, 100) : 0 }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $stat->count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                                        {{ $stat->count > 0 ? number_format($stat->total / $stat->count, 2, ',', '.') : 0 }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">TOTALE</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-green-600">{{ number_format($grandTotal, 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">100%</td>
                                <td class="px-4 py-3 text-sm text-center font-bold text-gray-900">{{ $statistics->sum('count') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                                    {{ $statistics->sum('count') > 0 ? number_format($grandTotal / $statistics->sum('count'), 2, ',', '.') : 0 }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Grafico a barre semplificato -->
                @if($statistics->count() > 1)
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2">Distribuzione percentuale per categoria</p>
                    <div class="flex h-4 rounded-full overflow-hidden">
                        @foreach($statistics as $stat)
                            @php
                                $percentage = $grandTotal > 0 ? ($stat->total / $grandTotal) * 100 : 0;
                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                                $colorIndex = $loop->index % count($colors);
                            @endphp
                            @if($percentage > 0)
                                <div class="{{ $colors[$colorIndex] }} transition-all" style="width: {{ $percentage }}%"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($statistics as $stat)
                            @php
                                $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                                $colorIndex = $loop->index % count($colors);
                                $percentage = $grandTotal > 0 ? ($stat->total / $grandTotal) * 100 : 0;
                            @endphp
                            @if($percentage > 0)
                                <span class="inline-flex items-center gap-1 text-xs">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $colors[$colorIndex] }}"></span>
                                    {{ Str::limit($stat->service_category ?? 'Non categorizzato', 20) }}
                                    ({{ number_format($percentage, 1) }}%)
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-chart-pie text-4xl text-gray-300 mb-2"></i>
                    <p>Nessun dato disponibile per il periodo selezionato</p>
                    <p class="text-xs text-gray-400 mt-1">Modifica i filtri o il periodo per visualizzare le statistiche</p>
                </div>
            @endif
        </div>
    </div>

    <!-- ==================== FATTURATO MENSILE ==================== -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gray-700">
                <i class="fas fa-calendar-alt mr-2 text-lime-600"></i>
                Fatturato Mensile
            </h3>
            <span class="text-xs text-gray-400">
                @if($dateFrom || $dateTo)
                    Periodo personalizzato
                @else
                    Ultimi 12 mesi
                @endif
            </span>
        </div>

        <!-- Loading -->
        <div wire:loading wire:target="refreshStats, statPeriod" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600"></div>
        </div>

        <div wire:loading.remove>
            @if($monthlyStatistics && $monthlyStatistics->count() > 0)
                @php
                    $maxMonthlyTotal = $monthlyStatistics->max('total') ?: 1;
                @endphp

                <!-- Grafico a barre verticali -->
                <div class="flex items-end gap-2 h-48 mb-4 border-b border-gray-200 pb-2 overflow-x-auto">
                    @foreach($monthlyStatistics as $month)
                        @php
                            $heightPercent = $maxMonthlyTotal > 0 ? max(($month->total / $maxMonthlyTotal) * 100, 2) : 2;
                        @endphp
                        <div class="flex flex-col items-center flex-shrink-0 justify-end h-full" style="min-width: 55px;">
                            <span class="text-[10px] text-gray-500 mb-1 whitespace-nowrap">
                                {{ $month->total > 0 ? number_format($month->total, 0, ',', '.') . ' €' : '' }}
                            </span>
                            <div class="w-8 bg-gradient-to-t from-lime-500 to-lime-300 rounded-t-md transition-all"
                                 style="height: {{ $heightPercent }}%"
                                 title="{{ $month->month_label }}: {{ number_format($month->total, 2, ',', '.') }} € ({{ $month->count }} fatture)">
                            </div>
                            <span class="text-[10px] text-gray-500 mt-1 whitespace-nowrap">{{ Str::limit($month->month_label, 8, '') }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Tabella dettaglio mensile -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mese</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fatturato</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">N. Fatture</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Media/Fattura</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($monthlyStatistics as $month)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <i class="fas fa-calendar-day text-lime-500 mr-2"></i>
                                        {{ $month->month_label }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                        {{ number_format($month->total, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $month->count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                                        {{ $month->count > 0 ? number_format($month->total / $month->count, 2, ',', '.') : '-' }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">TOTALE PERIODO</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-green-600">{{ number_format($monthlyStatistics->sum('total'), 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-sm text-center font-bold text-gray-900">{{ $monthlyStatistics->sum('count') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                                    {{ $monthlyStatistics->sum('count') > 0 ? number_format($monthlyStatistics->sum('total') / $monthlyStatistics->sum('count'), 2, ',', '.') : 0 }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-2"></i>
                    <p>Nessun dato disponibile per il periodo selezionato</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clearOwnershipInput', () => {
                const input = document.getElementById('stats_ownership_input');
                if (input) input.value = '';
            });
            Livewire.on('clearCustomerInput', () => {
                const input = document.getElementById('stats_customer_input');
                if (input) input.value = '';
            });
            Livewire.on('clearCostCenterInput', () => {
                const input = document.getElementById('stats_cost_center_input');
                if (input) input.value = '';
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
        });
    </script>
</div>