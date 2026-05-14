<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-dolly mr-3 text-lime-600"></i>
            Fatture di Acquisto
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices-received.xml-import') }}" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-upload"></i> Importa XML
            </a>
            <div class="relative group">
                <button wire:click="openTrashModal" class="relative px-5 py-2.5 rounded-lg shadow-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                    <i class="fas fa-trash-alt"></i>
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-md" style="{{ $trashCount == 0 ? 'display: none;' : '' }}">
                        {{ $trashCount }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Card unica: Date Range + Filtri di Ricerca -->
    <div class="bg-white rounded-lg shadow p-4 mb-4 border border-gray-200">
        <!-- RIGA SUPERIORE: Date Range Filter -->
        @livewire('components.date-range-filter', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])
        
        <!-- Linea di separazione -->
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- RIGA INFERIORE: Autocomplete Proprietà + Autocomplete Fornitore + Select Stato -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Autocomplete Proprietà -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Proprietà</label>
                <div class="relative">
                    <i class="fas fa-building absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <input type="text"
                        id="ownership_input"
                        wire:model.live.debounce.300ms="ownershipSearch"
                        x-on:focus="open = true"
                        x-on:input="open = true; @this.set('ownershipSearch', $event.target.value)"
                        placeholder="Cerca proprietà..."
                        class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500"
                        autocomplete="off">
                    @if($selectedOwnershipId)
                        <button type="button"
                            wire:click="clearOwnership"
                            x-on:click="document.getElementById('ownership_input').value = ''"
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
                                    document.getElementById('ownership_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('ownershipSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedOwnershipId', '{{ $item->id }}');
                                    @this.set('selectedOwnershipName', '{{ addslashes($item->name) }}');
                                    @this.set('showOwnershipDropdown', false);
                                    @this.call('resetPage');
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

            <!-- Autocomplete Fornitore -->
            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Fornitore</label>
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
                    @if($supplierResults && $supplierResults->count() > 0)
                        @foreach($supplierResults as $item)
                            <div
                                x-on:click="
                                    open = false;
                                    document.getElementById('supplier_input').value = '{{ addslashes($item->name) }}';
                                    @this.set('supplierSearch', '{{ addslashes($item->name) }}');
                                    @this.set('selectedSupplierId', '{{ $item->id }}');
                                    @this.set('selectedSupplierName', '{{ addslashes($item->name) }}');
                                    @this.set('showSupplierDropdown', false);
                                    @this.call('resetPage');
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
            
            <!-- Select Stato -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Stato</label>
                <div class="relative">
                    <i class="fas fa-tag absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                    <select wire:model.live="status" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        <option value="">Tutti gli stati</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Active Filters Tags -->
        @if($selectedOwnershipId || $selectedSupplierId || $status || $type_invoice || $search || $dateFrom || $dateTo)
        <div class="mt-4 pt-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500">Filtri attivi:</span>
                @if($selectedOwnershipId && $selectedOwnershipName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-building mr-1 text-xs"></i> {{ $selectedOwnershipName }}
                    <button wire:click="clearOwnership" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
                @if($selectedSupplierId && $selectedSupplierName)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-user mr-1 text-xs"></i> {{ $selectedSupplierName }}
                    <button wire:click="clearSupplier" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
                @if($status)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-tag mr-1 text-xs"></i> {{ collect($statuses)->firstWhere('value', $status)['label'] ?? $status }}
                    <button wire:click="$set('status', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
                @if($type_invoice)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-file-alt mr-1 text-xs"></i> {{ $typeDocuments[$type_invoice] ?? $type_invoice }}
                    <button wire:click="$set('type_invoice', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
                @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-file-invoice mr-1 text-xs"></i> N. Fattura: "{{ $search }}"
                    <button wire:click="$set('search', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
                @if($dateFrom || $dateTo)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-lime-100 text-lime-800">
                    <i class="fas fa-calendar mr-1 text-xs"></i> {{ $dateFrom ?: '...' }} → {{ $dateTo ?: '...' }}
                    <button wire:click="$set('dateFrom', ''); $set('dateTo', '')" class="ml-1 hover:text-lime-900"><i class="fas fa-times text-xs"></i></button>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_invoice')">Data @if($sortField === 'data_invoice')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('n_invoice')">N. Fattura @if($sortField === 'n_invoice')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Fornitore</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Proprietà</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Tipo Doc.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 cursor-pointer hover:bg-gray-100" wire:click="sortBy('importo_totale')">Totale @if($sortField === 'importo_totale')<i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>@endif</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Stato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50" wire:key="invoice-{{ $invoice->id }}">
                        <td class="px-4 py-3 text-sm">{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ $invoice->n_invoice }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $invoice->ownership->RagAbbrev ?? $invoice->ownership_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $typeDocuments[$invoice->type_invoice] ?? $invoice->type_invoice }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium {{ $invoice->status_badge_class }}">{{ $invoice->status_label }}</span></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('admin.invoices-received.xml-view', $invoice->id) }}" target="_blank" class="text-purple-600 hover:text-purple-900" title="Visualizza XML"><i class="fa-solid fa-magnifying-glass"></i></a>
                                <button wire:click="showDetails({{ $invoice->id }})" class="text-blue-600 hover:text-blue-900" title="Dettagli"><i class="fa-regular fa-eye"></i></button>
                                <button wire:click="confirmDelete({{ $invoice->id }})" class="text-red-600 hover:text-red-900" title="Elimina"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-8 text-gray-500">Nessuna fattura trovata</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t">{{ $invoices->links() }}</div>
    </div>

    <!-- ==================== MODAL DETTAGLI FATTURA ==================== -->
    @if($showModal && $selectedInvoice)
    <div x-data="{ open: true }" x-show="open" x-init="$watch('open', value => { if (!value) $wire.closeModal() })" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Dettaglio Fattura</h3>
                            <p class="text-sm text-gray-500 mt-1">Fattura n. {{ $selectedInvoice->n_invoice }} del {{ $selectedInvoice->data_invoice->format('d/m/Y') }}</p>
                        </div>
                        <button x-on:click="open = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="flex justify-end mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedInvoice->status_badge_class }}">{{ $selectedInvoice->status_label }}</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg"><label class="block text-xs font-medium text-gray-500 uppercase">Fornitore</label><p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->supplier_name }}</p></div>
                        <div class="bg-gray-50 p-3 rounded-lg"><label class="block text-xs font-medium text-gray-500 uppercase">Proprietà</label><p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->ownership_name }}</p></div>
                        <div class="bg-gray-50 p-3 rounded-lg"><label class="block text-xs font-medium text-gray-500 uppercase">Tipo Documento</label><p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->type_invoice_label }}</p></div>
                        @if($selectedInvoice->sdi_id)<div class="bg-gray-50 p-3 rounded-lg"><label class="block text-xs font-medium text-gray-500 uppercase">SDI ID</label><p class="text-sm font-mono text-gray-900 mt-1 break-all">{{ $selectedInvoice->sdi_id }}</p></div>@endif
                        @if($selectedInvoice->causale)<div class="bg-gray-50 p-3 rounded-lg col-span-2"><label class="block text-xs font-medium text-gray-500 uppercase">Causale / Note</label><p class="text-sm text-gray-700 mt-1">{{ $selectedInvoice->causale }}</p></div>@endif
                    </div>
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2">Riferimenti Amministrativi</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg"><label class="block text-xs font-medium text-blue-600 uppercase"><i class="fas fa-user-plus mr-1"></i> Inserito da</label><p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->creator->name ?? 'Sistema' }}</p><p class="text-xs text-gray-500 mt-1">{{ $selectedInvoice->created_at ? $selectedInvoice->created_at->format('d/m/Y H:i:s') : '-' }}</p></div>
                            <div class="bg-green-50 p-3 rounded-lg"><label class="block text-xs font-medium text-green-600 uppercase"><i class="fas fa-user-edit mr-1"></i> Modificato da</label><p class="text-sm font-medium text-gray-900 mt-1">{{ $selectedInvoice->updater->name ?? $selectedInvoice->creator->name ?? 'Sistema' }}</p><p class="text-xs text-gray-500 mt-1">{{ $selectedInvoice->updated_at ? $selectedInvoice->updated_at->format('d/m/Y H:i:s') : '-' }}</p></div>
                        </div>
                    </div>
                    @if($selectedInvoice->payments && $selectedInvoice->payments->count() > 0)
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2"><i class="fas fa-credit-card mr-2 text-green-600"></i> Piano Scadenze / Pagamenti @if($selectedInvoice->payments->count() > 1)<span class="text-xs text-gray-500 ml-2">({{ $selectedInvoice->payments->count() }} rate)</span>@endif</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Data scadenza</th><th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Importo</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Modalità pagamento</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500">IBAN</th><th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Stato</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($selectedInvoice->payments as $payment)
                                    <tr><td class="px-3 py-2 text-sm">{{ $payment->due_date ? $payment->due_date->format('d/m/Y') : '-' }}</td><td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($payment->amount, 2, ',', '.') }} €</td><td class="px-3 py-2 text-sm">{{ $payment->payment_method_label ?? $payment->payment_method ?? '-' }}</td><td class="px-3 py-2 text-sm font-mono text-xs">{{ $payment->iban ?? '-' }}</td><td class="px-3 py-2 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->status_badge_class }}">{{ $payment->status_label }}</span></td></tr>
                                    @endforeach
                                </tbody>
                                @if($selectedInvoice->payments->count() > 1)
                                <tfoot class="bg-gray-50"><tr><td class="px-3 py-2 text-right font-bold">Totale pagamenti</td><td class="px-3 py-2 text-right font-bold text-green-600">{{ number_format($selectedInvoice->payments->sum('amount'), 2, ',', '.') }} €</td><td colspan="3"></td></tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="mb-4 bg-gray-50 rounded-lg p-3"><p class="text-sm text-gray-500 text-center"><i class="fas fa-info-circle mr-1"></i> Nessun dato di pagamento disponibile</p></div>
                    @endif
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2">Righe Fattura</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Descrizione</th><th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Quantità</th><th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Prezzo Unit.</th><th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Sconto</th><th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Totale</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($selectedInvoice->rows as $row)
                                    <tr><td class="px-3 py-2 text-sm">{{ $row->description }}</td><td class="px-3 py-2 text-sm text-right">{{ number_format($row->quantity, 3, ',', '.') }}</td><td class="px-3 py-2 text-sm text-right">{{ number_format($row->unit_price, 4, ',', '.') }} €</td><td class="px-3 py-2 text-sm text-right">{{ $row->discount_percentage > 0 ? number_format($row->discount_percentage, 2, ',', '.') . '%' : '-' }}</td><td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($row->total, 2, ',', '.') }} €</td></tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr><td colspan="4" class="px-3 py-2 text-right font-bold">TOTALE</td><td class="px-3 py-2 text-right font-bold text-lg">{{ number_format($selectedInvoice->importo_totale, 2, ',', '.') }} €</td></tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 border-t pt-3 mt-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div><i class="fas fa-calendar-alt mr-1"></i> Importata il: {{ $selectedInvoice->imported_at ? $selectedInvoice->imported_at->format('d/m/Y H:i:s') : '-' }}</div>
                            <div><i class="fas fa-file-code mr-1"></i> File XML: {{ basename($selectedInvoice->xml_filename ?? '') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL CONFERMA ELIMINAZIONE ==================== -->
    @if($showDeleteModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6" x-on:click.away="show = false; $wire.cancelDelete()" x-transition.scale.origin.top>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">Sei sicuro di voler eliminare <strong>{{ $invoiceNameToDelete }}</strong>?<br><span class="text-xs text-gray-400">La fattura verrà spostata nel cestino e potrà essere ripristinata.</span></p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">Annulla</button>
                    <button wire:click="deleteInvoice" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">Elimina</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL CESTINO ==================== -->
    @if($showTrashModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto" x-on:click.away="show = false; $wire.closeTrashModal()" x-transition.scale.origin.top>
            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-trash-alt mr-2 text-red-600"></i>Cestino - Fatture Eliminate</h2>
                <button wire:click="closeTrashModal" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" wire:model.live="trashSearch" placeholder="Cerca per numero fattura o fornitore..." class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('n_invoice')">
                                <div class="flex items-center gap-1">Numero @if($trashSortField === 'n_invoice')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('data_invoice')">
                                <div class="flex items-center gap-1">Data Fattura @if($trashSortField === 'data_invoice')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totale</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="trashSortBy('deleted_at')">
                                <div class="flex items-center gap-1">Data eliminazione @if($trashSortField === 'deleted_at')<i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>@endif</div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trashedInvoices as $invoice)
                        <tr wire:key="trash-{{ $invoice->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->n_invoice }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->data_invoice->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $invoice->supplier_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">{{ number_format($invoice->importo_totale, 2, ',', '.') }} €</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->deleted_at ? $invoice->deleted_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <button wire:click="restoreFromTrash({{ $invoice->id }})" class="text-green-600 hover:text-green-900 transition-colors" title="Ripristina"><i class="fas fa-trash-restore text-lg"></i></button>
                                    <button wire:click="forceDeleteFromTrash({{ $invoice->id }})" onclick="return confirm('Eliminazione definitiva incluse le righe? Operazione non reversibile.')" class="text-red-600 hover:text-red-900 transition-colors" title="Elimina definitivamente"><i class="fas fa-skull-crosswalk text-lg"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-trash-alt text-gray-400 text-5xl mb-2"></i>
                                <p class="text-sm text-gray-500 mt-2">Il cestino è vuoto</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($trashedInvoices->hasPages())
            <div class="mt-4">
                <div class="text-sm text-gray-500 mb-2">{{ $trashedInvoices->firstItem() }} - {{ $trashedInvoices->lastItem() }} di {{ $trashedInvoices->total() }} elementi</div>
                <div class="flex justify-center">{{ $trashedInvoices->links() }}</div>
            </div>
            @endif
            <div class="flex justify-end mt-6 pt-4 border-t">
                <button wire:click="closeTrashModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors"><i class="fas fa-times mr-2"></i> Chiudi</button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('showSuccess', (event) => {
            // Implementa il tuo toast di successo
            console.log('Success:', event.message);
        });
        Livewire.on('showError', (event) => {
            // Implementa il tuo toast di errore
            console.error('Error:', event.message);
        });
    });
</script>