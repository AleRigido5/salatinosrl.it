<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Importa Fattura Elettronica</h1>
        <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">← Torna all'elenco</a>
    </div>

    <!-- Alert container -->
    <div x-data="{ alerts: [] }" 
         x-on:alert.window="alerts.push($event.detail); setTimeout(() => alerts.shift(), 5000)"
         class="fixed top-5 right-5 z-50 space-y-2">
        <template x-for="(alert, idx) in alerts" :key="idx">
            <div x-show="true" 
                 x-transition.duration.300ms
                 class="px-4 py-3 rounded shadow-lg min-w-[300px]"
                 :class="{
                     'bg-green-100 border-l-4 border-green-500 text-green-700': alert.type === 'success',
                     'bg-red-100 border-l-4 border-red-500 text-red-700': alert.type === 'error',
                     'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700': alert.type === 'warning',
                     'bg-blue-100 border-l-4 border-blue-500 text-blue-700': alert.type === 'info'
                 }">
                <div class="flex items-center justify-between">
                    <span x-text="alert.message"></span>
                    <button @click="alerts = alerts.filter(a => a !== alert)" class="ml-4 text-gray-500 hover:text-gray-700">×</button>
                </div>
            </div>
        </template>
    </div>

    <div class="bg-white rounded shadow p-6">
        <!-- Upload XML -->
        <div class="mb-6 p-4 border rounded bg-gray-50">
            <h2 class="text-lg font-bold mb-4">1. Carica file XML</h2>
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">File XML Fattura Elettronica</label>
                    <input type="file" wire:model="xml_file" accept=".xml" class="w-full border rounded px-3 py-2">
                    @error('xml_file') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <button type="button" wire:click="uploadXml" wire:loading.attr="disabled" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    <span wire:loading.remove>Analizza XML</span>
                    <span wire:loading>Analisi...</span>
                </button>
            </div>
        </div>

        @if($xml_parsed)
            <form wire:submit="save" class="space-y-6">
                <h2 class="text-lg font-bold mb-4">2. Dati Fattura</h2>
                
                <!-- Alert per fornitore non trovato -->
                @if($supplier_not_found && !$supplier_found)
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong>⚠️ Fornitore non trovato nel database</strong>
                                <p class="text-sm mt-1">
                                    P.IVA: {{ $supplier_from_xml['partita_iva'] ?? '-' }}<br>
                                    Denominazione: {{ $supplier_from_xml['denominazione'] ?? '-' }}
                                </p>
                            </div>
                            <button type="button" wire:click="createSupplierAutomatically" 
                                    class="px-3 py-1 bg-green-600 text-white rounded text-sm">
                                Crea automaticamente
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Dati fornitore trovato -->
                @if($supplier_found)
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                        <strong>✅ Fornitore trovato nel database</strong>
                        <p class="text-sm mt-1">
                            ID: {{ $id_entities }} | 
                            Denominazione: {{ collect($all_entities)->firstWhere('id_cliente', $id_entities)['ragione_sociale'] ?? '-' }}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Fornitore - Solo lettura se trovato automaticamente -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Fornitore</label>
                        <input type="text" 
                               value="{{ $supplier_from_xml['denominazione'] ?? '' }} (P.IVA: {{ $supplier_from_xml['partita_iva'] ?? '-' }})"
                               class="w-full border rounded px-3 py-2 bg-gray-50"
                               readonly
                               disabled>
                        <input type="hidden" wire:model="id_entities">
                    </div>

                    <!-- Proprietà - Autocomplete -->
                    <div class="relative">
                        <label class="block text-sm font-medium mb-1">Committente</label>
                        <input type="text" 
                               wire:model.live.debounce.300ms="search_ownership"
                               wire:keyup.escape="ownerships = []"
                               class="w-full border rounded px-3 py-2"
                               placeholder="Cerca proprietà...">
                        @if(!empty($ownerships))
                            <div class="absolute z-50 bg-white border rounded shadow-lg w-full max-h-48 overflow-y-auto">
                                @foreach($ownerships as $ownership)
                                    <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" wire:click="selectOwnership({{ $ownership['id'] }})">
                                        {{ $ownership['name'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="hidden" wire:model="id_ownership">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Numero Fattura *</label>
                        <input type="text" wire:model="n_invoice" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Data Fattura *</label>
                        <input type="date" wire:model="data_invoice" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Tipo Documento</label>
                        <select wire:model="type_invoice" class="w-full border rounded px-3 py-2">
                            @foreach($tipoDocumento as $key => $label)
                                <option value="{{ $key }}">{{ $key }} - {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Stato</label>
                        <select wire:model="status" class="w-full border rounded px-3 py-2">
                            @foreach($invoiceStatus as $key => $status)
                                <option value="{{ $key }}">{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Divisa</label>
                        <select wire:model="divisa" class="w-full border rounded px-3 py-2">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">SDI ID</label>
                        <input type="text" wire:model="sdi_id" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Causale / Note</label>
                    <textarea wire:model="causale" rows="2" class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <!-- Righe Fattura -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold">Righe Fattura</h3>
                        <button type="button" wire:click="addRow" class="px-3 py-1 bg-green-600 text-white rounded text-sm">
                            + Aggiungi Riga
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Centro Costo</th>
                                    <th class="px-3 py-2 text-left">Descrizione</th>
                                    <th class="px-3 py-2 text-right">Qtà</th>
                                    <th class="px-3 py-2 text-right">Prezzo</th>
                                    <th class="px-3 py-2 text-right">Sconto %</th>
                                    <th class="px-3 py-2 text-right">Totale</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $index => $row)
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="relative">
                                            <input type="text" 
                                                   wire:model="search_cost_center"
                                                   class="w-full border rounded px-2 py-1 text-sm"
                                                   placeholder="Cerca centro costo...">
                                            @if(!empty($costCenters))
                                                <div class="absolute z-50 bg-white border rounded shadow-lg w-full max-h-48 overflow-y-auto">
                                                    @foreach($costCenters as $cc)
                                                        <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm" 
                                                             wire:click="selectCostCenter({{ $cc['id'] }}, {{ $index }})">
                                                            {{ $cc['name'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <input type="hidden" wire:model="rows.{{ $index }}.id_cost_center">
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" wire:model="rows.{{ $index }}.description" class="w-full border rounded px-2 py-1 text-sm" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.000001" wire:model.live="rows.{{ $index }}.quantity" class="border rounded px-2 py-1 text-sm w-24 text-right">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.000001" wire:model.live="rows.{{ $index }}.unit_price" class="border rounded px-2 py-1 text-sm w-28 text-right">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.discount_percentage" class="border rounded px-2 py-1 text-sm w-20 text-right">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format(($row['quantity'] ?? 0) * ($row['unit_price'] ?? 0) * (1 - (($row['discount_percentage'] ?? 0) / 100)), 2, ',', '.') }} €
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" wire:click="removeRow({{ $index }})" class="text-red-600">🗑</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-4 text-center text-gray-500">
                                        Nessuna riga. <button type="button" wire:click="addRow" class="text-indigo-600">Aggiungi riga</button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="5" class="px-3 py-2 text-right font-bold">TOTALE DOCUMENTO</td>
                                    <td class="px-3 py-2 text-right font-bold text-lg">
                                        {{ number_format($importo_totale, 2, ',', '.') }} €
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-300 rounded">Annulla</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Salva Fattura
                    </button>
                </div>
            </form>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('alert', (data) => {
                // Alert già gestito da Alpine
                console.log('Alert:', data);
            });
        });
    </script>
    @endpush
</div>