<div>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-dolly w-5 h-5 mr-3 text-lime-600"></i>
            Fatture di Acquisto
        </h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices-received.xml-import') }}" class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-upload"></i>
                Importa XML
            </a>

            <!-- Pulsante Cestino con badge contatore -->
            <div class="relative group">
                <button wire:click="openTrashModal"
                        id="trashButton"
                        class="relative px-5 py-2.5 rounded-lg shadow-md transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300">
                    <i class="fas fa-trash-alt"></i>
                    <span id="trashCountBadge"
                          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center shadow-md"
                          style="{{ $trashCount == 0 ? 'display: none;' : '' }}">
                        {{ $trashCount }}
                    </span>
                </button>
                <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                    Cestino
                    <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </div>
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
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('data_invoice')">
                            <div class="flex items-center gap-1">
                                Data
                                @if($sortField === 'data_invoice')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proprietà</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('importo_totale')">
                            <div class="flex items-center justify-end gap-1">
                                Totale
                                @if($sortField === 'importo_totale')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition" wire:key="invoice-{{ $invoice->id }}">
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
                            <div class="flex items-center justify-end space-x-3">
                                <!-- Icona Lente di ingrandimento per visualizzare XML come PDF -->
                                <a href="{{ route('admin.invoices-received.xml-pdf', $invoice->id) }}" 
                                target="_blank"
                                class="text-purple-600 hover:text-purple-900 transition-colors"
                                title="Visualizza XML come PDF">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                                
                                <button wire:click="showDetails({{ $invoice->id }})"
                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                        title="Visualizza dettagli">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                
                                <button wire:click="confirmDelete({{ $invoice->id }})"
                                        class="text-red-600 hover:text-red-900 transition-colors"
                                        title="Elimina fattura">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-file-invoice text-4xl text-gray-400 mb-3"></i>
                                <p class="mt-2">Nessuna fattura trovata</p>
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

    <!-- ============================================ -->
    <!-- MODAL DETTAGLI FATTURA -->
    <!-- ============================================ -->
    @if($showModal && $selectedInvoice)
    <div x-data="{ open: true }"
        x-show="open"
        x-init="$watch('open', value => { if (!value) $wire.closeModal() })"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true">

        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-on:click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">

                <div class="bg-white px-6 pt-5 pb-4 border-b">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Dettaglio Fattura
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Fattura n. {{ $selectedInvoice->n_invoice }} del {{ $selectedInvoice->data_invoice->format('d/m/Y') }}
                            </p>
                        </div>
                        <button x-on:click="open = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div class="flex justify-end mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedInvoice->status_badge_class }}">
                            {{ $selectedInvoice->status_label }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
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

                    <div class="text-xs text-gray-400 border-t pt-3 mt-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div>Importata il: {{ $selectedInvoice->imported_at ? $selectedInvoice->imported_at->format('d/m/Y H:i:s') : '-' }}</div>
                            <div>File XML: {{ basename($selectedInvoice->xml_filename ?? '') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ============================================ -->
    <!-- MODAL CONFERMA ELIMINAZIONE -->
    <!-- ============================================ -->
    @if($showDeleteModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6"
             x-on:click.away="show = false; $wire.cancelDelete()"
             x-transition.scale.origin.top>

            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Conferma eliminazione</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Sei sicuro di voler eliminare <strong>{{ $invoiceNameToDelete }}</strong>?
                    <br>
                    <span class="text-xs text-gray-400">La fattura verrà spostata nel cestino e potrà essere ripristinata.</span>
                </p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                        Annulla
                    </button>
                    <button wire:click="deleteInvoice"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ============================================ -->
    <!-- MODAL CESTINO -->
    <!-- ============================================ -->
    @if($showTrashModal)
    <div wire:ignore.self class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         x-data="{ show: true }"
         x-show="show"
         x-transition.opacity.duration.200ms>

        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl p-6 max-h-[90vh] overflow-y-auto"
             x-on:click.away="show = false; $wire.closeTrashModal()"
             x-transition.scale.origin.top>

            <div class="flex justify-between items-center mb-6 border-b pb-3">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-trash-alt mr-2 text-red-600"></i>
                    Cestino - Fatture Eliminate
                </h2>
                <button wire:click="closeTrashModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Filtro ricerca -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text"
                           wire:model.live="trashSearch"
                           placeholder="Cerca per numero fattura o fornitore..."
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <!-- Tabella cestino -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="trashSortBy('n_invoice')">
                                <div class="flex items-center gap-1">
                                    Numero
                                    @if($trashSortField === 'n_invoice')
                                        <i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="trashSortBy('data_invoice')">
                                <div class="flex items-center gap-1">
                                    Data Fattura
                                    @if($trashSortField === 'data_invoice')
                                        <i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornitore</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totale</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="trashSortBy('deleted_at')">
                                <div class="flex items-center gap-1">
                                    Data eliminazione
                                    @if($trashSortField === 'deleted_at')
                                        <i class="fas fa-sort-{{ $trashSortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trashedInvoices as $invoice)
                        <tr wire:key="trash-{{ $invoice->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $invoice->n_invoice }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $invoice->data_invoice->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $invoice->supplier_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ number_format($invoice->importo_totale, 2, ',', '.') }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $invoice->deleted_at ? $invoice->deleted_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <button wire:click="restoreFromTrash({{ $invoice->id }})"
                                            class="text-green-600 hover:text-green-900 transition-colors"
                                            title="Ripristina">
                                        <i class="fas fa-trash-restore text-lg"></i>
                                    </button>
                                    <button wire:click="forceDeleteFromTrash({{ $invoice->id }})"
                                            onclick="return confirm('Eliminazione definitiva incluse le righe? Operazione non reversibile.')"
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            title="Elimina definitivamente">
                                        <i class="fas fa-skull-crosswalk text-lg"></i>
                                    </button>
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

            <!-- Paginazione cestino -->
            @if($trashedInvoices->hasPages())
            <div class="mt-4">
                <div class="text-sm text-gray-500 mb-2">
                    {{ $trashedInvoices->firstItem() }} - {{ $trashedInvoices->lastItem() }} di {{ $trashedInvoices->total() }} elementi
                </div>
                <div class="flex justify-center">
                    {{ $trashedInvoices->links() }}
                </div>
            </div>
            @endif

            <div class="flex justify-end mt-6 pt-4 border-t">
                <button wire:click="closeTrashModal"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    <i class="fas fa-times mr-2"></i> Chiudi
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Script badge cestino -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('trashCountUpdated', (data) => {
                const badge = document.getElementById('trashCountBadge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
        });
    </script>
</div>