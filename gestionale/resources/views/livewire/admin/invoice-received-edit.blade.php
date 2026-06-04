<div>
    <style>
        .col-code { width: 80px; min-width: 80px; }
        .col-description { width: 250px; min-width: 250px; }
        .col-quantity { width: 90px; min-width: 90px; }
        .col-price { width: 100px; min-width: 100px; }
        .col-taxable { width: 110px; min-width: 110px; }
        .col-um { width: 70px; min-width: 70px; }
        .col-discount { width: 80px; min-width: 80px; }
        .col-vat { width: 210px; min-width: 210px; }
        .col-cost-center { width: 160px; min-width: 160px; }
        .col-vehicle { width: 160px; min-width: 160px; }
        .col-actions { width: 50px; min-width: 50px; }
        
        .invoice-table input, .invoice-table select { width: 100%; }
        [x-cloak] { display: none !important; }
        
        .totals-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 100%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        .totals-card .total-item { padding: 0.75rem 0; }
        .totals-card .total-label { font-size: 0.7rem; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem; }
        .totals-card .total-value { font-size: 1.25rem; font-weight: 700; color: #1f2937; }
        .totals-card .total-grande { background: #f9fafb; border-radius: 0.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; }
        .totals-card .total-grande .total-value { font-size: 1.5rem; color: #10b981; }
        
        .readonly-badge {
            background-color: #fef3c7;
            color: #d97706;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .manual-badge {
            background-color: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .autocomplete-item {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .autocomplete-item:hover { background-color: #f3f4f6; }
        
        .animate-slide-in {
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Select IVA disabilitata ma visibile */
        select:disabled {
            background-color: #f9fafb;
            color: #374151;
            opacity: 1;
        }
        input:disabled {
            background-color: #f9fafb;
            color: #374151;
            opacity: 1;
        }
    </style>
    
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit text-lime-500 mr-2"></i> Modifica Fattura di Acquisto
            @if($is_manual)
                <span class="manual-badge ml-3">
                    <i class="fas fa-hand-paper mr-1"></i> Fattura Manuale — tutti i campi modificabili
                </span>
            @else
                <span class="readonly-badge ml-3">
                    <i class="fas fa-file-import mr-1"></i> Importata da XML — solo Causale, Centri di Costo e Mezzi modificabili
                </span>
            @endif
        </h1>
        <a href="{{ route('admin.invoices-received.index') }}" 
           class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center" title="Torna all'elenco">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <form wire:submit="update" class="bg-white rounded-lg shadow p-6">
        <!-- Layout a 2 colonne -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
            <!-- Colonna SINISTRA -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Proprietà <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="id_ownership" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                {{ $isReadonly ? 'disabled' : '' }}>
                            <option value="">Seleziona proprietà</option>
                            @foreach($ownerships as $o)
                                <option value="{{ $o->id_proprieta }}">{{ $o->RagAbbrev }}</option>
                            @endforeach
                        </select>
                        @error('id_ownership') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo Documento <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="type_invoice" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                                {{ $isReadonly ? 'disabled' : '' }}>
                            <option value="">Seleziona tipo</option>
                            @foreach($typeDocuments as $code => $label)
                                <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            N. Fattura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="n_invoice" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                               {{ $isReadonly ? 'disabled' : '' }}>
                        @error('n_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fornitore <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <div class="relative">
                                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                    <input type="text"
                                        id="supplier_input"
                                        wire:model.live.debounce.300ms="supplierSearch"
                                        value="{{ $supplierSearch }}"
                                        x-on:focus="if (!{{ $isReadonly ? 'true' : 'false' }}) open = true"
                                        x-on:input="if (!{{ $isReadonly ? 'true' : 'false' }}) { open = true; @this.set('supplierSearch', $event.target.value); }"
                                        placeholder="Cerca fornitore..."
                                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off"
                                        {{ $isReadonly ? 'readonly' : '' }}>
                                    @if($selectedSupplierId && !$isReadonly)
                                        <button type="button"
                                            wire:click="clearSupplier"
                                            x-on:click="document.getElementById('supplier_input').value = ''"
                                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                            <i class="fas fa-times-circle text-sm"></i>
                                        </button>
                                    @endif
                                </div>

                                @if(!$isReadonly)
                                <div x-show="open && @entangle('showSupplierDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($supplierResults && count($supplierResults) > 0)
                                        @foreach($supplierResults as $item)
                                            <div
                                                x-on:click="
                                                    open = false;
                                                    document.getElementById('supplier_input').value = '{{ addslashes($item['name']) }}';
                                                    @this.call('selectSupplier', '{{ $item['id'] }}', '{{ addslashes($item['name']) }}');
                                                "
                                                class="autocomplete-item">
                                                <div class="font-medium text-gray-800">{{ $item['name'] }}</div>
                                                @if($item['piva'])
                                                    <div class="text-xs text-gray-500">P.IVA: {{ $item['piva'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                                    @endif
                                </div>
                                @endif
                            </div>
                            
                            @if(!$isReadonly)
                            <button type="button" wire:click="openSupplierModal" 
                                    class="px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" 
                                    title="Nuovo Fornitore">
                                <i class="fas fa-plus"></i>
                            </button>
                            @endif
                        </div>
                        @if($selectedSupplierId && $selectedSupplierName)
                            <div class="mt-1 text-xs text-green-600">
                                <i class="fas fa-check-circle"></i> {{ $selectedSupplierName }}
                            </div>
                        @endif
                        @error('selectedSupplierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Data Fattura <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="data_invoice" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                               {{ $isReadonly ? 'disabled' : '' }}>
                        @error('data_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Causale / Note</label>
                    {{-- Causale è SEMPRE modificabile --}}
                    <textarea wire:model="causale" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500"
                              placeholder="Note aggiuntive..."></textarea>
                </div>
            </div>
            
            <!-- Colonna DESTRA: Totali -->
            <div class="lg:col-span-1">
                <div class="totals-card">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div class="total-item border-b-0 pb-0">
                            <div class="total-label">TOTALE IMPONIBILE</div>
                            <div class="total-value">€ {{ number_format($total_taxable, 2, ',', '.') }}</div>
                        </div>
                        <div class="total-item border-b-0 pb-0">
                            <div class="total-label">TOTALE SCONTI</div>
                            <div class="total-value text-red-500">- € {{ number_format($total_discount, 2, ',', '.') }}</div>
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    @if(count($vatSummary) > 0)
                        <div class="mb-3">
                            <div class="total-label text-center mb-2 pb-1 border-b border-gray-200">DETTAGLIO IVA PER ALIQUOTA</div>
                            @foreach($vatSummary as $vat)
                                <div class="flex justify-between items-center py-1 text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-700">
                                        @if($vat['rate'] == 0)
                                            <span class="text-xs bg-gray-100 px-1 py-0.5 rounded">{{ $vat['description'] }}</span>
                                        @else
                                            <span class="text-xs bg-blue-100 text-blue-800 px-1 py-0.5 rounded">{{ $vat['rate_percent'] }}%</span>
                                            <span class="text-gray-600">IVA</span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold">€ {{ number_format($vat['vat_amount'], 2, ',', '.') }}</div>
                                        <div class="text-xs text-gray-500">su € {{ number_format($vat['taxable_amount'], 2, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="total-item border-b-0 pb-0">
                            <div class="total-label">TOTALE IVA</div>
                            <div class="total-value">€ {{ number_format($total_vat, 2, ',', '.') }}</div>
                        </div>
                        <div class="total-grande m-0">
                            <div class="total-label">TOTALE FATTURA</div>
                            <div class="total-value">€ {{ number_format($importo_totale, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CENTRO DI COSTO - Applica a TUTTE le righe --}}
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-6">
            <label class="block text-sm font-medium mb-2 text-blue-800">
                Applica Centro di Costo a TUTTE le {{ count($rows) }} righe
            </label>
            <div class="relative">
                <input type="text"
                    id="cost_center_all_input"
                    value="{{ $cost_center_all_search }}"
                    wire:model.live.debounce.500ms="cost_center_all_search"
                    class="w-full border rounded-lg px-3 py-2"
                    placeholder="Cerca centro di costo..."
                    autocomplete="off">
                @if(!empty($cost_center_all_results))
                    <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach($cost_center_all_results as $cc)
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                                wire:click="applyCostCenterToAllRows({{ $cc['id'] }})">
                                {{ $cc['name'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($cost_center_all_search)
                <div class="text-xs text-blue-600 mt-2">✅ Selezionato: "{{ $cost_center_all_search }}"</div>
            @endif
        </div>

        {{-- MEZZO - Applica a TUTTE le righe --}}
        <div class="bg-green-50 p-4 rounded-lg border border-green-200 mt-4">
            <label class="block text-sm font-medium mb-2 text-green-800">
                Applica Mezzo a TUTTE le {{ count($rows) }} righe
            </label>
            <div class="relative">
                <input type="text"
                    id="vehicle_all_input"
                    value="{{ $vehicle_all_search }}"
                    wire:model.live.debounce.500ms="vehicle_all_search"
                    class="w-full border rounded-lg px-3 py-2"
                    placeholder="Cerca mezzo (targa, marca, modello)..."
                    autocomplete="off">
                @if(!empty($vehicle_all_results))
                    <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach($vehicle_all_results as $vehicle)
                            <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                                wire:click="applyVehicleToAllRows({{ $vehicle['id'] }})"
                                onclick="(function(){var el=document.getElementById('vehicle_all_input'); if(el){ el.value='{{ addslashes($vehicle['name']) }}'; el.dispatchEvent(new Event('input',{bubbles:true})); } })()">
                                {{ $vehicle['name'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($vehicle_all_search)
                <div class="text-xs text-green-600 mt-2">✅ Selezionato: "{{ $vehicle_all_search }}"</div>
            @endif
        </div>
        
        <!-- RIGHE FATTURA -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-blue-500 mr-2"></i> Righe Fattura
                </h3>
                @if(!$isReadonly)
                <button type="button" wire:click="addRow" 
                        class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi riga
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="invoice-table min-w-full border rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="col-description px-2 py-2 text-left text-xs font-medium">Descrizione</th>
                            <th class="col-quantity px-2 py-2 text-right text-xs font-medium">Qtà</th>
                            <th class="col-price px-2 py-2 text-right text-xs font-medium">Prezzo Unit.</th>
                            <th class="col-discount px-2 py-2 text-right text-xs font-medium">Sconto%</th>
                            <th class="col-vat px-2 py-2 text-left text-xs font-medium">Aliquota IVA</th>
                            <th class="col-taxable px-2 py-2 text-right text-xs font-medium">Imponibile</th>
                            <th class="col-cost-center px-2 py-2 text-left text-xs font-medium">Centro Costo</th>
                            <th class="col-vehicle px-2 py-2 text-left text-xs font-medium">Mezzo</th>
                            <th class="col-actions px-2 py-2 text-center text-xs font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                        <tr class="border-b hover:bg-gray-50" wire:key="row-{{ $index }}">
                            <td class="col-description px-2 py-1">
                                <input type="text" wire:model.live="rows.{{ $index }}.description"
                                    class="w-full px-1 py-1 text-sm border rounded-md"
                                    {{ $isReadonly ? 'disabled' : '' }}>
                            </td>
                            <td class="col-quantity px-2 py-1">
                                <input type="number" step="0.001" wire:model.live="rows.{{ $index }}.quantity"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right"
                                    {{ $isReadonly ? 'disabled' : '' }}>
                            </td>
                            <td class="col-price px-2 py-1">
                                <input type="number" step="0.0001" wire:model.live="rows.{{ $index }}.unit_price"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right"
                                    {{ $isReadonly ? 'disabled' : '' }}>
                            </td>
                            <td class="col-discount px-2 py-1">
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.discount_percentage" 
                                       class="w-full px-1 py-1 text-sm border rounded-md text-right"
                                       {{ $isReadonly ? 'disabled' : '' }}>
                            </td>

                            {{--
                                Select IVA — il value di ogni option è la percentuale (es. 22).
                                Il wire:model contiene la percentuale (es. 22).
                                Livewire selezionerà automaticamente l'option il cui value
                                corrisponde al valore di rows.$index.vat_rate.
                                
                                Le aliquote a 0% con sdi_nature diverse hanno tutte value=0,
                                quindi il select mostrerà la prima con rate=0 trovata.
                                Per le fatture importate con più nature diverse (N1, N2.2, N4...)
                                questo è un limite accettabile: la riga mostra "IVA 0%" e
                                l'informazione reale è nei vatSummaries.
                            --}}
                            <td class="col-vat px-2 py-1">
                                <select wire:model.live="rows.{{ $index }}.vat_rate_id"
                                    class="w-full px-1 py-1 text-sm border rounded-md"
                                    {{ $isReadonly ? 'disabled' : '' }}>
                                    @foreach($vatRatesList as $vat)
                                        @php
                                            $displayText = number_format($vat['rate_percent'], 0) . '%';
                                            if ($vat['rate_percent'] == 0 && !empty($vat['sdi_nature'])) {
                                                $displayText .= ' — ' . $vat['sdi_nature'];
                                            }
                                        @endphp
                                        <option value="{{ $vat['id'] }}">{{ $displayText }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="col-taxable px-2 py-1">
                                <input type="text" readonly
                                    value="{{ number_format($row['taxable_amount'] ?? 0, 2, ',', '.') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right bg-gray-100 font-semibold">
                            </td>

                            <!-- Campo Autocomplete Centro Di Costo con x-teleport -->
                            <td class="col-cost-center px-2 py-1">
                                <div class="w-full"
                                    x-data="{
                                        open: false,
                                        triggerRect: {},
                                        updateRect() {
                                            this.triggerRect = this.$refs.ccTrigger.getBoundingClientRect();
                                        }
                                    }"
                                    x-on:click.away="open = false">
                                    <div class="relative" x-ref="ccTrigger">
                                        <i class="fas fa-building absolute left-2 top-2 text-gray-400 text-xs"></i>
                                        <input type="text"
                                            id="cost_center_input_{{ $index }}"
                                            wire:model.live.debounce.300ms="costCenterSearch.{{ $index }}"
                                            x-ref="ccInput"
                                            x-on:focus="updateRect(); open = true"
                                            x-on:input="updateRect(); open = true"
                                            x-effect="$el.value = $wire.costCenterSearch[{{ $index }}] ?? ''"
                                            placeholder="Cerca centro..."
                                            class="w-full pl-7 pr-6 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                            autocomplete="off">
                                    </div>

                                    <!-- Dropdown teleportato nel body, posizionato via JS -->
                                    <template x-teleport="body">
                                        <div
                                            x-show="open && @entangle('showCostCenterDropdown.' . $index)"
                                            x-on:click.away="open = false"
                                            :style="`position:fixed; z-index:9999; width:250px; top:${triggerRect.bottom + window.scrollY}px; left:${triggerRect.left + window.scrollX}px;`"
                                            class="bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            @if(isset($costCenterResults[$index]) && count($costCenterResults[$index]) > 0)
                                                @foreach($costCenterResults[$index] as $cc)
                                                    <div
                                                        x-on:click="
                                                            open = false;
                                                            document.getElementById('cost_center_input_{{ $index }}').value = '{{ addslashes($cc['name']) }}';
                                                            @this.set('costCenterSearch.{{ $index }}', '{{ addslashes($cc['name']) }}');
                                                            @this.set('rows.{{ $index }}.id_cost_center', '{{ $cc['id'] }}');
                                                            @this.set('rows.{{ $index }}.cost_center_name', '{{ addslashes($cc['name']) }}');
                                                            @this.set('selectedCostCenterId.{{ $index }}', '{{ $cc['id'] }}');
                                                            @this.set('selectedCostCenterName.{{ $index }}', '{{ addslashes($cc['name']) }}');
                                                            @this.set('showCostCenterDropdown.{{ $index }}', false);
                                                            @this.call('calculateTotals');
                                                        "
                                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                                        <div class="font-medium text-gray-800">{{ $cc['name'] }}</div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="px-3 py-2 text-sm text-gray-500 text-center">
                                                    @if(strlen($costCenterSearch[$index] ?? '') >= 2)
                                                        Nessun centro di costo trovato
                                                    @else
                                                        Digita almeno 2 caratteri
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <!-- Campo Autocomplete Mezzi con x-teleport -->
                            <td class="col-vehicle px-2 py-1">
                                <div class="w-full"
                                    x-data="{
                                        open: false,
                                        triggerRect: {},
                                        updateRect() {
                                            this.triggerRect = this.$refs.vhTrigger.getBoundingClientRect();
                                        }
                                    }"
                                    x-on:click.away="open = false">
                                    <div class="relative" x-ref="vhTrigger">
                                        <i class="fas fa-truck absolute left-2 top-2 text-gray-400 text-xs"></i>
                                        <input type="text"
                                            id="vehicle_input_{{ $index }}"
                                            wire:model.live.debounce.300ms="vehicleSearch.{{ $index }}"
                                            x-ref="vhInput"
                                            x-on:focus="updateRect(); open = true"
                                            x-on:input="updateRect(); open = true"
                                            x-effect="$el.value = $wire.vehicleSearch[{{ $index }}] ?? ''"
                                            placeholder="Cerca mezzo..."
                                            class="w-full pl-7 pr-6 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                            autocomplete="off">
                                    </div>

                                    <!-- Dropdown teleportato nel body -->
                                    <template x-teleport="body">
                                        <div
                                            x-show="open && @entangle('showVehicleDropdown.' . $index)"
                                            x-on:click.away="open = false"
                                            :style="`position:fixed; z-index:9999; width:250px; top:${triggerRect.bottom + window.scrollY}px; left:${triggerRect.left + window.scrollX}px;`"
                                            class="bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            @if(isset($vehicleResults[$index]) && count($vehicleResults[$index]) > 0)
                                                @foreach($vehicleResults[$index] as $vehicle)
                                                    <div
                                                        x-on:click="
                                                            open = false;
                                                            document.getElementById('vehicle_input_{{ $index }}').value = '{{ addslashes($vehicle['name']) }}';
                                                            @this.set('vehicleSearch.{{ $index }}', '{{ addslashes($vehicle['name']) }}');
                                                            @this.set('rows.{{ $index }}.id_vehicle', '{{ $vehicle['id'] }}');
                                                            @this.set('rows.{{ $index }}.vehicle_name', '{{ addslashes($vehicle['name']) }}');
                                                            @this.set('selectedVehicleId.{{ $index }}', '{{ $vehicle['id'] }}');
                                                            @this.set('selectedVehicleName.{{ $index }}', '{{ addslashes($vehicle['name']) }}');
                                                            @this.set('showVehicleDropdown.{{ $index }}', false);
                                                            @this.call('calculateTotals');
                                                        "
                                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
                                                        <div class="font-medium text-gray-800">{{ $vehicle['name'] }}</div>
                                                        @if($vehicle['plate'] ?? false)
                                                            <div class="text-xs text-gray-500">Targa: {{ $vehicle['plate'] }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="px-3 py-2 text-sm text-gray-500 text-center">
                                                    @if(strlen($vehicleSearch[$index] ?? '') >= 2)
                                                        Nessun mezzo trovato
                                                    @else
                                                        Digita almeno 2 caratteri
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <td class="col-actions px-2 py-1 text-center">
                                @if(!$isReadonly && $index > 0)
                                    <button type="button" wire:click="removeRow({{ $index }})" 
                                            class="text-red-500 hover:text-red-700 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SCADENZE PAGAMENTO -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i> Scadenze Pagamento
                </h3>
                @if(!$isReadonly)
                <button type="button" wire:click="addPayment" 
                        class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi scadenza
                </button>
                @endif
            </div>

            @if(count($payments) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium w-32">Data Scadenza</th>
                                <th class="px-3 py-2 text-right text-xs font-medium w-32">Importo (€)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium w-40">Metodo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium w-48">IBAN</th>
                                <th class="px-3 py-2 text-left text-xs font-medium w-32">Stato</th>
                                <th class="px-3 py-2 text-center text-xs font-medium w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $index => $payment)
                            <tr class="border-b hover:bg-gray-50" wire:key="payment-{{ $index }}">
                                <td class="px-3 py-2">
                                    <input type="date" 
                                        wire:model.live="payments.{{ $index }}.due_date" 
                                        class="w-full px-2 py-1 text-sm border rounded-md"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" 
                                        wire:model.live="payments.{{ $index }}.amount" 
                                        class="w-full px-2 py-1 text-sm border rounded-md text-right"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                </td>
                                <td class="px-3 py-2">
                                    <select wire:model.live="payments.{{ $index }}.payment_method" 
                                        class="w-full px-2 py-1 text-sm border rounded-md"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                        <option value="">— nessuna —</option>
                                        @foreach($paymentMethods as $code => $label)
                                            <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" 
                                        wire:model.live="payments.{{ $index }}.iban" 
                                        placeholder="IT00 XXXX..."
                                        class="w-full px-2 py-1 text-sm border rounded-md"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                </td>
                                <td class="px-3 py-2">
                                    <select wire:model.live="payments.{{ $index }}.status"
                                        class="w-full px-2 py-1 text-sm border rounded-md">
                                        <option value="issued">Emessa</option>
                                        <option value="paid">Pagata</option>
                                        <option value="overdue">Scaduta</option>
                                        <option value="cancelled">Annullata</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if(!$isReadonly && count($payments) > 1)
                                    <button type="button" 
                                        wire:click="removePayment({{ $index }})" 
                                        class="text-red-500 hover:text-red-700 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-3 py-2 text-right font-bold">Totale Scadenze:</td>
                                <td class="px-3 py-2 text-right font-bold">
                                    € {{ number_format($total_payments_amount, 2, ',', '.') }}
                                </td>
                                <td colspan="4"></td>
                            </tr>
                            @if(abs($total_payments_amount - $importo_totale) > 0.01 && $importo_totale > 0)
                            <tr>
                                <td class="px-3 py-2 text-right text-orange-600">⚠️ Differenza:</td>
                                <td class="px-3 py-2 text-right text-orange-600 font-bold">
                                    € {{ number_format($importo_totale - $total_payments_amount, 2, ',', '.') }}
                                </td>
                                <td colspan="4" class="text-xs text-orange-500 px-3">
                                    L'importo totale delle scadenze non corrisponde al totale fattura
                                </td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-4 bg-gray-50 rounded-lg border border-dashed">
                    <i class="fas fa-calendar-alt mr-2"></i> Nessuna scadenza inserita
                    @if(!$isReadonly)
                    <button type="button" wire:click="addPayment" class="ml-3 text-purple-600 hover:text-purple-700 underline">
                        Aggiungi scadenza
                    </button>
                    @endif
                </div>
            @endif
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-received.index') }}" 
               class="px-4 py-2 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                Annulla
            </a>
            <button type="submit" 
                    class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Aggiorna Fattura
            </button>
        </div>
    </form>
    
    <!-- MODALE CREAZIONE NUOVO FORNITORE -->
    @if($showSupplierModal && !$isReadonly)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ open: true }" x-show="open" 
         x-on:keydown.escape.window="open = false; $wire.closeSupplierModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-on:click="open = false; $wire.closeSupplierModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Nuovo Fornitore</h3>
                        <button type="button" wire:click="closeSupplierModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale / Nome <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="newSupplierName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        @error('newSupplierName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                            <input type="text" wire:model="newSupplierPiva" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                            <input type="text" wire:model="newSupplierCf" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="newSupplierEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                            <input type="text" wire:model="newSupplierPhone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                        <input type="text" wire:model="newSupplierAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                            <input type="text" wire:model="newSupplierCap" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                            <input type="text" wire:model="newSupplierCity" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                            <input type="text" wire:model="newSupplierProvince" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" wire:click="closeSupplierModal" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Annulla</button>
                    <button type="button" wire:click="createSupplier" 
                            class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600">
                        <i class="fas fa-save mr-2"></i> Crea Fornitore
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('alert', (data) => {
                const type = data.type || (data[0]?.type);
                const message = data.message || (data[0]?.message);
                if (!message) return;
                const alertDiv = document.createElement('div');
                alertDiv.className = `fixed top-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg min-w-[300px] animate-slide-in ${
                    type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' :
                    type === 'error'   ? 'bg-red-100 border-l-4 border-red-500 text-red-700' :
                    type === 'warning' ? 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700' :
                                         'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
                }`;
                alertDiv.innerHTML = `<div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-500 hover:text-gray-700">×</button>
                </div>`;
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 5000);
            });
        });
    </script>
</div>