<div>
    <style>
        .autocomplete-dropdown {
            position: absolute;
            z-index: 9999;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-height: 250px;
            overflow-y: auto;
            width: 100%;
            margin-top: 2px;
        }
        .autocomplete-item {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .autocomplete-item:hover {
            background-color: #f3f4f6;
        }
        .alert-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .disabled-input {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
        .bg-blue-50 {
            background-color: #eff6ff;
        }
        .relative {
            position: relative;
        }
    </style>

    <div class="flex justify-end items-center mb-4 relative group">
        <a href="{{ route('admin.invoices-received.index') }}" 
        class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
            Torna all'elenco 
            <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- Upload XML -->
        <div class="mb-6 p-4 border rounded-lg bg-gray-50">
            <h2 class="text-lg font-bold mb-4">1. Carica file XML</h2>
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">File XML Fattura Elettronica</label>
                    <input type="file" wire:model="xml_file" accept=".xml" class="w-full border rounded-lg px-3 py-2">
                    @error('xml_file') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <button type="button" wire:click="uploadXml" wire:loading.attr="disabled" 
                        class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <span wire:loading.remove>Analizza XML</span>
                    <span wire:loading>Analisi...</span>
                </button>
            </div>
        </div>

        @if($xml_parsed)
            <!-- Riepilogo dati estratti -->
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <h3 class="font-bold text-green-800 mb-2">📋 Dati estratti dall'XML</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="font-medium">Fattura n.</span> {{ $n_invoice ?: 'Non trovato' }}</div>
                    <div><span class="font-medium">Data</span> {{ $data_invoice ? date('d/m/Y', strtotime($data_invoice)) : 'Non trovata' }}</div>
                    <div><span class="font-medium">Totale</span> {{ number_format($importo_totale, 2, ',', '.') }} €</div>
                    <div><span class="font-medium">Tipo</span> {{ $tipoDocumento[$type_invoice] ?? $type_invoice ?: 'Non trovato' }}</div>
                    <div><span class="font-medium">Fornitore</span> {{ $fornitore_denominazione ?: 'Non trovato' }}</div>
                    <div><span class="font-medium">P.IVA Fornitore</span> {{ $fornitore_partita_iva ?: 'Non trovata' }}</div>
                </div>
            </div>

            <form wire:submit="save" class="space-y-6">
                <h2 class="text-lg font-bold mb-4">2. Verifica e Conferma</h2>
                
                <!-- Alert fornitore non trovato -->
                @if($supplier_not_found && !$supplier_found)
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>⚠️ Fornitore non trovato nel database</strong>
                                <p class="text-sm mt-1">
                                    Denominazione: {{ $fornitore_denominazione }}<br>
                                    P.IVA: {{ $fornitore_partita_iva }}
                                </p>
                            </div>
                            <button type="button" wire:click="createSupplierAutomatically" 
                                    class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                                Crea automaticamente
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Campi Fattura - TUTTI DISABILITATI -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fornitore (Cedente)</label>
                        <input type="text" value="{{ $supplier_display ?: 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Committente (Cessionario)</label>
                        <input type="text" value="{{ $committente_denominazione ?: 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Numero Fattura</label>
                        <input type="text" value="{{ $n_invoice ?: 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Data Fattura</label>
                        <input type="text" value="{{ $data_invoice ? date('d/m/Y', strtotime($data_invoice)) : 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo Documento</label>
                        <input type="text" value="{{ $tipoDocumento[$type_invoice] ?? $type_invoice ?: 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Stato</label>
                        <input type="text" value="Emessa" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Divisa</label>
                        <input type="text" value="{{ $currencies[$divisa] ?? $divisa ?: 'EUR' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">SDI ID</label>
                        <input type="text" value="{{ $sdi_id ?: 'Non disponibile' }}" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>
                    </div>
                </div>

                <!-- Causale / Note -->
                <div>
                    <label class="block text-sm font-medium mb-1">Causale / Note</label>
                    <textarea rows="2" class="w-full border rounded-lg px-3 py-2 disabled-input" disabled>{{ $causale ?: 'Non presente' }}</textarea>
                </div>

                <!-- ============================================ -->
                <!-- CENTRO DI COSTO - Applica a TUTTE le righe -->
                <!-- ============================================ -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200" wire:key="cost-center-all-{{ $cost_center_all_search }}>
                    <label class="block text-sm font-medium mb-2 text-blue-800">
                        Applica Centro di Costo a TUTTE le {{ count($rows) }} righe
                    </label>
                    <div class="relative">
                        <input type="text" 
                            wire:model.live.debounce.300ms="cost_center_all_search"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Cerca centro di costo..."
                            autocomplete="off">
                        @if(!empty($cost_center_all_results))
                            <div class="autocomplete-dropdown">
                                @foreach($cost_center_all_results as $cc)
                                    <div class="autocomplete-item" wire:click="applyCostCenterToAllRows({{ $cc['id'] }})">
                                        {{ $cc['name'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($cost_center_all_search)
                        <div class="text-xs text-blue-600 mt-2">
                            ✅ Selezionato: "{{ $cost_center_all_search }}"
                        </div>
                    @endif
                </div>

                <!-- ============================================ -->
                <!-- MEZZO - Applica a TUTTE le righe -->
                <!-- ============================================ -->
                <div class="bg-green-50 p-4 rounded-lg border border-green-200" wire:key="vehicle-all-{{ $vehicle_all_search }}">
                    <label class="block text-sm font-medium mb-2 text-green-800">
                        Applica Mezzo a TUTTE le {{ count($rows) }} righe
                    </label>
                    <div class="relative">
                        <input type="text" 
                            wire:model.live.debounce.300ms="vehicle_all_search"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Cerca mezzo (targa, marca, modello)..."
                            autocomplete="off">
                        @if(!empty($vehicle_all_results))
                            <div class="autocomplete-dropdown">
                                @foreach($vehicle_all_results as $vehicle)
                                    <div class="autocomplete-item" wire:click="applyVehicleToAllRows({{ $vehicle['id'] }})">
                                        {{ $vehicle['name'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($vehicle_all_search)
                        <div class="text-xs text-green-600 mt-2">
                            ✅ Selezionato: "{{ $vehicle_all_search }}"
                        </div>
                    @endif
                </div>

                <!-- ============================================ -->
                <!-- RIGHE FATTURA -->
                <!-- ============================================ -->
                <div>
                    <h3 class="font-bold mb-4">Righe Fattura ({{ count($rows) }})</h3>
                    @if(count($rows) > 0)
                        <div class="overflow-x-auto">
                            <table class="table-fixed w-full border rounded-lg">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-32">Codice Art.</th>
                                        <th class="px-3 py-2 text-left w-80">Descrizione</th>
                                        <th class="px-3 py-2 text-right w-20">Q.ta</th>
                                        <th class="px-3 py-2 text-center w-16">U.M.</th>
                                        <th class="px-3 py-2 text-right w-32">Prezzo Unit.</th>
                                        <th class="px-3 py-2 text-right w-20">Sconto</th>
                                        <th class="px-3 py-2 text-right w-32">Prezzo Tot.</th>
                                        <th class="px-3 py-2 text-center w-16">Iva%</th>
                                        <th class="px-3 py-2 text-left w-40">Natura</th>
                                        <th class="px-3 py-2 text-left w-64">Centro Costo</th>
                                        <th class="px-3 py-2 text-left w-64">Mezzo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $index => $row)
                                    <tr class="border-b hover:bg-gray-50" wire:key="row-{{ $index }}-{{ $row['id_cost_center'] ?? 'none' }}-{{ $row['id_vehicle'] ?? 'none' }}">

                                        <!-- Codice Articolo -->
                                        <td class="px-3 py-2 align-top">
                                            @if(!empty($row['codice_articolo']))
                                                @foreach($row['codice_articolo'] as $codice)
                                                    <div class="text-xs">
                                                        <span class="font-medium">{{ $codice['tipo'] }}:</span>
                                                        <span class="text-gray-600">{{ $codice['valore'] }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>

                                        <!-- Descrizione -->
                                        <td class="px-3 py-2">
                                            <span class="text-sm">{{ $row['description'] ?: 'Descrizione non disponibile' }}</span>
                                        </td>

                                        <!-- Quantità -->
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-sm">{{ number_format($row['quantity'], 3, ',', '.') }}</span>
                                        </td>

                                        <!-- Unità di Misura -->
                                        <td class="px-3 py-2 text-center">
                                            <span class="text-xs">{{ $row['unita_misura'] ?: '-' }}</span>
                                        </td>

                                        <!-- Prezzo Unitario -->
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-sm">{{ number_format($row['unit_price'], 4, ',', '.') }} €</span>
                                        </td>

                                        <!-- Sconto -->
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-sm">{{ $row['discount_percentage'] > 0 ? number_format($row['discount_percentage'], 2, ',', '.') . '%' : '-' }}</span>
                                        </td>

                                        <!-- Prezzo Totale -->
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-sm font-medium">
                                                {{ number_format(($row['quantity'] * $row['unit_price']) * (1 - ($row['discount_percentage'] / 100)), 2, ',', '.') }} €
                                            </span>
                                        </td>

                                        <!-- Iva % -->
                                        <td class="px-3 py-2 text-center">
                                            <span class="text-sm">{{ $row['aliquota_iva'] ?? '-' }}%</span>
                                        </td>

                                        <!-- Natura -->
                                        <td class="px-3 py-2 align-top">
                                            @if(!empty($row['natura']))
                                                <div class="text-xs">
                                                    <span class="font-medium">{{ $row['natura'] }}</span>
                                                    @php
                                                        $naturaLabel = $this->getNaturaLabel($row['natura']);
                                                    @endphp
                                                    @if($naturaLabel)
                                                        <div class="text-gray-500 text-xs">{{ $naturaLabel }}</div>
                                                    @endif
                                                    @if(!empty($row['riferimento_amministrativo']))
                                                        <div class="text-gray-400 text-xs mt-1">Rif. Amm.: {{ $row['riferimento_amministrativo'] }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>

                                        <!-- Centro di Costo -->
                                        <td class="px-3 py-2">
                                            <div class="relative">
                                                <input type="text" 
                                                    wire:model.live.debounce.300ms="row_cost_center_search.{{ $index }}"
                                                    placeholder="Cerca centro di costo..."
                                                    class="w-full border rounded-lg px-2 py-1 text-sm"
                                                    autocomplete="off">
                                                @if(!empty($row_cost_center_results[$index] ?? []))
                                                    <div class="autocomplete-dropdown">
                                                        @foreach($row_cost_center_results[$index] as $cc)
                                                            <div class="autocomplete-item" wire:click="selectCostCenterForRow({{ $cc['id'] }}, {{ $index }})">
                                                                {{ $cc['name'] }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            @if($row['cost_center_name'])
                                                <div class="text-xs text-green-600 mt-1">✓ {{ $row['cost_center_name'] }}</div>
                                            @else
                                                <div class="text-xs text-gray-400 mt-1">Non assegnato</div>
                                            @endif
                                        </td>

                                        <!-- Mezzo -->
                                        <td class="px-3 py-2">
                                            <div class="relative">
                                                <input type="text" 
                                                    wire:model.live.debounce.300ms="row_vehicle_search.{{ $index }}"
                                                    placeholder="Cerca mezzo (targa, marca, modello)..."
                                                    class="w-full border rounded-lg px-2 py-1 text-sm"
                                                    autocomplete="off">
                                                @if(!empty($row_vehicle_results[$index] ?? []))
                                                    <div class="autocomplete-dropdown" style="z-index: 9999;">
                                                        @foreach($row_vehicle_results[$index] as $vehicle)
                                                            <div class="autocomplete-item" wire:click="selectVehicleForRow({{ $vehicle['id'] }}, {{ $index }})">
                                                                {{ $vehicle['name'] }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            @if($row['vehicle_name'] ?? false)
                                                <div class="text-xs text-green-600 mt-1">✓ {{ $row['vehicle_name'] }}</div>
                                            @else
                                                <div class="text-xs text-gray-400 mt-1">Non assegnato</div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="9" class="px-3 py-3 text-right font-bold">TOTALE DOCUMENTO</td>
                                        <td class="px-3 py-3 text-right font-bold text-lg">
                                            {{ number_format($importo_totale, 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-4 bg-gray-50 rounded-lg">
                            Nessuna riga trovata nell'XML
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 transition">Annulla</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Importa Fattura
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Script per Alert -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('alert', (data) => {
                const type = data.type || (data[0]?.type);
                const message = data.message || (data[0]?.message);
                
                if (!message) return;
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert-toast px-4 py-3 rounded-lg shadow-lg min-w-[300px] ${
                    type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
                    type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
                    type === 'warning' ? 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700' :
                    'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
                }`;
                alertDiv.innerHTML = `<div class="flex items-center justify-between"><span>${message}</span><button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-500 hover:text-gray-700">×</button></div>`;
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 5000);
            });
        });
    </script>
</div>