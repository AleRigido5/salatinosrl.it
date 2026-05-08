<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Fatture di Acquisto</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices-received.xml-import') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Importa XML
            </a>
            <a href="{{ route('admin.invoices-received.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Fattura
            </a>
        </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cerca..." class="border rounded-lg px-3 py-2">
            <select wire:model.live="status" class="border rounded-lg px-3 py-2">
                <option value="">Tutti gli stati</option>
                @foreach($statuses as $s)
                    <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                @endforeach
            </select>
            <select wire:model.live="id_ownership" class="border rounded-lg px-3 py-2">
                <option value="">Tutte le proprietà</option>
                @foreach($ownerships as $o)
                    <option value="{{ $o->id }}">{{ $o->label }}</option>
                @endforeach
            </select>
            <select wire:model.live="id_entities" class="border rounded-lg px-3 py-2">
                <option value="">Tutti i fornitori</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-between items-center mt-3">
            <button wire:click="resetFilters" class="text-sm text-indigo-600 hover:text-indigo-800">
                Reset filtri
            </button>
            <div class="text-sm text-gray-500">
                Totale: <span class="font-semibold">{{ number_format($totalImporto, 2, ',', '.') }} €</span>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('n_invoice')">
                            <div class="flex items-center gap-1">
                                Numero
                                @if($sortField === 'n_invoice')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_invoice')">
                            <div class="flex items-center gap-1">
                                Data
                                @if($sortField === 'data_invoice')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proprietà</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('importo_totale')">
                            <div class="flex items-center justify-end gap-1">
                                Totale
                                @if($sortField === 'importo_totale')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $invoice->n_invoice }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invoice->data_invoice->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invoice->supplier_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $invoice->ownership_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                            {{ number_format($invoice->importo_totale, 2, ',', '.') }} €
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status_badge_class }}">
                                {{ $invoice->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Icona Vedi -->
                                <button wire:click="showDetails({{ $invoice->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900 transition" 
                                        title="Visualizza dettagli">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <!-- Icona Modifica -->
                                <a href="{{ route('admin.invoices-received.edit', $invoice) }}" 
                                   class="text-blue-600 hover:text-blue-900 transition" 
                                   title="Modifica fattura">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <!-- Icona Elimina -->
                                <button wire:click="deleteInvoice({{ $invoice->id }})" 
                                        onclick="return confirm('Sei sicuro di voler eliminare questa fattura?')"
                                        class="text-red-600 hover:text-red-900 transition" 
                                        title="Elimina fattura">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Nessuna fattura trovata
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginazione -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $invoices->links() }}
        </div>
    </div>

    <!-- MODAL DETTAGLI FATTURA -->
    @if($showModal && $selectedInvoice)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Centra il modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal content -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4">
                    <!-- Header -->
                    <div class="flex justify-between items-center pb-3 border-b">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Dettaglio Fattura
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Fattura n. {{ $selectedInvoice->n_invoice }} del {{ $selectedInvoice->data_invoice->format('d/m/Y') }}
                            </p>
                        </div>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="mt-4 space-y-4">
                        <!-- Badge stato -->
                        <div class="flex justify-end">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedInvoice->status_badge_class }}">
                                {{ $selectedInvoice->status_label }}
                            </span>
                        </div>

                        <!-- Dati fattura -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-gray-500 uppercase">Fornitore</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->supplier_name }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-gray-500 uppercase">Proprietà</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->ownership_name }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-gray-500 uppercase">Tipo Documento</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->type_invoice_label }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <label class="block text-xs font-medium text-gray-500 uppercase">Divisa</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->divisa }}</p>
                            </div>
                            @if($selectedInvoice->sdi_id)
                            <div class="bg-gray-50 p-3 rounded-lg col-span-2">
                                <label class="block text-xs font-medium text-gray-500 uppercase">SDI ID</label>
                                <p class="text-sm font-mono text-gray-900 mt-1 break-all">{{ $selectedInvoice->sdi_id }}</p>
                            </div>
                            @endif
                            @if($selectedInvoice->causale)
                            <div class="bg-gray-50 p-3 rounded-lg col-span-2">
                                <label class="block text-xs font-medium text-gray-500 uppercase">Causale / Note</label>
                                <p class="text-sm text-gray-700 mt-1">{{ $selectedInvoice->causale }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Righe fattura -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Righe Fattura</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Descrizione</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Quantità</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Prezzo Unit.</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Sconto</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Totale</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($selectedInvoice->rows as $row)
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $row->description }}</td>
                                            <td class="px-3 py-2 text-sm text-right">{{ number_format($row->quantity, 3, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-sm text-right">{{ number_format($row->unit_price, 4, ',', '.') }} €</td>
                                            <td class="px-3 py-2 text-sm text-right">{{ $row->discount_percentage > 0 ? number_format($row->discount_percentage, 2, ',', '.') . '%' : '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($row->total, 2, ',', '.') }} €</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-3 py-2 text-right font-bold">TOTALE</td>
                                            <td class="px-3 py-2 text-right font-bold text-lg">{{ number_format($selectedInvoice->importo_totale, 2, ',', '.') }} €</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Info importazione -->
                        <div class="text-xs text-gray-400 border-t pt-3 mt-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div>Importata il: {{ $selectedInvoice->imported_at ? $selectedInvoice->imported_at->format('d/m/Y H:i:s') : '-' }}</div>
                                <div>File XML: {{ basename($selectedInvoice->xml_filename ?? '') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Chiudi
                    </button>
                    <a href="{{ route('admin.invoices-received.edit', $selectedInvoice) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Modifica fattura
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>