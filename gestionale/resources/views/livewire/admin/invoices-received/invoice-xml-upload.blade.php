<div>
    @if(!$hasData)
    <!-- Sezione upload XML -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-center">
            <i class="fas fa-file-upload text-5xl text-lime-500 mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Carica Fattura Elettronica XML</h2>
            <p class="text-gray-500 mb-6">Carica un file XML di fattura elettronica per importare automaticamente i dati</p>
            
            <form wire:submit.prevent="uploadXml" class="max-w-md mx-auto">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 mb-4">
                    <input type="file" wire:model="xmlFile" accept=".xml" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100">
                    @error('xmlFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <button type="submit" wire:loading.attr="disabled" class="bg-lime-500 hover:bg-lime-600 text-white px-6 py-2 rounded-lg transition">
                    <span wire:loading.remove><i class="fas fa-upload mr-2"></i> Carica XML</span>
                    <span wire:loading><i class="fas fa-spinner fa-spin mr-2"></i> Elaborazione...</span>
                </button>
            </form>
            
            <div class="mt-6 text-left text-sm text-gray-500">
                <p class="font-semibold mb-2">Formato XML supportato:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Fattura Elettronica PA (Formato standard)</li>
                    <li>Estensione .xml</li>
                    <li>Dimensione massima: 5MB</li>
                </ul>
            </div>
        </div>
    </div>
    @else
    
    <!-- Sezione riepilogo dati importati -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-file-invoice text-lime-500 mr-2"></i> Riepilogo Fattura
            </h2>
            <button type="button" wire:click="resetForm" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i> Annulla importazione
            </button>
        </div>
        
        <!-- Dati fattura -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Proprietà <span class="text-red-500">*</span></label>
                <select wire:model="invoiceData.id_ownership" class="w-full px-3 py-2 border rounded-md">
                    <option value="">Seleziona proprietà...</option>
                    @foreach($ownerships as $ownership)
                        <option value="{{ $ownership->id_proprieta }}">{{ $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'Proprietà ' . $ownership->id_proprieta }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore <span class="text-red-500">*</span></label>
                <select wire:model="invoiceData.id_entities" class="w-full px-3 py-2 border rounded-md">
                    <option value="">Seleziona fornitore...</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id_cliente }}">{{ $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome) }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Numero Fattura</label>
                <input type="text" wire:model="invoiceData.numero_fattura" readonly class="w-full px-3 py-2 bg-gray-100 border rounded-md">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fattura</label>
                <input type="date" wire:model="invoiceData.data_fattura" readonly class="w-full px-3 py-2 bg-gray-100 border rounded-md">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fornitore (dati XML)</label>
                <div class="text-sm text-gray-600 p-2 bg-white rounded border">
                    {{ $invoiceData['fornitore']['denominazione'] ?? '' }}
                    {{ $invoiceData['fornitore']['nome'] ?? '' }} {{ $invoiceData['fornitore']['cognome'] ?? '' }}
                    @if(!empty($invoiceData['fornitore']['partita_iva']))
                    <span class="text-gray-400"> - P.IVA: {{ $invoiceData['fornitore']['partita_iva'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Tabella righe fattura (solo IVA editabile) -->
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Righe Fattura</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrizione</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Q.tà</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prezzo Unit.</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sconto %</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aliquota IVA</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Totale</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rows as $index => $row)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row['description'] }}</td>
                        <td class="px-4 py-3 text-sm text-center">{{ number_format($row['quantity'], 3) }}</td>
                        <td class="px-4 py-3 text-sm text-right">€ {{ number_format($row['unit_price'], 4) }}</td>
                        <td class="px-4 py-3 text-sm text-center">{{ number_format($row['discount_percentage'], 2) }}%</td>
                        <td class="px-4 py-3 text-center">
                            <select wire:change="updateRowVat({{ $index }}, $event.target.value)" class="px-2 py-1 text-sm border rounded-md">
                                <option value="">Seleziona IVA</option>
                                @foreach($vatRates as $vat)
                                    <option value="{{ $vat->id }}" {{ $row['vat_rate_id'] == $vat->id ? 'selected' : '' }}>{{ $vat->rate }}%</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">€ {{ number_format($row['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-bold">Totale Fattura:</td>
                        <td class="px-4 py-3 text-right font-bold text-lg">€ {{ number_format($invoiceData['importo_totale'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Informazioni sui centri di costo -->
        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Nota:</strong> I centri di costo possono essere assegnati successivamente nella modifica della fattura.
            </p>
        </div>
        
        <!-- Bottoni -->
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" wire:click="resetForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                Annulla
            </button>
            <button type="button" wire:click="saveInvoice" class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600">
                <i class="fas fa-save mr-2"></i> Importa Fattura
            </button>
        </div>
    </div>
    @endif
</div>