<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-chart-simple mr-3 text-lime-600"></i>
            Statistiche Generali Acquisti / Vendite
        </h1>
    </div>

    <!-- Card Filtri -->
        <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
            <div class="flex flex-wrap items-end gap-4">
                <!-- Autocomplete Proprietà -->
                <div class="relative flex-1 min-w-[220px]" x-data="{ open: false }" x-on:click.away="open = false">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                    <div class="relative">
                        <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                        <input type="text"
                            id="stats_general_ownership_input"
                            wire:model.live.debounce.300ms="ownershipSearch"
                            x-on:focus="open = true"
                            x-on:keydown="open = true"
                            placeholder="Cerca proprietà..."
                            class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                            autocomplete="off">
                        @if($selectedOwnershipId)
                            <button type="button"
                                wire:click="clearOwnership"
                                x-on:click="document.getElementById('stats_general_ownership_input').value = ''"
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
                                        document.getElementById('stats_general_ownership_input').value = '{{ addslashes($item->name) }}';
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

                <!-- Anno (helper solo client-side: compila Dal/Al senza contattare il server) -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Anno</label>
                    <select
                        x-on:change="
                            const year = $event.target.value;
                            const fromInput = document.getElementById('stats_general_date_from');
                            const toInput = document.getElementById('stats_general_date_to');
                            if (year && fromInput && toInput) {
                                fromInput.value = year + '-01-01';
                                toInput.value = year + '-12-31';
                                fromInput.dispatchEvent(new Event('input'));
                                toInput.dispatchEvent(new Event('input'));
                            }
                        "
                        class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ (string) $year === $selectedYear ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dal -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dal</label>
                    <input type="date"
                        id="stats_general_date_from"
                        wire:model="dateFrom"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>

                <!-- Al -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Al</label>
                    <input type="date"
                        id="stats_general_date_to"
                        wire:model="dateTo"
                        class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                </div>

                <button wire:click="refreshStats"
                    wire:loading.attr="disabled"
                    wire:target="refreshStats"
                    class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-1.5 rounded-lg shadow-md hover:shadow-lg hover:from-lime-600 hover:to-lime-700 transition-all duration-200 disabled:opacity-60">
                    Applica
                </button>
            </div>
        </div>

        <!-- Tabella Riepilogo Mensile Vendite / Acquisti -->
        <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
            <div wire:loading wire:target="refreshStats" class="flex justify-center items-center py-16">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-lime-600"></div>
            </div>

            <div wire:loading.remove wire:target="refreshStats">
                @if($monthlyStatistics && $monthlyStatistics->count() > 0)
                    @php
                        $totVenditaImponibile = $monthlyStatistics->sum('vendite_imponibile');
                        $totVenditaIva = $monthlyStatistics->sum('vendite_iva');
                        $totVenditaTotale = $monthlyStatistics->sum('vendite_totale');
                        $totAcquistoImponibile = $monthlyStatistics->sum('acquisti_imponibile');
                        $totAcquistoIva = $monthlyStatistics->sum('acquisti_iva');
                        $totAcquistoTotale = $monthlyStatistics->sum('acquisti_totale');
                        $totDifferenzaIva = $totVenditaIva - $totAcquistoIva;
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th rowspan="2" class="px-4 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider align-bottom border-r">Mese</th>
                                    <th colspan="3" class="px-4 py-2 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r bg-blue-50">Fatture di Vendita</th>
                                    <th colspan="3" class="px-4 py-2 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r bg-orange-50">Fatture di Acquisto</th>
                                    <th rowspan="2" class="px-4 py-2 text-center text-xs font-bold text-gray-700 uppercase tracking-wider align-bottom">Differenza IVA</th>
                                </tr>
                                <tr>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Imponibile</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">IVA</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50 border-r">Totale Fatt.</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-orange-50">Imponibile</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-orange-50">IVA</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider bg-orange-50 border-r">Totale Fatt.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($monthlyStatistics as $month)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 border-r whitespace-nowrap">
                                            <i class="fas fa-calendar-day text-lime-500 mr-2"></i>
                                            {{ $month->month_label }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($month->vendite_imponibile, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($month->vendite_iva, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 border-r">{{ number_format($month->vendite_totale, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($month->acquisti_imponibile, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($month->acquisti_iva, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 border-r">{{ number_format($month->acquisti_totale, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold {{ $month->differenza_iva > 0 ? 'bg-red-100 text-red-700' : ($month->differenza_iva < 0 ? 'bg-green-100 text-green-700' : 'text-gray-500') }}">
                                            {{ number_format($month->differenza_iva, 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 border-r">TOTALE PERIODO</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">{{ number_format($totVenditaImponibile, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">{{ number_format($totVenditaIva, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-green-600 border-r">{{ number_format($totVenditaTotale, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">{{ number_format($totAcquistoImponibile, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">{{ number_format($totAcquistoIva, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-green-600 border-r">{{ number_format($totAcquistoTotale, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold {{ $totDifferenzaIva > 0 ? 'bg-red-100 text-red-700' : ($totDifferenzaIva < 0 ? 'bg-green-100 text-green-700' : 'text-gray-500') }}">
                                        {{ number_format($totDifferenzaIva, 2, ',', '.') }} €
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Legenda -->
                    <div class="flex justify-end gap-2 mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-red-500 text-white">
                            <i class="fas fa-square mr-1.5 text-[8px]"></i> IVA A DEBITO
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-green-500 text-white">
                            <i class="fas fa-square mr-1.5 text-[8px]"></i> IVA A CREDITO
                        </span>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-chart-simple text-4xl text-gray-300 mb-2"></i>
                        <p>Nessun dato disponibile per il periodo selezionato</p>
                    </div>
                @endif
            </div>
        </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clearOwnershipInput', () => {
                const input = document.getElementById('stats_general_ownership_input');
                if (input) input.value = '';
            });
        });
    </script>
</div>