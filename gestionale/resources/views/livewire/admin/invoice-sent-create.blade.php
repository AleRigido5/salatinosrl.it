<div>
    <style>
        /* Larghezze fisse per le colonne */
        .col-code { width: 80px; min-width: 80px; }
        .col-description { width: 250px; min-width: 250px; }
        .col-quantity { width: 90px; min-width: 90px; }
        .col-price { width: 100px; min-width: 100px; }
        .col-taxable { width: 110px; min-width: 110px; }
        .col-um { width: 70px; min-width: 70px; }
        .col-discount { width: 80px; min-width: 80px; }
        .col-vat { width: 180px; min-width: 180px; }
        .col-cost-center { width: 160px; min-width: 160px; }
        .col-vehicle { width: 160px; min-width: 160px; }
        .col-actions { width: 50px; min-width: 50px; }
        
        @media (min-width: 1920px) {
            .col-description { width: 300px; min-width: 300px; }
            .col-cost-center { width: 200px; min-width: 200px; }
            .col-vehicle { width: 200px; min-width: 200px; }
            .col-vat { width: 220px; min-width: 220px; }
        }
        
        @media (max-width: 1400px) {
            .col-description { width: 200px; min-width: 200px; }
            .col-cost-center { width: 150px; min-width: 150px; }
            .col-vehicle { width: 150px; min-width: 150px; }
            .col-vat { width: 160px; min-width: 160px; }
        }
        
        .invoice-table {
            white-space: nowrap;
        }
        
        .invoice-table input,
        .invoice-table select {
            width: 100%;
            white-space: normal;
        }
        
        .required-field {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .field-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        [x-cloak] {
            display: none !important;
        }
        
        /* Card totali dettagliata */
        .totals-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 100%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }

        .totals-card .total-item {
            padding: 0.75rem 0;
        }

        .totals-card .total-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .totals-card .total-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .totals-card .total-value.text-red-500 {
            color: #ef4444;
        }

        .totals-card .total-grande {
            background: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
        }

        .totals-card .total-grande .total-value {
            font-size: 1.5rem;
            color: #10b981;
        }

        .totals-card {
            max-height: 500px;
            overflow-y: auto;
        }
        
        /* Stili autocomplete uniformati */
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
            font-size: 0.875rem;
        }
        
        .autocomplete-item:hover {
            background-color: #f3f4f6;
        }
        
        .relative {
            position: relative;
        }
    </style>
    
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i> Nuova Fattura di Vendita
        </h1>
        <div class="relative group">
            <a href="{{ route('admin.invoices-sent.index') }}" 
               class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6">
        <!-- Layout a 2 colonne -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
            <!-- Colonna SINISTRA: Campi del documento -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                        <select wire:model.live="id_ownership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                            <option value="">Seleziona proprietà</option>
                            @foreach($ownerships as $o)
                                <option value="{{ $o->id_proprieta }}">
                                    [{{ $o->id_proprieta }}] {{ $o->RagAbbrev ?? $o->Rag_Soc_intest }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_ownership') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Nella view invoice-sent-create.blade.php, dopo il campo Proprietà -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sezionale <span class="text-red-500">*</span></label>
                        <select wire:model.live="selectedSeriesId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                            <option value="">Seleziona sezionale</option>
                            @foreach($availableSeries as $series)
                                <option value="{{ $series['id'] }}">
                                    {{ $series['code'] }} - {{ $series['name'] }} (Anno {{ $series['year'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedSeriesId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento <span class="text-red-500">*</span></label>
                        <select wire:model.live="type_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                            <option value="">Seleziona tipo</option>
                            @foreach($typeDocuments as $code => $label)
                                <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="n_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        @error('n_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <div class="relative">
                                    <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                    <input type="text"
                                        id="customer_input"
                                        wire:model.live.debounce.300ms="customerSearch"
                                        x-on:focus="open = true"
                                        x-on:input="open = true; @this.set('customerSearch', $event.target.value)"
                                        placeholder="Cerca cliente..."
                                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    @if($selectedCustomerId)
                                        <button type="button"
                                            wire:click="clearCustomer"
                                            x-on:click="document.getElementById('customer_input').value = ''"
                                            class="absolute right-2 top-2 text-gray-400 hover:text-red-500">
                                            <i class="fas fa-times-circle text-sm"></i>
                                        </button>
                                    @endif
                                </div>

                                <div x-show="open && @entangle('showCustomerDropdown')"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    @if($customerResults && count($customerResults) > 0)
                                        @foreach($customerResults as $item)
                                            <div
                                                x-on:click="
                                                    open = false;
                                                    document.getElementById('customer_input').value = '{{ addslashes($item['name']) }}';
                                                    @this.set('customerSearch', '{{ addslashes($item['name']) }}');
                                                    @this.set('selectedCustomerId', '{{ $item['id'] }}');
                                                    @this.set('selectedCustomerName', '{{ addslashes($item['name']) }}');
                                                    @this.set('showCustomerDropdown', false);
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
                            </div>
                            
                            <button type="button" wire:click="openCustomerModal" class="px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" title="Nuovo Cliente">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        @if($selectedCustomerId && $selectedCustomerName)
                            <div class="mt-1 text-xs text-green-600">
                                <i class="fas fa-check-circle"></i> Cliente selezionato: {{ $selectedCustomerName }}
                            </div>
                        @endif
                        @error('selectedCustomerId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.live="data_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                        @error('data_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Causale / Note</label>
                    <textarea wire:model="causale" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500" placeholder="Note aggiuntive..."></textarea>
                </div>
            </div>
            
            <!-- Colonna DESTRA: Card Totali Dettagliata -->
            <div class="lg:col-span-1">
                <div class="totals-card">
                    <!-- RIGA 1: TOTALE IMPONIBILE + TOTALE SCONTI affiancati -->
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
                    
                    <!-- Separatore sottile -->
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <!-- DETTAGLIO IVA PER ALIQUOTA -->
                    @if(count($vatSummary) > 0)
                        <div class="mb-3">
                            <div class="total-label text-center mb-2 pb-1 border-b border-gray-200">DETTAGLIO IVA PER ALIQUOTA</div>
                            @foreach($vatSummary as $vat)
                                <div class="flex justify-between items-center py-1 text-sm border-b border-gray-100 last:border-0">
                                    <div class="font-medium text-gray-700">
                                        @if($vat['rate'] == 0)
                                            @if($vat['nature_code'])
                                                <span class="text-xs bg-gray-100 px-1 py-0.5 rounded">Cod. {{ $vat['nature_code'] }}</span>
                                            @endif
                                            <span class="text-gray-600">{{ Str::limit($vat['description'], 30) }}</span>
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
                    
                    <!-- Separatore sottile -->
                    <div class="border-b border-gray-200 mb-3"></div>
                    
                    <!-- RIGA 2: TOTALE IVA + TOTALE FATTURA affiancati -->
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
        
        <!-- RIGHE FATTURA -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-blue-500 mr-2"></i> Righe Fattura
                </h3>
                <button type="button" wire:click="addRow" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi riga
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="invoice-table min-w-full border rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="col-code px-2 py-2 text-left text-xs font-medium">Codice</th>
                            <th class="col-description px-2 py-2 text-left text-xs font-medium">Descrizione <span class="text-red-500">*</span></th>
                            <th class="col-quantity px-2 py-2 text-right text-xs font-medium">Qtà <span class="text-red-500">*</span></th>
                            <th class="col-price px-2 py-2 text-right text-xs font-medium">Prezzo Unit.</th>
                            <th class="col-um px-2 py-2 text-center text-xs font-medium">UM</th>
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
                            <td class="col-code px-2 py-1">
                                <input type="text" wire:model.live="rows.{{ $index }}.code" class="w-full px-1 py-1 text-sm border rounded-md">
                            </td>
                            <td class="col-description px-2 py-1">
                                <input type="text" wire:model.live="rows.{{ $index }}.description"
                                    class="w-full px-1 py-1 text-sm border rounded-md @error('rows.' . $index . '.description') field-error @enderror"
                                    required>
                            </td>
                            <td class="col-quantity px-2 py-1">
                                <input type="number" step="0.001" wire:model.live="rows.{{ $index }}.quantity"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right @error('rows.' . $index . '.quantity') field-error @enderror"
                                    required>
                            </td>
                            <td class="col-price px-2 py-1">
                                <input type="number" step="0.0001" wire:model.live="rows.{{ $index }}.unit_price"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right @error('rows.' . $index . '.unit_price') field-error @enderror"
                                    required>
                            </td>
                            <td class="col-um px-2 py-1">
                                <select wire:model.live="rows.{{ $index }}.unit_measure" 
                                        class="w-full px-1 py-1 text-sm border rounded-md text-center">
                                    @foreach($unitMeasureList as $um)
                                        <option value="{{ $um['codice'] }}">
                                            {{ $um['nome'] }} ({{ $um['codice'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="col-discount px-2 py-1">
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.discount_percentage" class="w-full px-1 py-1 text-sm border rounded-md text-right" placeholder="0">
                            </td>
                            <td class="col-vat px-2 py-1">
                                <select wire:model.live="rows.{{ $index }}.vat_rate"
                                    class="w-full px-1 py-1 text-sm border rounded-md">
                                    <option value="0">Seleziona IVA</option>
                                    @foreach($vatRatesList as $vat)
                                        <option value="{{ $vat['rate'] }}">  {{-- Qui 'rate' è già decimale (0.22) --}}
                                            {{ $vat['description'] }}
                                            @if($vat['sdi_nature'])
                                                ({{ $vat['sdi_nature'] }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="col-taxable px-2 py-1">
                                <input type="text" readonly
                                    value="{{ number_format($row['taxable_amount'] ?? 0, 2, ',', '.') }}"
                                    class="w-full px-1 py-1 text-sm border rounded-md text-right bg-gray-100 font-semibold">
                            </td>

                            <!-- Campo Autocomplete Centro Di Costo -->
                            <td class="col-cost-center px-2 py-1">
                                <div class="w-full relative" x-data="{ open: false }" x-on:click.away="open = false">
                                    <i class="fas fa-building absolute left-2 top-2 text-gray-400 text-xs z-10"></i>
                                    <input type="text"
                                        id="cost_center_input_{{ $index }}"
                                        wire:model.live.debounce.300ms="costCenterSearch.{{ $index }}"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Cerca centro..."
                                        class="w-full pl-7 pr-6 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    
                                    <div x-show="open && @entangle('showCostCenterDropdown.' . $index)" 
                                        class="relative w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        style="min-width: 200px;">
                                        @if(isset($costCenterResults[$index]) && count($costCenterResults[$index]) > 0)
                                            @foreach($costCenterResults[$index] as $cc)
                                                <div
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('cost_center_input_{{ $index }}').value = '{{ addslashes($cc['name']) }}';
                                                        @this.set('costCenterSearch.{{ $index }}', '{{ addslashes($cc['name']) }}');
                                                        @this.set('rows.{{ $index }}.id_cost_center', '{{ $cc['id'] }}');
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
                                </div>
                            </td>

                            <!-- Campo Autocomplete Mezzi -->
                            <td class="col-vehicle px-2 py-1">
                                <div class="w-full relative" x-data="{ open: false }" x-on:click.away="open = false">
                                    <i class="fas fa-truck absolute left-2 top-2 text-gray-400 text-xs z-10"></i>
                                    <input type="text"
                                        id="vehicle_input_{{ $index }}"
                                        wire:model.live.debounce.300ms="vehicleSearch.{{ $index }}"
                                        x-on:focus="open = true"
                                        x-on:input="open = true"
                                        placeholder="Cerca mezzo..."
                                        class="w-full pl-7 pr-6 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                                        autocomplete="off">
                                    
                                    <div x-show="open && @entangle('showVehicleDropdown.' . $index)" 
                                        class="relative w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        style="min-width: 200px;">
                                        @if(isset($vehicleResults[$index]) && count($vehicleResults[$index]) > 0)
                                            @foreach($vehicleResults[$index] as $vehicle)
                                                <div
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('vehicle_input_{{ $index }}').value = '{{ addslashes($vehicle['name']) }}';
                                                        @this.set('vehicleSearch.{{ $index }}', '{{ addslashes($vehicle['name']) }}');
                                                        @this.set('rows.{{ $index }}.id_vehicle', '{{ $vehicle['id'] }}');
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
                                </div>
                            </td>

                            <td class="col-actions px-2 py-1 text-center">
                                @if($index > 0)
                                    <button type="button" wire:click="removeRow({{ $index }})" class="text-red-500 hover:text-red-700 transition-colors">
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
                <button type="button" wire:click="addPayment" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-1 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i> Aggiungi scadenza
                </button>
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
                                <th class="px-3 py-2 text-center text-xs font-medium w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $index => $payment)
                            <tr class="border-b hover:bg-gray-50" wire:key="payment-{{ $index }}">
                                <td class="px-3 py-2">
                                    <input type="date" 
                                        wire:model.live="payments.{{ $index }}.due_date" 
                                        class="w-full px-2 py-1 text-sm border rounded-md @error('payments.' . $index . '.due_date') field-error @enderror">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" 
                                        wire:model.live="payments.{{ $index }}.amount" 
                                        class="w-full px-2 py-1 text-sm border rounded-md text-right @error('payments.' . $index . '.amount') field-error @enderror">
                                </td>
                                <td class="px-3 py-2">
                                    <select wire:model.live="payments.{{ $index }}.payment_method" 
                                        class="w-full px-2 py-1 text-sm border rounded-md">
                                        <option value="">Seleziona modalità di pagamento</option>
                                        @foreach($paymentMethods as $code => $label)
                                            <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" 
                                        wire:model.live="payments.{{ $index }}.iban" 
                                        placeholder="IT00 XXXX XXXX XXXX XXXX XXXX XXX"
                                        class="w-full px-2 py-1 text-sm border rounded-md">
                                    @if(isset($payments[$index]['bank_name']) && $payments[$index]['bank_name'])
                                        <div class="text-xs text-gray-500 mt-1">{{ $payments[$index]['bank_name'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" 
                                        wire:click="removePayment({{ $index }})" 
                                        class="text-red-500 hover:text-red-700 transition-colors"
                                        title="Rimuovi scadenza">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-3 py-2 text-right font-bold" colspan="1">Totale Scadenze:</td>
                                <td class="px-3 py-2 text-right font-bold" colspan="1">
                                    € {{ number_format($total_payments_amount, 2, ',', '.') }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                            @if(($total_payments_amount != $importo_totale) && $importo_totale > 0)
                            <tr>
                                <td class="px-3 py-2 text-right text-orange-600" colspan="1">⚠️ Differenza:</td>
                                <td class="px-3 py-2 text-right text-orange-600 font-bold" colspan="1">
                                    € {{ number_format($importo_totale - $total_payments_amount, 2, ',', '.') }}
                                </td>
                                <td colspan="3" class="text-xs text-orange-500">L'importo totale delle scadenze non corrisponde al totale fattura</td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-4 bg-gray-50 rounded-lg border border-dashed">
                    <i class="fas fa-calendar-alt mr-2"></i> Nessuna scadenza inserita
                    <button type="button" wire:click="addPayment" class="ml-3 text-purple-600 hover:text-purple-700 underline">
                        Aggiungi scadenza
                    </button>
                </div>
            @endif
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-sent.index') }}" class="px-4 py-2 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                Annulla
            </a>
            <button type="submit" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Salva Fattura
            </button>
        </div>
    </form>
    
    <!-- MODALE CREAZIONE NUOVO CLIENTE -->
    @if($showCustomerModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeCustomerModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false; $wire.closeCustomerModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Nuovo Cliente</h3>
                        <button type="button" wire:click="closeCustomerModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale / Nome <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newCustomerName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('newCustomerName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                                <input type="text" wire:model="newCustomerPiva" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                                <input type="text" wire:model="newCustomerCf" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" wire:model="newCustomerEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                                <input type="text" wire:model="newCustomerPhone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="newCustomerAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CAP</label>
                                <input type="text" wire:model="newCustomerCap" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                                <input type="text" wire:model="newCustomerCity" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                                <input type="text" wire:model="newCustomerProvince" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" wire:click="closeCustomerModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Annulla
                    </button>
                    <button type="button" wire:click="createCustomer" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition-colors">
                        <i class="fas fa-save mr-2"></i> Crea Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>