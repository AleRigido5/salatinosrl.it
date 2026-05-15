<div>
    <style>
        /* Larghezze fisse per le colonne */
        .col-code { width: 100px; min-width: 100px; }
        .col-description { width: 350px; min-width: 350px; }
        .col-quantity { width: 100px; min-width: 100px; }
        .col-price { width: 100px; min-width: 100px; }
        .col-total-price { width: 100px; min-width: 100px; } /* Nuova colonna prezzo totale */
        .col-um { width: 80px; min-width: 80px; }
        .col-discount { width: 80px; min-width: 80px; }
        .col-vat { width: 80px; min-width: 80px; }
        .col-cost-center { width: 200px; min-width: 200px; }
        .col-actions { width: 50px; min-width: 50px; }
        
        /* Per schermi molto grandi (TV, monitor 4K) */
        @media (min-width: 1920px) {
            .col-description { width: 450px; min-width: 450px; }
            .col-cost-center { width: 250px; min-width: 250px; }
        }
        
        /* Per schermi normali */
        @media (max-width: 1280px) {
            .col-description { width: 300px; min-width: 300px; }
            .col-cost-center { width: 180px; min-width: 180px; }
        }
        
        /* Per tablet */
        @media (max-width: 1024px) {
            .col-description { width: 250px; min-width: 250px; }
            .col-cost-center { width: 150px; min-width: 150px; }
        }
        
        /* I campi input occupano tutta la larghezza della cella */
        .invoice-table input,
        .invoice-table select {
            width: 100%;
        }
        
        /* Stile per campi obbligatori */
        .required-field {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .field-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
    </style>
    
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle text-lime-500 mr-2"></i> Nuova Fattura di Acquisto
        </h1>
        <div class="relative group">
            <a href="{{ route('admin.invoices-received.index') }}" 
               class="bg-gray-600 hover:bg-gray-900 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6">
        <!-- RIGA 1: Proprietà + Tipo Doc + N. Fattura + Totale -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                <select wire:model="id_ownership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                    <option value="">Seleziona proprietà</option>
                    @foreach($ownerships as $o)
                        <option value="{{ $o->id_proprieta }}">{{ $o->RagAbbrev }}</option>
                    @endforeach
                </select>
                @error('id_ownership') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento <span class="text-red-500">*</span></label>
                <select wire:model="type_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                    <option value="">Seleziona tipo</option>
                    @foreach($typeDocuments as $code => $label)
                        <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                    @endforeach
                </select>
                @error('type_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N. Fattura <span class="text-red-500">*</span></label>
                <input type="text" wire:model="n_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                @error('n_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Totale Fattura (€)</label>
                <input type="text" readonly value="{{ number_format($importo_totale, 2, ',', '.') }}" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700 font-bold">
            </div>
        </div>
        
        <!-- RIGA 2: Fornitore + Data Fattura -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <div class="flex-1 relative" x-data="{ open: false }" x-on:click.away="open = false">
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            <input type="text"
                                id="supplier_input"
                                wire:model.live.debounce.300ms="supplierSearch"
                                x-on:focus="open = true"
                                x-on:input="open = true; @this.set('supplierSearch', $event.target.value)"
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
                            @if($supplierResults && count($supplierResults) > 0)
                                @foreach($supplierResults as $item)
                                    <div
                                        x-on:click="
                                            open = false;
                                            document.getElementById('supplier_input').value = '{{ addslashes($item['name']) }}';
                                            @this.set('supplierSearch', '{{ addslashes($item['name']) }}');
                                            @this.set('selectedSupplierId', '{{ $item['id'] }}');
                                            @this.set('selectedSupplierName', '{{ addslashes($item['name']) }}');
                                            @this.set('showSupplierDropdown', false);
                                        "
                                        class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0">
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
                    
                    <button type="button" wire:click="openSupplierModal" class="px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors" title="Nuovo Fornitore">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @if($selectedSupplierId && $selectedSupplierName)
                    <div class="mt-1 text-xs text-green-600">
                        <i class="fas fa-check-circle"></i> Fornitore selezionato: {{ $selectedSupplierName }}
                    </div>
                @endif
                @error('selectedSupplierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura <span class="text-red-500">*</span></label>
                <input type="date" wire:model="data_invoice" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                @error('data_invoice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        
        <!-- Causale / Note -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Causale / Note</label>
            <textarea wire:model="causale" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500" placeholder="Note aggiuntive..."></textarea>
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
                            <th class="col-code px-3 py-2 text-left text-xs font-medium">Codice</th>
                            <th class="col-description px-3 py-2 text-left text-xs font-medium">Descrizione <span class="text-red-500">*</span></th>
                            <th class="col-quantity px-3 py-2 text-right text-xs font-medium">Qtà <span class="text-red-500">*</span></th>
                            <th class="col-price px-3 py-2 text-right text-xs font-medium">Prezzo Unit. <span class="text-red-500">*</span></th>
                            <th class="col-total-price px-3 py-2 text-right text-xs font-medium">Prezzo Totale <span class="text-red-500">*</span></th>
                            <th class="col-um px-3 py-2 text-center text-xs font-medium">UM</th>
                            <th class="col-discount px-3 py-2 text-right text-xs font-medium">Sconto%</th>
                            <th class="col-vat px-3 py-2 text-right text-xs font-medium">IVA%</th>
                            <th class="col-cost-center px-3 py-2 text-left text-xs font-medium">Centro Costo</th>
                            <th class="col-actions px-3 py-2 text-center text-xs font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                        <tr class="border-b hover:bg-gray-50" wire:key="row-{{ $index }}">
                            <td class="col-code px-3 py-2">
                                <input type="text" wire:model.live="rows.{{ $index }}.code" class="w-full px-2 py-1 text-sm border rounded-md">
                            </td>
                            <td class="col-description px-3 py-2">
                                <input type="text" wire:model.live="rows.{{ $index }}.description" 
                                    class="w-full px-2 py-1 text-sm border rounded-md @error('rows.' . $index . '.description') field-error @enderror"
                                    required>
                                @error('rows.' . $index . '.description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </td>
                            <td class="col-quantity px-3 py-2">
                                <input type="number" step="0.001" wire:model.live="rows.{{ $index }}.quantity" 
                                    class="w-full px-2 py-1 text-sm border rounded-md text-right @error('rows.' . $index . '.quantity') field-error @enderror"
                                    required>
                            </td>
                            <td class="col-price px-3 py-2">
                                <input type="number" step="0.0001" wire:model.live="rows.{{ $index }}.unit_price" 
                                    class="w-full px-2 py-1 text-sm border rounded-md text-right @error('rows.' . $index . '.unit_price') field-error @enderror"
                                    required>
                             </td>
                            <td class="col-total-price px-3 py-2">
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.total_price" 
                                    class="w-full px-2 py-1 text-sm border rounded-md text-right @error('rows.' . $index . '.total_price') field-error @enderror"
                                    required>
                             </td>
                            <td class="col-um px-3 py-2">
                                <input type="text" wire:model.live="rows.{{ $index }}.unit_measure" class="w-full px-2 py-1 text-sm border rounded-md text-center" placeholder="PZ">
                             </td>
                            <td class="col-discount px-3 py-2">
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.discount_percentage" class="w-full px-2 py-1 text-sm border rounded-md text-right" placeholder="0">
                             </td>
                            <td class="col-vat px-3 py-2">
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.vat_rate" class="w-full px-2 py-1 text-sm border rounded-md text-right" placeholder="22">
                             </td>
                            <td class="col-cost-center px-3 py-2">
                                <div class="relative w-full" x-data="{ open: false }" x-on:click.away="open = false">
                                    <div class="relative">
                                        <input type="text"
                                            id="cost_center_input_{{ $index }}"
                                            wire:model.live.debounce.300ms="costCenterSearch.{{ $index }}"
                                            x-on:focus="open = true"
                                            x-on:input="open = true; @this.set('costCenterSearch.{{ $index }}', $event.target.value)"
                                            placeholder="Cerca centro di costo..."
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500 text-left"
                                            autocomplete="off">
                                    </div>

                                    <!-- Dropdown più largo che esce fuori dalla tabella -->
                                    <div x-show="open && @entangle('showCostCenterDropdown.' . $index)"
                                        class="fixed z-[9999] mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        style="min-width: 300px; width: auto;"
                                        x-data="{ 
                                            position() {
                                                const input = document.getElementById('cost_center_input_{{ $index }}');
                                                if (input) {
                                                    const rect = input.getBoundingClientRect();
                                                    this.$el.style.top = (rect.bottom + window.scrollY) + 'px';
                                                    this.$el.style.left = rect.left + 'px';
                                                    this.$el.style.width = Math.max(rect.width, 300) + 'px';
                                                }
                                            }
                                        }"
                                        x-init="position(); $watch('open', () => position())"
                                        x-on:scroll.window.debounce="position()"
                                        x-on:resize.window.debounce="position()">
                                        @if(isset($costCenterResults[$index]) && count($costCenterResults[$index]) > 0)
                                            @foreach($costCenterResults[$index] as $cc)
                                                <div
                                                    x-on:click="
                                                        open = false;
                                                        document.getElementById('cost_center_input_{{ $index }}').value = '{{ addslashes($cc['name']) }}';
                                                        @this.set('costCenterSearch.{{ $index }}', '{{ addslashes($cc['name']) }}');
                                                        @this.set('rows.{{ $index }}.id_cost_center', '{{ $cc['id'] }}');
                                                        @this.set('rows.{{ $index }}.cost_center_name', '{{ addslashes($cc['name']) }}');
                                                        @this.set('showCostCenterDropdown.{{ $index }}', false);
                                                        @this.call('calculateTotal');
                                                    "
                                                    class="px-3 py-2 hover:bg-lime-50 cursor-pointer text-sm border-b border-gray-100 last:border-0 text-left">
                                                    <div class="font-medium text-gray-800">{{ $cc['name'] }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="px-3 py-2 text-sm text-gray-500 text-center">Nessun risultato trovato</div>
                                        @endif
                                    </div>
                                </div>
                             </td>
                            <td class="col-actions px-3 py-2 text-center">
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
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                Annulla
            </a>
            <button type="submit" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-4 py-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-save mr-2"></i> Salva Fattura
            </button>
        </div>
    </form>
    
    <!-- MODALE CREAZIONE NUOVO FORNITORE -->
    @if($showSupplierModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: true }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeSupplierModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false; $wire.closeSupplierModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Nuovo Fornitore</h3>
                        <button type="button" wire:click="closeSupplierModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale / Nome <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newSupplierName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            @error('newSupplierName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partita IVA</label>
                                <input type="text" wire:model="newSupplierPiva" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
                                <input type="text" wire:model="newSupplierCf" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" wire:model="newSupplierEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
                                <input type="text" wire:model="newSupplierPhone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                            <input type="text" wire:model="newSupplierAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500">
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
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button type="button" wire:click="closeSupplierModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Annulla
                    </button>
                    <button type="button" wire:click="createSupplier" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 transition-colors">
                        <i class="fas fa-save mr-2"></i> Crea Fornitore
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>