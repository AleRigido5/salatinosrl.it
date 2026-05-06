<div>
    <form wire:submit.prevent="save" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                <select wire:model="id_ownership" class="w-full px-3 py-2 border rounded-md focus:ring-lime-500" required>
                    <option value="">Seleziona...</option>
                    @foreach($ownerships as $ownership)
                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'Proprietà ' . $ownership->id_proprieta }}</option>
                    @endforeach
                </select>
                @error('id_ownership') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore <span class="text-red-500">*</span></label>
                <select wire:model="id_entities" class="w-full px-3 py-2 border rounded-md focus:ring-lime-500" required>
                    <option value="">Seleziona...</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id_cliente }}">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</option>
                    @endforeach
                </select>
                @error('id_entities') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento <span class="text-red-500">*</span></label>
                <select wire:model="type_invoice" class="w-full px-3 py-2 border rounded-md">
                    @foreach($types as $code => $label)
                        <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numero Fattura <span class="text-red-500">*</span></label>
                <input type="text" wire:model="n_invoice" class="w-full px-3 py-2 border rounded-md" placeholder="es. 001/2024">
                @error('n_invoice') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura <span class="text-red-500">*</span></label>
                <input type="date" wire:model="data_invoice" class="w-full px-3 py-2 border rounded-md">
                @error('data_invoice') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divisa</label>
                <select wire:model="divisa" class="w-full px-3 py-2 border rounded-md">
                    @foreach($currencies as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                <select wire:model="status" class="w-full px-3 py-2 border rounded-md">
                    @foreach($statuses as $value => $status)
                        <option value="{{ $value }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SDI ID</label>
                <input type="text" wire:model="sdi_id" class="w-full px-3 py-2 border rounded-md" placeholder="Codice SDI">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Causale</label>
                <textarea wire:model="causale" rows="2" class="w-full px-3 py-2 border rounded-md" placeholder="Causale della fattura..."></textarea>
            </div>
        </div>
        
        <!-- Righe Fattura -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">Righe Fattura</h3>
                <button type="button" wire:click="addRow" class="px-3 py-1 bg-lime-500 text-white rounded-md hover:bg-lime-600 text-sm">
                    <i class="fas fa-plus"></i> Aggiungi riga
                </button>
            </div>
            
            <div class="space-y-3">
                @foreach($rows as $index => $row)
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Centro di Costo <span class="text-red-500">*</span></label>
                            <select wire:model="rows.{{ $index }}.id_cost_center" class="w-full px-2 py-1.5 text-sm border rounded-md">
                                <option value="">Seleziona...</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->Nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Descrizione <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="rows.{{ $index }}.description" class="w-full px-2 py-1.5 text-sm border rounded-md" placeholder="Descrizione">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Q.tà <span class="text-red-500">*</span></label>
                            <input type="number" step="0.001" wire:model="rows.{{ $index }}.quantity" class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Prezzo €</label>
                            <input type="number" step="0.0001" wire:model="rows.{{ $index }}.unit_price" class="w-full px-2 py-1.5 text-sm border rounded-md">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sconto %</label>
                            <input type="number" step="0.01" wire:model="rows.{{ $index }}.discount_percentage" class="w-full px-2 py-1.5 text-sm border rounded-md" value="0">
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">IVA</label>
                            <select wire:model="rows.{{ $index }}.vat_rate_id" class="w-full px-2 py-1.5 text-sm border rounded-md">
                                <option value="">Seleziona...</option>
                                @foreach($vatRates as $vat)
                                    <option value="{{ $vat->id }}">{{ $vat->rate }}%</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Totale</label>
                            <input type="text" readonly value="€ {{ number_format($row['quantity'] * $row['unit_price'] * (1 - ($row['discount_percentage'] ?? 0) / 100), 2) }}" class="w-full px-2 py-1.5 text-sm bg-gray-100 border rounded-md">
                        </div>
                        
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" wire:click="removeRow({{ $index }})" class="text-red-500 hover:text-red-700 px-2 py-1.5">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            @error('rows') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>
        
        <!-- Totale -->
        <div class="mt-6 pt-4 border-t">
            <div class="flex justify-end">
                <div class="bg-gray-100 p-4 rounded-lg text-right">
                    <p class="text-sm text-gray-600">Totale Fattura</p>
                    <p class="text-2xl font-bold text-green-600">€ {{ number_format($importo_totale, 2) }}</p>
                </div>
            </div>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.invoices-received.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                Annulla
            </a>
            <button type="submit" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600">
                <i class="fas fa-save mr-2"></i> {{ $mode === 'create' ? 'Crea' : 'Aggiorna' }}
            </button>
        </div>
    </form>
</div>